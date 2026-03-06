<?php
/**
 * Diagnóstico de product_list — BORRAR DESPUÉS DE USAR
 * URL: /whatsapp/check-productlist.php?secret=TU_CLAVE
 *
 * Qué hace:
 *  1. Lee los primeros productos activos con stock > 0 de la BD
 *  2. Hace la llamada real a /messages con type=product_list
 *  3. Muestra el payload enviado y la respuesta cruda de Meta
 */
declare(strict_types=1);

// ── Protección básica ─────────────────────────────────────────────────────────
$secret = 'CAMBIA_ESTA_CLAVE';          // ← cambia esto antes de subir
if (($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Bootstrap mínimo ──────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

// ── Leer productos de la BD ───────────────────────────────────────────────────
$db = wa_db();

// Sin filtro wa_in_catalog para este diagnóstico (queremos ver si al menos uno funciona)
$rows = $db->query("SELECT id, name, price FROM products WHERE status = 'active' AND stock > 0 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "ERROR: No hay productos activos con stock > 0 en la BD.\n";
    exit;
}

echo "=== PRODUCTOS QUE SE ENVIARÍAN ===\n";
foreach ($rows as $r) {
    echo "  retailer_id={$r['id']}  nombre={$r['name']}  precio={$r['price']}\n";
}
echo "\n";

// ── Construir payload ─────────────────────────────────────────────────────────
$catalogId     = WA_CATALOG_ID;
$phoneNumberId = WA_PHONE_NUMBER_ID;
$token         = WA_ACCESS_TOKEN;
$version       = WA_API_VERSION;

// Número de prueba: el primero de la tabla de conversaciones (o pon el tuyo)
$testPhone = $_GET['phone'] ?? '';
if (!$testPhone) {
    echo "Pasá ?phone=598XXXXXXXX para probar el envío real.\n";
    echo "(sin phone=, solo se muestra el payload, NO se envía nada)\n\n";
}

$productItems = array_map(fn($r) => ['product_retailer_id' => (string)$r['id']], $rows);

$payload = [
    'messaging_product' => 'whatsapp',
    'to'                => $testPhone ?: '59800000000',
    'type'              => 'interactive',
    'interactive'       => [
        'type'   => 'product_list',
        'header' => ['type' => 'text', 'text' => 'Diagnóstico'],
        'body'   => ['text' => 'Prueba de product_list'],
        'footer' => ['text' => 'TiendaMoroni'],
        'action' => [
            'catalog_id' => $catalogId,
            'sections'   => [
                [
                    'title'         => 'Productos',
                    'product_items' => $productItems,
                ],
            ],
        ],
    ],
];

echo "=== PAYLOAD A ENVIAR ===\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
echo "catalog_id usado: {$catalogId}\n";
echo "phone_number_id:  {$phoneNumberId}\n\n";

if (!$testPhone) {
    echo "→ NO se hizo el POST (falta ?phone=). Agregá el parámetro para probarlo.\n";
    exit;
}

// ── POST real ────────────────────────────────────────────────────────────────
$url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

echo "=== RESPUESTA DE META ===\n";
echo "HTTP Code: {$httpCode}\n";
if ($curlErr) {
    echo "cURL error: {$curlErr}\n";
} else {
    $decoded = json_decode($response, true);
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// ── Verificar commerce settings ───────────────────────────────────────────────
echo "\n=== WHATSAPP COMMERCE SETTINGS ===\n";
$csUrl = "https://graph.facebook.com/{$version}/{$phoneNumberId}/whatsapp_commerce_settings?fields=is_cart_enabled,is_catalog_visible,catalog_id&access_token={$token}";
$csRes = @file_get_contents($csUrl);
$cs    = json_decode($csRes ?: '{}', true) ?: [];
echo json_encode($cs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
$linkedCatalogId = $cs['data'][0]['catalog_id'] ?? null;
echo "\ncatalog_id real vinculado a WhatsApp: " . ($linkedCatalogId ?? '(NINGUNO - catálogo no vinculado a este WABA)') . "\n";

// ── Buscar WABA ID y sus catálogos ───────────────────────────────────────────
echo "\n=== WABA ASOCIADO AL PHONE NUMBER ===\n";
$wabaUrl = "https://graph.facebook.com/{$version}/{$phoneNumberId}?fields=id,display_phone_number,verified_name,whatsapp_business_account&access_token={$token}";
$wabaRes = @file_get_contents($wabaUrl);
$wabaData = json_decode($wabaRes ?: '{}', true) ?: [];
echo json_encode($wabaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

$wabaId = $wabaData['whatsapp_business_account']['id'] ?? null;
if ($wabaId) {
    echo "\n=== CATÁLOGOS VINCULADOS AL WABA {$wabaId} ===\n";
    $catUrl = "https://graph.facebook.com/{$version}/{$wabaId}?fields=id,name,message_template_namespace,on_behalf_of_business_info&access_token={$token}";
    $catRes = @file_get_contents($catUrl);
    echo json_decode($catRes ?: '{}', true) ? json_encode(json_decode($catRes, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $catRes;
    echo "\n";
}

// ── Verificar si el catalog_id configurado existe y tiene canales ────────────
echo "\n=== CATALOG {$catalogId} - CANALES CONECTADOS ===\n";
$catChUrl = "https://graph.facebook.com/{$version}/{$catalogId}?fields=id,name,product_count,vertical,destination_catalogs&access_token={$token}";
$catChRes = @file_get_contents($catChUrl);
$catCh    = json_decode($catChRes ?: '{}', true) ?: [];
echo json_encode($catCh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== RESUMEN DE DIAGNÓSTICO ===\n";
if (!$linkedCatalogId) {
    echo "❌ PROBLEMA ENCONTRADO: No hay ningún catálogo vinculado a este phone_number_id en WhatsApp.\n";
    echo "   El campo 'catalog_id' no aparece en whatsapp_commerce_settings.\n";
    echo "   Solución: En Meta Business Manager → WhatsApp Manager → Catálogos → vinculá tu catálogo.\n";
} elseif ($linkedCatalogId !== $catalogId) {
    echo "❌ PROBLEMA: catalog_id en config ({$catalogId}) no coincide con el vinculado ({$linkedCatalogId}).\n";
    echo "   Actualizá WA_CATALOG_ID en config.php a {$linkedCatalogId}\n";
} else {
    echo "✅ El catalog_id coincide ({$catalogId}).\n";
    echo "   Si aun así falla, los productos no están aprobados en Commerce Manager para WhatsApp.\n";
}
