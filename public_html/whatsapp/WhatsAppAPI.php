<?php
/**
 * WhatsAppAPI – Cliente para la WhatsApp Cloud API de Meta.
 *
 * Límites de la API que se respetan estrictamente:
 *   - Reply buttons:    máx. 3 botones | título botón: máx. 20 chars
 *   - Interactive list: máx. 10 rows totales | título row: máx. 24 | desc: máx. 72
 *   - List header/footer: máx. 60 chars | button opener: máx. 20 chars
 *   - Product list: máx. 30 productos | máx. 10 secciones
 *
 * Todos los envíos usan POST a:
 *   https://graph.facebook.com/{version}/{phone_number_id}/messages
 */

declare(strict_types=1);

class WhatsAppAPI
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $apiVersion;
    private Logger $logger;

    public function __construct(
        string $phoneNumberId,
        string $accessToken,
        string $apiVersion,
        Logger $logger
    ) {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken   = $accessToken;
        $this->apiVersion    = $apiVersion;
        $this->logger        = $logger;
    }

    // ── Tipos de mensajes ─────────────────────────────────────────────────────

    /**
     * Envía un mensaje de texto simple.
     */
    public function sendText(string $to, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $text],
        ];
        return $this->post($to, 'text', $payload);
    }

    /**
     * Envía un mensaje con botones de respuesta rápida (máx. 3 botones).
     *
     * @param string $to      Número destino en formato internacional (ej: 598XXXXXXXX).
     * @param string $body    Texto principal del mensaje.
     * @param array  $buttons Array de ['id' => string, 'title' => string]. Máx. 3.
     * @param string $header  (opcional) Texto del header. Máx. 60 chars.
     * @param string $footer  (opcional) Texto del footer. Máx. 60 chars.
     */
    public function sendReplyButtons(
        string $to,
        string $body,
        array  $buttons,
        string $header = '',
        string $footer = ''
    ): array {
        $apiButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $apiButtons[] = [
                'type'  => 'reply',
                'reply' => [
                    'id'    => (string)$btn['id'],
                    'title' => wa_truncate((string)$btn['title'], 20),
                ],
            ];
        }

        $interactive = [
            'type'   => 'button',
            'body'   => ['text' => $body],
            'action' => ['buttons' => $apiButtons],
        ];

        if ($header !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => wa_truncate($header, 60)];
        }
        if ($footer !== '') {
            $interactive['footer'] = ['text' => wa_truncate($footer, 60)];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ];
        return $this->post($to, 'button', $payload);
    }

    /**
     * Envía una lista interactiva con secciones y filas (máx. 10 rows totales).
     *
     * @param string $to          Número destino.
     * @param string $header      Header del mensaje. Máx. 60 chars.
     * @param string $body        Cuerpo del mensaje.
     * @param string $footer      Footer del mensaje. Máx. 60 chars.
     * @param string $buttonText  Texto del botón que abre la lista. Máx. 20 chars.
     * @param array  $sections    Array de secciones:
     *                            [['title' => string, 'rows' => [['id', 'title', 'description'], ...]]]
     */
    public function sendList(
        string $to,
        string $header,
        string $body,
        string $footer,
        string $buttonText,
        array  $sections
    ): array {
        $apiSections = [];
        $totalRows   = 0;

        foreach ($sections as $section) {
            $rows = [];
            foreach (($section['rows'] ?? []) as $row) {
                if ($totalRows >= 10) {
                    break 2; // Respetar límite de 10 rows totales
                }
                $rowData = [
                    'id'    => (string)$row['id'],
                    'title' => wa_truncate((string)$row['title'], 24),
                ];
                $desc = wa_truncate((string)($row['description'] ?? ''), 72);
                if ($desc !== '') {
                    $rowData['description'] = $desc;
                }
                $rows[] = $rowData;
                $totalRows++;
            }
            if (!empty($rows)) {
                $apiSections[] = [
                    'title' => wa_truncate((string)($section['title'] ?? ''), 24),
                    'rows'  => $rows,
                ];
            }
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'header' => ['type' => 'text', 'text' => wa_truncate($header, 60)],
                'body'   => ['text' => $body],
                'footer' => ['text' => wa_truncate($footer, 60)],
                'action' => [
                    'button'   => wa_truncate($buttonText, 20),
                    'sections' => $apiSections,
                ],
            ],
        ];
        return $this->post($to, 'list', $payload);
    }

    /**
     * Envía un multi-product message (product_list).
     * Los productos se muestran con imagen, nombre y precio del catálogo de Meta.
     *
     * @param string $to        Número destino.
     * @param string $header    Header del mensaje. Máx. 60 chars.
     * @param string $body      Cuerpo del mensaje.
     * @param string $footer    Footer. Máx. 60 chars.
     * @param string $catalogId ID del catálogo en Meta Commerce Manager.
     * @param array  $sections  Array de secciones:
     *                          [['title' => string, 'products' => [['retailer_id' => string], ...]]]
     *                          Máx. 30 productos totales, máx. 10 secciones.
     */
    public function sendProductList(
        string $to,
        string $header,
        string $body,
        string $footer,
        string $catalogId,
        array  $sections
    ): array {
        $apiSections   = [];
        $totalProducts = 0;

        foreach (array_slice($sections, 0, 10) as $section) {
            $items = [];
            foreach (($section['products'] ?? []) as $product) {
                if ($totalProducts >= 30) {
                    break 2;
                }
                $items[] = ['product_retailer_id' => (string)$product['retailer_id']];
                $totalProducts++;
            }
            if (!empty($items)) {
                $sec = ['product_items' => $items];
                if (!empty($section['title'])) {
                    $sec['title'] = wa_truncate((string)$section['title'], 24);
                }
                $apiSections[] = $sec;
            }
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'product_list',
                'header' => ['type' => 'text', 'text' => wa_truncate($header, 60)],
                'body'   => ['text' => $body],
                'footer' => ['text' => wa_truncate($footer, 60)],
                'action' => [
                    'catalog_id' => $catalogId,
                    'sections'   => $apiSections,
                ],
            ],
        ];
        return $this->post($to, 'product_list', $payload);
    }

    /**
     * Envía un botón CTA (Call to Action) que abre una URL en el navegador.
     *
     * @param string $to          Número destino.
     * @param string $body        Texto del cuerpo.
     * @param string $displayText Texto visible del botón. Máx. 20 chars.
     * @param string $url         URL de destino.
     */
    public function sendCtaUrl(string $to, string $body, string $displayText, string $url): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'cta_url',
                'body' => ['text' => $body],
                'action' => [
                    'name'       => 'cta_url',
                    'parameters' => [
                        'display_text' => wa_truncate($displayText, 20),
                        'url'          => $url,
                    ],
                ],
            ],
        ];
        return $this->post($to, 'cta_url', $payload);
    }

    // ── Transporte ────────────────────────────────────────────────────────────

    /**
     * Realiza la llamada HTTP POST a la Graph API de Meta.
     * Registra el mensaje saliente en el Logger.
     *
     * @param string $to      Número destino (para el log).
     * @param string $type    Tipo de mensaje (para el log).
     * @param array  $payload Payload completo a enviar como JSON.
     * @return array          Respuesta decodificada de la API.
     */
    private function post(string $to, string $type, array $payload): array
    {
        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->apiVersion,
            $this->phoneNumberId
        );

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Registrar el mensaje saliente en la BD
        $this->logger->logOutgoing($to, $type, $payload);

        if ($curlError) {
            $this->logger->error('cURL error al enviar mensaje', [
                'error' => $curlError,
                'to'    => $to,
                'type'  => $type,
            ]);
            return ['error' => $curlError];
        }

        $decoded = json_decode($response ?: '{}', true) ?: [];

        if ($httpCode !== 200) {
            $this->logger->error('API error al enviar mensaje', [
                'http_code' => $httpCode,
                'response'  => $decoded,
                'to'        => $to,
                'type'      => $type,
            ]);
        }

        return $decoded;
    }
}
