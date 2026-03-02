<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Database as DB;

class FeedController
{
    public function productosXml(array $params = []): void
    {
        // Clean any buffered output before sending the feed
        while (ob_get_level()) {
            ob_end_clean();
        }

        $products = DB::fetchAll(
            "SELECT id, name, description, stock, price, slug, main_image_url
             FROM products
             WHERE status = 'active'
             ORDER BY id ASC"
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
            echo '      <g:brand>Tienda Moroni</g:brand>' . "\n";
            echo '    </item>' . "\n";
        }

        echo '  </channel>' . "\n";
        echo '</rss>';
    }
}
