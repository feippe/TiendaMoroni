<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Database as DB;

class FeedController
{
    public function productosXml(array $params = []): void
    {
        // ── HTTP Basic Auth para Meta Commerce Manager ─────────────────────────
        // Definir FEED_META_USER y FEED_META_PASS en config/config.php.
        // Configurar los mismos valores en Commerce Manager al registrar el feed.
        if (defined('FEED_META_USER') && defined('FEED_META_PASS')) {
            $user = $_SERVER['PHP_AUTH_USER'] ?? '';
            $pass = $_SERVER['PHP_AUTH_PW']   ?? '';
            if (
                !hash_equals(FEED_META_USER, $user) ||
                !hash_equals(FEED_META_PASS, $pass)
            ) {
                header('WWW-Authenticate: Basic realm="Product Feed"');
                http_response_code(401);
                exit('Acceso denegado');
            }
        }

        // Clean any buffered output before sending the feed
        while (ob_get_level()) {
            ob_end_clean();
        }

        $products = DB::fetchAll(
            "SELECT p.id, p.name, p.description, p.stock, p.price, p.slug, p.main_image_url,
                    c.name AS category_name,
                    v.business_name AS vendor_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN vendors v ON v.id = p.vendor_id
             WHERE p.status = 'active'
             ORDER BY p.id ASC"
        );

        header('Content-Type: application/xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        echo '  <channel>' . "\n";
        echo '    <title>' . htmlspecialchars(SITE_NAME, ENT_XML1, 'UTF-8') . '</title>' . "\n";
        echo '    <link>' . htmlspecialchars(SITE_URL, ENT_XML1, 'UTF-8') . '</link>' . "\n";
        echo '    <description>Catálogo de productos ' . htmlspecialchars(SITE_NAME, ENT_XML1, 'UTF-8') . '</description>' . "\n";

        foreach ($products as $p) {
            $availability = ((int)$p['stock'] > 0) ? 'in stock' : 'out of stock';
            $price        = number_format((float)$p['price'], 2, '.', '') . ' UYU';
            $link         = SITE_URL . '/producto/' . $p['slug'];
            $imageLink    = $p['main_image_url'] ?? '';
            $description  = strip_tags($p['description'] ?? '');

            echo '    <item>' . "\n";
            echo '      <g:id>'          . htmlspecialchars((string)$p['id'], ENT_XML1, 'UTF-8') . '</g:id>' . "\n";
            echo '      <g:title><![CDATA[' . $p['name'] . ']]></g:title>' . "\n";
            echo '      <g:description><![CDATA[' . $description . ']]></g:description>' . "\n";
            echo '      <g:availability>' . $availability . '</g:availability>' . "\n";
            echo '      <g:condition>new</g:condition>' . "\n";
            echo '      <g:price>'       . htmlspecialchars($price, ENT_XML1, 'UTF-8') . '</g:price>' . "\n";
            echo '      <g:link>'        . htmlspecialchars($link, ENT_XML1, 'UTF-8') . '</g:link>' . "\n";
            echo '      <g:image_link>'  . htmlspecialchars($imageLink, ENT_XML1, 'UTF-8') . '</g:image_link>' . "\n";
            echo '      <g:brand>'       . htmlspecialchars($p['vendor_name'] ?? 'Tienda Moroni', ENT_XML1, 'UTF-8') . '</g:brand>' . "\n";
            if (!empty($p['category_name'])) {
                echo '      <g:product_type>' . htmlspecialchars($p['category_name'], ENT_XML1, 'UTF-8') . '</g:product_type>' . "\n";
            }
            echo '    </item>' . "\n";
        }

        echo '  </channel>' . "\n";
        echo '</rss>';
    }
}
