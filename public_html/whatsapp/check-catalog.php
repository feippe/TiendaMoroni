<?php
/**
 * check-catalog.php – Script de diagnóstico para verificar el catálogo de Meta.
 *
 * Compara los productos de la BD local con los productos que Meta tiene
 * en Commerce Manager, para identificar cuáles faltan o están rechazados.
 *
 * USO (desde terminal):
 *   php check-catalog.php
 *
 * USO (desde navegador, protegido):
 *   https://tiendamoroni.com/whatsapp/check-catalog.php?key=TU_SECRET
 *
 * ELIMINAR después de resolver el problema.
 */

declare(strict_types=1);

// ── Protección básica ─────────────────────────────────────────────────────────
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    // En web, requerir key secreta por query string
    $key = $_GET['key'] ?? '';
    if ($key !== 'moroni_diag_2026') {
        http_response_code(403);
        exit('Acceso denegado');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

require_once __DIR__ . '/config.php';

// ── Configuración ─────────────────────────────────────────────────────────────
$catalogId   = WA_CATALOG_ID;
$accessToken = WA_ACCESS_TOKEN;
$apiVersion  = WA_API_VERSION;
$phoneNumId  = WA_PHONE_NUMBER_ID;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNÓSTICO DE CATÁLOGO META — TiendaMoroni\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ── 1. Verificar configuración ────────────────────────────────────────────────
echo "── 1. CONFIGURACIÓN ──────────────────────────────────────────\n";
echo "  catalog_id:       {$catalogId}\n";
echo "  phone_number_id:  {$phoneNumId}\n";
echo "  api_version:      {$apiVersion}\n";
echo "  access_token:     " . substr($accessToken, 0, 20) . "...\n\n";

// ── 2. Obtener productos de la BD local ───────────────────────────────────────
echo "── 2. PRODUCTOS EN BD LOCAL ────────────────────────────────────\n";
try {
    $pdo = wa_db();
    $stmt = $pdo->query(
        "SELECT id, name, price, stock, status, main_image_url
         FROM products
         WHERE status = 'active'
         ORDER BY id ASC"
    );
    $localProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Total productos activos: " . count($localProducts) . "\n\n";

    foreach ($localProducts as $p) {
        $stockStatus = ((int)$p['stock'] > 0) ? 'IN STOCK' : '⚠ OUT OF STOCK';
        $hasImage    = !empty($p['main_image_url']) ? 'OK' : '⚠ SIN IMAGEN';
        echo sprintf(
            "  [ID=%s] %-40s | $%s | stock=%d (%s) | img=%s\n",
            $p['id'],
            mb_substr($p['name'], 0, 40),
            number_format((float)$p['price'], 2),
            (int)$p['stock'],
            $stockStatus,
            $hasImage
        );
    }
    echo "\n";
} catch (Throwable $e) {
    echo "  ERROR al consultar BD: " . $e->getMessage() . "\n\n";
    $localProducts = [];
}

// ── 3. Consultar catálogo en Meta Commerce Manager ────────────────────────────
echo "── 3. PRODUCTOS EN CATÁLOGO DE META ────────────────────────────\n";
$metaProducts = [];
$url = sprintf(
    'https://graph.facebook.com/%s/%s/products?fields=retailer_id,name,availability,review_status,image_url&limit=100',
    $apiVersion,
    $catalogId
);

echo "  Consultando: {$url}\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $accessToken,
    ],
]);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "  ⚠ cURL error: {$curlError}\n\n";
} elseif ($httpCode !== 200) {
    echo "  ⚠ HTTP {$httpCode}: {$response}\n\n";
} else {
    $data = json_decode($response, true);
    $metaProducts = $data['data'] ?? [];

    echo "  Total productos en Meta: " . count($metaProducts) . "\n\n";

    if (empty($metaProducts)) {
        echo "  ⚠ ¡EL CATÁLOGO ESTÁ VACÍO EN META!\n";
        echo "  Posibles causas:\n";
        echo "    - El feed XML no fue sincronizado todavía\n";
        echo "    - Los productos fueron rechazados por Meta\n";
        echo "    - El catalog_id es incorrecto\n";
        echo "    - El access_token no tiene permisos sobre el catálogo\n\n";
    } else {
        foreach ($metaProducts as $mp) {
            $rid    = $mp['retailer_id'] ?? '(sin retailer_id)';
            $name   = $mp['name'] ?? '(sin nombre)';
            $avail  = $mp['availability'] ?? '(desconocido)';
            $review = $mp['review_status'] ?? '(sin review_status)';

            $statusIcon = match ($avail) {
                'in stock'     => '✅',
                'out of stock' => '⚠',
                default        => '❓',
            };
            $reviewIcon = match ($review) {
                'approved' => '✅',
                'pending'  => '⏳',
                'rejected' => '❌',
                default    => '❓',
            };

            echo sprintf(
                "  [retailer_id=%s] %-35s | %s %s | review: %s %s\n",
                $rid,
                mb_substr($name, 0, 35),
                $statusIcon,
                $avail,
                $reviewIcon,
                $review
            );
        }
        echo "\n";
    }
}

// ── 4. Cruzar datos: productos locales vs Meta ───────────────────────────────
echo "── 4. CRUCE: BD LOCAL vs CATÁLOGO META ─────────────────────────\n";
$metaByRetailerId = [];
foreach ($metaProducts as $mp) {
    $rid = (string)($mp['retailer_id'] ?? '');
    if ($rid !== '') {
        $metaByRetailerId[$rid] = $mp;
    }
}

$sendable    = [];
$notInMeta   = [];
$notApproved = [];
$outOfStock  = [];

foreach ($localProducts as $p) {
    $rid = (string)$p['id'];  // retailer_id = products.id (como en el feed)

    if (!isset($metaByRetailerId[$rid])) {
        $notInMeta[] = $p;
        continue;
    }

    $mp     = $metaByRetailerId[$rid];
    $avail  = $mp['availability'] ?? '';
    $review = $mp['review_status'] ?? '';

    if ($review !== 'approved') {
        $notApproved[] = ['local' => $p, 'meta' => $mp];
        continue;
    }

    if ($avail !== 'in stock') {
        $outOfStock[] = ['local' => $p, 'meta' => $mp];
        continue;
    }

    $sendable[] = $rid;
}

echo "  ✅ Productos enviables por product_list: " . count($sendable);
if (!empty($sendable)) {
    echo " → retailer_ids: [" . implode(', ', $sendable) . "]";
}
echo "\n";

echo "  ❌ No existen en catálogo de Meta:       " . count($notInMeta) . "\n";
foreach ($notInMeta as $p) {
    echo "     → ID={$p['id']} {$p['name']}\n";
}

echo "  ⏳ Existen pero NO aprobados:            " . count($notApproved) . "\n";
foreach ($notApproved as $item) {
    echo "     → ID={$item['local']['id']} {$item['local']['name']} (review: {$item['meta']['review_status']})\n";
}

echo "  ⚠ Aprobados pero OUT OF STOCK en Meta:  " . count($outOfStock) . "\n";
foreach ($outOfStock as $item) {
    echo "     → ID={$item['local']['id']} {$item['local']['name']} (stock local: {$item['local']['stock']})\n";
}

echo "\n";

// ── 5. Verificar conexión catálogo ↔ WhatsApp Phone Number ───────────────────
echo "── 5. CATÁLOGO CONECTADO AL NÚMERO DE WHATSAPP ──────────────────\n";
$url2 = sprintf(
    'https://graph.facebook.com/%s/%s/product_catalogs',
    $apiVersion,
    $phoneNumId
);

$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $accessToken,
    ],
]);
$resp2  = curl_exec($ch2);
$http2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if ($http2 === 200) {
    $catalogs = json_decode($resp2, true)['data'] ?? [];
    if (empty($catalogs)) {
        echo "  ⚠ No hay catálogos conectados al número {$phoneNumId}\n";
    } else {
        foreach ($catalogs as $cat) {
            $catId   = $cat['id'] ?? '?';
            $catName = $cat['name'] ?? '(sin nombre)';
            $match   = ($catId === $catalogId) ? '✅ MATCH' : '❌ NO COINCIDE';
            echo "  → Catálogo: {$catName} (ID: {$catId}) {$match}\n";
        }
    }
} else {
    echo "  ⚠ No se pudo verificar (HTTP {$http2}): {$resp2}\n";
}

echo "\n";

// ── 6. Test de envío product_list (simulado) ─────────────────────────────────
echo "── 6. PAYLOAD DE PRUEBA ────────────────────────────────────────\n";
if (!empty($sendable)) {
    $testSections = [[
        'title'         => 'Test',
        'product_items' => array_map(
            fn($rid) => ['product_retailer_id' => $rid],
            array_slice($sendable, 0, 5)  // Máx 5 para el test
        ),
    ]];

    $testPayload = [
        'messaging_product' => 'whatsapp',
        'to'                => '(NÚMERO_TEST)',
        'type'              => 'interactive',
        'interactive'       => [
            'type'   => 'product_list',
            'header' => ['type' => 'text', 'text' => 'Test catálogo'],
            'body'   => ['text' => 'Productos de prueba'],
            'footer' => ['text' => 'TiendaMoroni'],
            'action' => [
                'catalog_id' => $catalogId,
                'sections'   => $testSections,
            ],
        ],
    ];

    echo "  " . json_encode($testPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo "  ⚠ No hay productos enviables. No se puede generar payload de test.\n";
    echo "  ACCIÓN REQUERIDA: Verificar en Commerce Manager que los productos\n";
    echo "  estén aprobados y en stock.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  FIN DEL DIAGNÓSTICO\n";
echo "═══════════════════════════════════════════════════════════════\n";
