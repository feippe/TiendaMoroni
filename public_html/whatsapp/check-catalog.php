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

// ── Helper HTTP GET ───────────────────────────────────────────────────────────
function metaGet(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $response  = curl_exec($ch);
    $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    return [
        'http'     => $httpCode,
        'body'     => $response ?: '',
        'data'     => json_decode($response ?: '{}', true) ?: [],
        'curl_err' => $curlError,
    ];
}

// ── 3. Verificar permisos del token ───────────────────────────────────────────
echo "── 3. PERMISOS DEL TOKEN ───────────────────────────────────────\n";
$debugUrl = "https://graph.facebook.com/{$apiVersion}/debug_token?input_token={$accessToken}";
$debugRes = metaGet($debugUrl, $accessToken);
if ($debugRes['http'] === 200) {
    $tokenData = $debugRes['data']['data'] ?? [];
    $scopes    = $tokenData['scopes'] ?? [];
    $appId     = $tokenData['app_id'] ?? '?';
    $type      = $tokenData['type'] ?? '?';
    $isValid   = ($tokenData['is_valid'] ?? false) ? 'SÍ' : 'NO';
    echo "  Token válido: {$isValid}\n";
    echo "  Tipo: {$type}\n";
    echo "  App ID: {$appId}\n";
    echo "  Permisos: " . implode(', ', $scopes) . "\n";

    $required = ['whatsapp_business_management', 'whatsapp_business_messaging', 'catalog_management'];
    $missing  = array_diff($required, $scopes);
    if (!empty($missing)) {
        echo "  ⚠ FALTAN PERMISOS CRÍTICOS: " . implode(', ', $missing) . "\n";
        echo "    → Sin 'catalog_management' no se puede leer ni enviar catálogo\n";
    } else {
        echo "  ✅ Todos los permisos necesarios están presentes\n";
    }
} else {
    echo "  ⚠ No se pudo verificar token (HTTP {$debugRes['http']})\n";
    echo "  Respuesta: {$debugRes['body']}\n";
}
echo "\n";

// ── 4. Auto-descubrir el catálogo conectado al número de WhatsApp ─────────────
echo "── 4. CATÁLOGO CONECTADO AL NÚMERO ({$phoneNumId}) ──────────────\n";
$discoveredCatalogId = null;

// Método 1: whatsapp_commerce_settings (el endpoint correcto)
$comUrl = "https://graph.facebook.com/{$apiVersion}/{$phoneNumId}/whatsapp_commerce_settings";
echo "  Consultando: {$comUrl}\n";
$comRes = metaGet($comUrl, $accessToken);
if ($comRes['http'] === 200) {
    $comData = $comRes['data']['data'] ?? [];
    if (!empty($comData)) {
        foreach ($comData as $cs) {
            $cid = $cs['id'] ?? $cs['catalog_id'] ?? '?';
            echo "  ✅ Catálogo conectado: ID={$cid}\n";
            $discoveredCatalogId = (string)$cid;
        }
    } else {
        // Puede venir en formato plano
        $cid = $comRes['data']['id'] ?? $comRes['data']['catalog_id'] ?? null;
        if ($cid) {
            echo "  ✅ Catálogo conectado: ID={$cid}\n";
            $discoveredCatalogId = (string)$cid;
        } else {
            echo "  ⚠ No se encontró catálogo conectado.\n";
            echo "  Respuesta raw: " . json_encode($comRes['data'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} else {
    echo "  ⚠ Error HTTP {$comRes['http']}: " . $comRes['body'] . "\n";
}

// Método 2: Consultar el WABA ID y buscar catálogos desde ahí
echo "\n  Intentando descubrir WABA ID...\n";
$phoneInfoUrl = "https://graph.facebook.com/{$apiVersion}/{$phoneNumId}?fields=id,display_phone_number,name_status,quality_rating";
$phoneRes = metaGet($phoneInfoUrl, $accessToken);
if ($phoneRes['http'] === 200) {
    $phoneInfo = $phoneRes['data'];
    echo "  Phone number info: " . json_encode($phoneInfo, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "  ⚠ No se pudo obtener info del número: HTTP {$phoneRes['http']}\n";
}

// Método 3: Buscar catálogos del Business directamente
// Prueba el catalog_id configurado para ver si al menos existe
echo "\n  Verificando catalog_id configurado ({$catalogId})...\n";
$catInfoUrl = "https://graph.facebook.com/{$apiVersion}/{$catalogId}?fields=id,name,product_count,vertical";
$catRes = metaGet($catInfoUrl, $accessToken);
if ($catRes['http'] === 200) {
    $catInfo = $catRes['data'];
    echo "  ✅ Catálogo EXISTE: " . json_encode($catInfo, JSON_UNESCAPED_UNICODE) . "\n";
    $discoveredCatalogId = $discoveredCatalogId ?: $catalogId;
} else {
    echo "  ❌ Catálogo {$catalogId} NO accesible: HTTP {$catRes['http']}\n";
    echo "     " . $catRes['body'] . "\n";
    echo "\n  ⚠ PROBLEMA: El catalog_id en config.php NO es válido o el token no tiene permisos.\n";
}

// Si descubrimos un catálogo diferente al configurado
if ($discoveredCatalogId && $discoveredCatalogId !== $catalogId) {
    echo "\n  🔴 ¡¡CATALOG_ID INCORRECTO EN CONFIG!!\n";
    echo "     Config actual:    {$catalogId}\n";
    echo "     Descubierto real: {$discoveredCatalogId}\n";
    echo "     → ACTUALIZAR WA_CATALOG_ID en config.php a '{$discoveredCatalogId}'\n";
}

// Usar el mejor catalog_id disponible para el resto del diagnóstico
$effectiveCatalogId = $discoveredCatalogId ?: $catalogId;
echo "\n  Catalog ID efectivo para diagnóstico: {$effectiveCatalogId}\n";
echo "\n";

// ── 5. Consultar productos del catálogo en Meta ──────────────────────────────
echo "── 5. PRODUCTOS EN CATÁLOGO DE META ────────────────────────────\n";
$metaProducts = [];
$prodUrl = sprintf(
    'https://graph.facebook.com/%s/%s/products?fields=retailer_id,name,availability,review_status,image_url&limit=100',
    $apiVersion,
    $effectiveCatalogId
);

echo "  Consultando: {$prodUrl}\n\n";
$prodRes = metaGet($prodUrl, $accessToken);

if ($prodRes['curl_err']) {
    echo "  ⚠ cURL error: {$prodRes['curl_err']}\n\n";
} elseif ($prodRes['http'] !== 200) {
    echo "  ⚠ HTTP {$prodRes['http']}: {$prodRes['body']}\n\n";
    echo "  POSIBLES CAUSAS:\n";
    echo "    1. El catalog_id '{$effectiveCatalogId}' no es un ID de catálogo válido\n";
    echo "    2. El token no tiene el permiso 'catalog_management'\n";
    echo "    3. El catálogo pertenece a otro Business Manager\n";
    echo "    4. El ID es de otro tipo de objeto (WABA, página, etc.)\n\n";
    echo "  ACCIONES PARA VERIFICAR:\n";
    echo "    → Ir a https://business.facebook.com/commerce/\n";
    echo "    → Seleccionar tu catálogo → Configuración → copiar el 'ID del catálogo'\n";
    echo "    → Verificar que el System User tiene acceso al catálogo en Business Settings\n";
    echo "    → El permiso 'catalog_management' debe estar en el access token\n\n";
} else {
    $metaProducts = $prodRes['data']['data'] ?? [];

    echo "  Total productos en Meta: " . count($metaProducts) . "\n\n";

    if (empty($metaProducts)) {
        echo "  ⚠ ¡EL CATÁLOGO ESTÁ VACÍO!\n";
        echo "  Posibles causas:\n";
        echo "    - El feed XML no fue sincronizado todavía\n";
        echo "    - Los productos fueron rechazados por Meta\n";
        echo "    - El catálogo usa subida manual y no hay productos cargados\n\n";
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

// ── 6. Cruzar datos: productos locales vs Meta ───────────────────────────────
echo "── 6. CRUCE: BD LOCAL vs CATÁLOGO META ─────────────────────────\n";
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
    $rid = (string)$p['id'];

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

// ── 7. Test de envío product_list (simulado) ─────────────────────────────────
echo "── 7. PAYLOAD DE PRUEBA ────────────────────────────────────────\n";
if (!empty($sendable)) {
    $testSections = [[
        'title'         => 'Test',
        'product_items' => array_map(
            fn($rid) => ['product_retailer_id' => $rid],
            array_slice($sendable, 0, 5)
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
                'catalog_id' => $effectiveCatalogId,
                'sections'   => $testSections,
            ],
        ],
    ];

    echo "  " . json_encode($testPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo "  ⚠ No hay productos enviables. No se puede generar payload de test.\n\n";
    echo "  ══════════════════════════════════════════════════════════\n";
    echo "  GUÍA DE RESOLUCIÓN PASO A PASO:\n";
    echo "  ══════════════════════════════════════════════════════════\n\n";
    echo "  1. Abrir Commerce Manager:\n";
    echo "     https://business.facebook.com/commerce/catalogs/{$effectiveCatalogId}/products\n\n";
    echo "  2. Si no carga → el catalog_id es incorrecto. Ir a:\n";
    echo "     https://business.facebook.com/commerce/\n";
    echo "     Seleccionar tu catálogo → Configuración → copiar ID.\n";
    echo "     Actualizar WA_CATALOG_ID en config.php con ese ID.\n\n";
    echo "  3. Si carga pero sin productos → configurar Data Source:\n";
    echo "     Catálogo → Data Sources → Data Feed\n";
    echo "     URL: https://tiendamoroni.com/feed/productos.xml\n";
    echo "     (con HTTP Basic Auth si está configurado)\n\n";
    echo "  4. Verificar que el System User del token tenga acceso:\n";
    echo "     Business Settings → System Users → (tu user)\n";
    echo "     → Assets → Catalogs → Agregar tu catálogo\n\n";
    echo "  5. Verificar que el catálogo esté conectado al WhatsApp:\n";
    echo "     WhatsApp Manager → Phone number → Settings → Catalog\n";
    echo "     O vía API con este mismo script después de corregir.\n\n";
    echo "  6. Re-ejecutar este diagnóstico después de cada cambio.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  FIN DEL DIAGNÓSTICO\n";
echo "═══════════════════════════════════════════════════════════════\n";
