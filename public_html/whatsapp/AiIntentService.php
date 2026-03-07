<?php
/**
 * AiIntentService – Clasificador de intenciones del usuario.
 *
 * Estrategia de evaluación (en orden):
 *   1. Reglas locales: regex de búsqueda (más específicos, exigen un producto)
 *   2. Reglas locales: keywords de FAQ (más generales)
 *   3. Gemini AI: solo si las reglas no dan match con confianza suficiente
 *   4. Fallback seguro: si todo falla, devuelve intent=fallback
 *
 * El servicio NUNCA genera contenido. Solo clasifica.
 * Devuelve siempre una estructura normalizada:
 *   [
 *     'intent'      => 'faq' | 'search' | 'fallback',
 *     'faq_key'     => string|null,
 *     'search_term' => string|null,
 *     'confidence'  => float,
 *     'source'      => 'rule' | 'ai' | 'fallback',
 *   ]
 *
 * Kill-switch: si WA_AI_ENABLED === false, solo se evalúan reglas locales.
 * Si las reglas no matchean, se devuelve fallback sin consultar Gemini.
 */

declare(strict_types=1);

class AiIntentService
{
    private array  $faqData;
    private array  $rulesData;
    private Logger $logger;
    private bool   $aiEnabled;
    private string $geminiApiKey;
    private string $geminiModel;

    /** En segundos. Si Gemini tarda más, se aborta y va a fallback. */
    private const GEMINI_TIMEOUT = 6;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;

        // ── Cargar JSONs ─────────────────────────────────────────────────────
        $faqPath   = __DIR__ . '/faq.json';
        $rulesPath = __DIR__ . '/intent_rules.json';

        $this->faqData   = $this->loadJson($faqPath);
        $this->rulesData = $this->loadJson($rulesPath);

        // ── Kill-switch y credenciales ────────────────────────────────────────
        $this->aiEnabled    = defined('WA_AI_ENABLED') && WA_AI_ENABLED === true;
        $this->geminiApiKey = defined('WA_GEMINI_API_KEY') ? WA_GEMINI_API_KEY : '';
        $this->geminiModel  = defined('WA_GEMINI_MODEL') ? WA_GEMINI_MODEL : 'gemini-2.0-flash';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  PUNTO DE ENTRADA PÚBLICO
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Clasifica un texto libre del usuario.
     *
     * @param string $text  Mensaje crudo del usuario.
     * @return array        Estructura normalizada de intent.
     */
    public function classify(string $text): array
    {
        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return $this->result('fallback', null, null, 0.0, 'rule');
        }

        // ── Paso 1: Búsqueda por regex/frase (más específico → va primero) ────
        // Los extractors exigen un término concreto después del verbo, así que
        // "quiero comprar una tapa" → search, pero "cómo comprar?" → no matchea.
        $searchTerm = $this->matchSearch($normalized);
        if ($searchTerm !== null) {
            $this->logger->info("AI_INTENT: search match term=\"{$searchTerm}\" source=rule text=\"{$normalized}\"");
            return $this->result('search', null, $searchTerm, 0.95, 'rule');
        }

        // ── Paso 2: FAQ por keywords ─────────────────────────────────────────
        $faqResult = $this->matchFaq($normalized);
        if ($faqResult !== null) {
            $this->logger->info("AI_INTENT: FAQ match key={$faqResult} source=rule text=\"{$normalized}\"");
            return $this->result('faq', $faqResult, null, 1.0, 'rule');
        }

        // ── Paso 3: Stopwords → fallback directo (no gastar en AI) ───────────
        if ($this->isStopword($normalized)) {
            $this->logger->info("AI_INTENT: stopword detected → fallback text=\"{$normalized}\"");
            return $this->result('fallback', null, null, 1.0, 'rule');
        }

        // ── Paso 4: Gemini AI (solo si habilitado y con API key) ─────────────
        if ($this->aiEnabled && $this->geminiApiKey !== '') {
            $aiResult = $this->queryGemini($text);
            if ($aiResult !== null) {
                $this->logger->info(
                    "AI_INTENT: gemini result intent={$aiResult['intent']}"
                    . " search_term=" . ($aiResult['search_term'] ?? 'null')
                    . " faq_key=" . ($aiResult['faq_key'] ?? 'null')
                    . " confidence={$aiResult['confidence']}"
                );
                return $aiResult;
            }
            // Si Gemini falla, se cae al fallback de abajo
        }

        // ── Paso 5: Fallback seguro ──────────────────────────────────────────
        $this->logger->info("AI_INTENT: fallback (sin match) text=\"{$normalized}\"");
        return $this->result('fallback', null, null, 0.0, 'fallback');
    }

    /**
     * Devuelve la respuesta de texto de una FAQ por su clave.
     * Retorna null si la clave no existe o la FAQ está deshabilitada.
     */
    public function getFaqResponse(string $key): ?string
    {
        $item = $this->faqData['items'][$key] ?? null;
        if ($item === null || !($item['enabled'] ?? false)) {
            return null;
        }
        return (string)$item['response'];
    }

    /**
     * Devuelve el mensaje de fallback configurado en faq.json.
     */
    public function getFallbackMessage(): string
    {
        return (string)($this->faqData['default_fallback_message']
            ?? $this->rulesData['fallback']['message']
            ?? 'Puedes usar el menú para explorar el catálogo o realizar una búsqueda.');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  REGLAS LOCALES (sin red)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Busca match de FAQ comparando keywords contra el texto normalizado.
     * Retorna la faq_key o null.
     */
    private function matchFaq(string $normalized): ?string
    {
        $enabled = $this->rulesData['faq_routing']['enabled'] ?? true;
        if (!$enabled) {
            return null;
        }

        $minMatches = (int)($this->rulesData['faq_routing']['min_keyword_matches'] ?? 1);
        $items      = $this->faqData['items'] ?? [];

        foreach ($items as $key => $item) {
            if (!($item['enabled'] ?? false)) {
                continue;
            }
            $keywords = $item['keywords'] ?? [];
            $matches  = 0;

            foreach ($keywords as $kw) {
                $kwNorm = $this->normalize($kw);
                if ($kwNorm !== '' && mb_strpos($normalized, $kwNorm) !== false) {
                    $matches++;
                    if ($matches >= $minMatches) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Intenta extraer un search_term del texto usando las reglas de intent_rules.json.
     * Primero prueba los extractors regex; si no matchean, busca frases indicadoras.
     */
    private function matchSearch(string $normalized): ?string
    {
        $searchConf = $this->rulesData['search_intent'] ?? [];
        if (!($searchConf['enabled'] ?? true)) {
            return null;
        }

        // ── Extractors regex (más precisos) ──────────────────────────────────
        $extractors = $searchConf['extractors'] ?? [];
        foreach ($extractors as $ext) {
            if (($ext['type'] ?? '') !== 'regex') {
                continue;
            }
            $pattern = $ext['pattern'] ?? '';
            $group   = (int)($ext['group'] ?? 1);
            if ($pattern !== '' && preg_match('/' . $pattern . '/iu', $normalized, $m)) {
                $term = trim($m[$group] ?? '');
                $term = $this->cleanSearchTerm($term);
                if ($term !== '') {
                    return $term;
                }
            }
        }

        // ── Frases indicadoras (menos precisas pero útiles) ──────────────────
        $phrases = $searchConf['phrases'] ?? [];
        foreach ($phrases as $phrase) {
            $phraseNorm = $this->normalize($phrase);
            if ($phraseNorm !== '' && mb_strpos($normalized, $phraseNorm) !== false) {
                // Extraer lo que viene DESPUÉS de la frase
                $pos  = mb_strpos($normalized, $phraseNorm);
                $rest = trim(mb_substr($normalized, $pos + mb_strlen($phraseNorm)));
                $rest = $this->cleanSearchTerm($rest);
                if ($rest !== '') {
                    return $rest;
                }
                // Si la frase matchea pero no queda nada útil, no es una búsqueda
            }
        }

        return null;
    }

    /**
     * Verifica si el texto es una stopword que no debería ir a Gemini.
     */
    private function isStopword(string $normalized): bool
    {
        $stopwords = $this->rulesData['search_intent']['stopwords'] ?? [];
        foreach ($stopwords as $sw) {
            if ($this->normalize($sw) === $normalized) {
                return true;
            }
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  GEMINI AI
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Consulta Gemini para clasificar el texto cuando las reglas locales no alcanzan.
     * Retorna array normalizado o null si falla.
     */
    private function queryGemini(string $userText): ?array
    {
        // ── Construir lista de FAQ keys disponibles ──────────────────────────
        $faqKeys = [];
        foreach (($this->faqData['items'] ?? []) as $key => $item) {
            if ($item['enabled'] ?? false) {
                $faqKeys[] = $key;
            }
        }

        $systemPrompt = <<<PROMPT
Eres un clasificador de intenciones para un bot de WhatsApp de una tienda online de productos artesanales.

Tu ÚNICA tarea es clasificar el mensaje del usuario en UNA de estas tres categorías:
1. "faq" → el usuario pregunta algo que se responde con información de la tienda
2. "search" → el usuario quiere buscar o comprar un producto
3. "fallback" → no se puede clasificar con seguridad

FAQ keys disponibles: %FAQ_KEYS%

Responde SOLAMENTE con un JSON válido, sin texto adicional, sin markdown:
{"intent":"faq|search|fallback","faq_key":"clave_o_null","search_term":"término_o_null","confidence":0.0}

Reglas estrictas:
- Si intent=faq, faq_key DEBE ser una de las keys disponibles, search_term=null
- Si intent=search, search_term DEBE ser una palabra o frase corta (máx 3 palabras) útil para buscar en base de datos con LIKE, faq_key=null
- Si intent=fallback, ambos deben ser null
- confidence es un float entre 0.0 y 1.0
- NO inventes productos, precios ni información
- Si no estás seguro, usa fallback
PROMPT;

        $systemPrompt = str_replace('%FAQ_KEYS%', implode(', ', $faqKeys), $systemPrompt);

        try {
            $url = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                $this->geminiModel,
                $this->geminiApiKey
            );

            $payload = [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $userText]],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 100,
                    'responseMimeType' => 'application/json',
                ],
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::GEMINI_TIMEOUT,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            ]);

            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                $this->logger->error('AI_INTENT: Gemini cURL error', ['error' => $curlErr]);
                return null;
            }

            if ($httpCode !== 200) {
                $this->logger->error('AI_INTENT: Gemini HTTP error', [
                    'http_code' => $httpCode,
                    'response'  => mb_substr((string)$response, 0, 500),
                ]);
                return null;
            }

            $decoded = json_decode((string)$response, true);
            $text    = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text === null) {
                $this->logger->error('AI_INTENT: Gemini response sin texto', [
                    'response' => mb_substr((string)$response, 0, 500),
                ]);
                return null;
            }

            // Limpiar posible markdown wrapping
            $text = trim($text);
            if (str_starts_with($text, '```')) {
                $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/\s*```$/', '', $text);
            }

            $parsed = json_decode($text, true);
            if (!is_array($parsed) || !isset($parsed['intent'])) {
                $this->logger->error('AI_INTENT: Gemini JSON inválido', ['raw' => $text]);
                return null;
            }

            // ── Validar y normalizar respuesta ───────────────────────────────
            $intent     = $parsed['intent'] ?? 'fallback';
            $faqKey     = $parsed['faq_key'] ?? null;
            $searchTerm = $parsed['search_term'] ?? null;
            $confidence = (float)($parsed['confidence'] ?? 0.0);

            // Validar que intent sea uno de los permitidos
            if (!in_array($intent, ['faq', 'search', 'fallback'], true)) {
                $intent = 'fallback';
            }

            // Validar faq_key existe
            if ($intent === 'faq') {
                if ($faqKey === null || !isset($this->faqData['items'][$faqKey])) {
                    $this->logger->info("AI_INTENT: Gemini devolvió faq_key inválida: {$faqKey}");
                    $intent = 'fallback';
                    $faqKey = null;
                }
            }

            // Validar search_term no vacío
            if ($intent === 'search') {
                $searchTerm = trim((string)$searchTerm);
                $searchTerm = $this->cleanSearchTerm($searchTerm);
                if ($searchTerm === '') {
                    $this->logger->info('AI_INTENT: Gemini devolvió search sin término');
                    $intent     = 'fallback';
                    $searchTerm = null;
                }
            }

            // Verificar confianza mínima para search
            $minConf = (float)($this->rulesData['settings']['min_confidence_for_ai_search'] ?? 0.7);
            if ($intent === 'search' && $confidence < $minConf) {
                $this->logger->info("AI_INTENT: confidence={$confidence} < min={$minConf} → fallback");
                $intent     = 'fallback';
                $searchTerm = null;
            }

            return $this->result($intent, $faqKey, $searchTerm, $confidence, 'ai');

        } catch (\Throwable $e) {
            $this->logger->error('AI_INTENT: Gemini exception', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Normaliza texto para comparación: lowercase, trim, quita acentos opcionalmente.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        // Quitar acentos si está configurado
        if ($this->rulesData['settings']['normalize_accents'] ?? true) {
            $text = $this->removeAccents($text);
        }

        return $text;
    }

    /**
     * Remueve acentos preservando la ñ.
     */
    private function removeAccents(string $text): string
    {
        // Preservar ñ/Ñ temporalmente
        $text = str_replace(['ñ', 'Ñ'], ['__NY__', '__NY__'], $text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = str_replace('__NY__', 'ñ', $text);
        return $text;
    }

    /**
     * Limpia un search_term aplicando los cleanup_patterns de intent_rules.json.
     */
    private function cleanSearchTerm(string $term): string
    {
        $patterns = $this->rulesData['search_intent']['cleanup_patterns'] ?? [];
        foreach ($patterns as $pattern) {
            $term = (string)preg_replace('/' . $pattern . '/iu', '', $term);
        }
        return trim($term);
    }

    /**
     * Carga y decodifica un archivo JSON. Retorna array vacío si falla.
     */
    private function loadJson(string $path): array
    {
        if (!file_exists($path)) {
            $this->logger->error("AI_INTENT: JSON no encontrado: {$path}");
            return [];
        }
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            $this->logger->error("AI_INTENT: JSON inválido: {$path}");
            return [];
        }
        return $data;
    }

    /**
     * Construye el array de resultado normalizado.
     */
    private function result(
        string  $intent,
        ?string $faqKey,
        ?string $searchTerm,
        float   $confidence,
        string  $source
    ): array {
        return [
            'intent'      => $intent,
            'faq_key'     => $faqKey,
            'search_term' => $searchTerm,
            'confidence'  => $confidence,
            'source'      => $source,
        ];
    }
}
