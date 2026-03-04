<?php
/**
 * webhook.php – Endpoint público del bot de WhatsApp para TiendaMoroni.
 *
 * URL de producción: https://tiendamoroni.com/whatsapp/webhook.php
 *
 * GET  → Verificación del webhook por Meta al registrarlo en el panel de Developers.
 * POST → Recepción de mensajes, status updates y otros eventos de WhatsApp.
 *
 * Seguridad implementada:
 *   - Verificación de HMAC-SHA256 (X-Hub-Signature-256) en cada POST.
 *   - Respuesta 200 inmediata para evitar reintentos de Meta (< 5 s).
 *   - Procesamiento asíncrono usando fastcgi_finish_request() o output buffering.
 */

declare(strict_types=1);

// ── Cargar dependencias del módulo ────────────────────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/ConversationManager.php';
require_once __DIR__ . '/WhatsAppAPI.php';
require_once __DIR__ . '/ProductService.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/MessageRouter.php';

// ── GET: Verificación del webhook por Meta ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Meta envía: ?hub.mode=subscribe&hub.verify_token=TOKEN&hub.challenge=CHALLENGE
    $mode      = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';

    if ($mode === 'subscribe' && hash_equals(WA_VERIFY_TOKEN, $token)) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    exit('Verificación fallida');
}

// ── POST: Recepción de eventos de WhatsApp ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Leer el cuerpo crudo ANTES de cualquier otra operación
    $rawBody = (string)file_get_contents('php://input');

    // ── Validar firma HMAC-SHA256 ─────────────────────────────────────────────
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected  = 'sha256=' . hash_hmac('sha256', $rawBody, WA_APP_SECRET);

    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        exit('Firma inválida');
    }

    // ── Responder 200 OK de inmediato ─────────────────────────────────────────
    // Meta espera respuesta en < 5 segundos. Si falla 5 veces seguidas, desactiva el webhook.
    http_response_code(200);
    header('Content-Type: text/plain');

    if (function_exists('fastcgi_finish_request')) {
        // En PHP-FPM: responder al cliente y seguir ejecutando en background
        echo 'OK';
        fastcgi_finish_request();
    } else {
        // En Apache mod_php: usar output buffering para enviar la respuesta
        ignore_user_abort(true);
        ob_start();
        echo 'OK';
        $size = ob_get_length();
        header('Content-Length: ' . $size);
        ob_end_flush();
        flush();
    }

    // ── Procesar el payload ───────────────────────────────────────────────────
    $payload = json_decode($rawBody, true);

    // Ignorar eventos que no sean de WhatsApp Business Account
    if (!is_array($payload) || ($payload['object'] ?? '') !== 'whatsapp_business_account') {
        exit;
    }

    try {
        // Instanciar servicios
        $pdo    = wa_db();
        $logger = new Logger($pdo, WA_LOG_DIR);

        $config = [
            'whatsapp' => [
                'phone_number_id' => WA_PHONE_NUMBER_ID,
                'access_token'    => WA_ACCESS_TOKEN,
                'catalog_id'      => WA_CATALOG_ID,
                'api_version'     => WA_API_VERSION,
            ],
            'app' => [
                'base_url'             => WA_BASE_URL,
                'conversation_timeout' => WA_CONVERSATION_TIMEOUT,
            ],
        ];

        $conv   = new ConversationManager($pdo, WA_CONVERSATION_TIMEOUT);
        $waApi  = new WhatsAppAPI(WA_PHONE_NUMBER_ID, WA_ACCESS_TOKEN, WA_API_VERSION, $logger);
        $prods  = new ProductService($pdo);
        $orders = new OrderService($pdo);
        $router = new MessageRouter($waApi, $conv, $prods, $orders, $logger, $config);

        // ── Iterar sobre los entries del payload ──────────────────────────────
        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                // Solo procesar eventos de tipo "messages"
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                // Los status updates (delivery/read receipts) tienen 'statuses', no 'messages'
                // → simplemente los ignoramos
                $messages = $value['messages'] ?? [];
                if (empty($messages)) {
                    continue;
                }

                foreach ($messages as $message) {
                    $phone = (string)($message['from'] ?? '');
                    if ($phone === '') {
                        continue;
                    }

                    // Ignorar mensajes del propio número del bot (eco)
                    if ($phone === WA_PHONE_NUMBER_ID) {
                        continue;
                    }

                    $router->route($phone, $message);
                }
            }
        }

    } catch (Throwable $e) {
        // Loguear cualquier error fatal sin interrumpir la ejecución
        $logDir  = WA_LOG_DIR;
        $logFile = $logDir . '/' . date('Y-m') . '-errors.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }

        $entry = sprintf(
            "[%s] FATAL: %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    exit;
}

// ── Cualquier otro método HTTP ────────────────────────────────────────────────
http_response_code(405);
header('Allow: GET, POST');
echo 'Method Not Allowed';
