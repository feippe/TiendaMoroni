<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Database as DB;

class FeedController
{
    public function productos(array $params = []): void
    {
        // Clean any buffered output (notices, warnings) before sending the feed
        while (ob_get_level()) {
            ob_end_clean();
        }

        $products = DB::fetchAll(
            "SELECT id, name, description, stock, price, slug, main_image_url
             FROM products
             WHERE status = 'active'
             ORDER BY id ASC"
        );

        header('Content-Type: text/csv; charset=UTF-8');
        // No Content-Disposition: Meta's crawler needs to parse the response inline

        $out = fopen('php://output', 'w');

        // UTF-8 BOM — required by Meta Commerce Manager
        fwrite($out, "\xEF\xBB\xBF");

        // Header row (exact column order required by Meta)
        fputcsv($out, ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand'], ',', '"', '\\');

        foreach ($products as $p) {
            $availability = ((int)$p['stock'] > 0) ? 'in stock' : 'out of stock';

            $price = number_format((float)$p['price'], 2, '.', '') . ' UYU';

            $link = SITE_URL . '/producto/' . $p['slug'];

            $imageLink = $p['main_image_url'] ?? '';

            // Strip HTML tags from description — Meta requires plain text
            $description = strip_tags($p['description'] ?? '');

            fputcsv($out, [
                (string)$p['id'],
                htmlspecialchars((string)$p['name'],   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($description,          ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $availability,
                'new',
                $price,
                $link,
                (string)$imageLink,
                'Tienda Moroni',
            ], ',', '"', '\\');
        }

        fclose($out);
    }
}
