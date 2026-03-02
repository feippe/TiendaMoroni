<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Database as DB;

class SitemapController
{
    public function index(array $params = []): void
    {
        $urls = [];

        // Static pages
        $statics = [
            ['loc' => SITE_URL . '/',                 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => SITE_URL . '/productos',         'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => SITE_URL . '/publicar-gratis',   'priority' => '0.6', 'changefreq' => 'monthly'],
        ];
        foreach ($statics as $s) {
            $urls[] = $s;
        }

        // Categories (no timestamp columns in schema)
        $categories = DB::fetchAll(
            "SELECT slug FROM categories ORDER BY sort_order ASC"
        );
        foreach ($categories as $cat) {
            $urls[] = [
                'loc'        => SITE_URL . '/categoria/' . $cat['slug'],
                'priority'   => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        // Active products
        $products = DB::fetchAll(
            "SELECT slug, updated_at FROM products WHERE status = 'active' ORDER BY updated_at DESC"
        );
        foreach ($products as $p) {
            $urls[] = [
                'loc'        => SITE_URL . '/producto/' . $p['slug'],
                'lastmod'    => $this->formatDate($p['updated_at']),
                'priority'   => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        // Vendors
        $vendors = DB::fetchAll(
            "SELECT slug, created_at FROM vendors ORDER BY created_at DESC"
        );
        foreach ($vendors as $v) {
            $urls[] = [
                'loc'        => SITE_URL . '/vendedor/' . $v['slug'],
                'lastmod'    => $this->formatDate($v['created_at']),
                'priority'   => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                echo '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            if (!empty($url['changefreq'])) {
                echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            }
            if (!empty($url['priority'])) {
                echo '    <priority>' . $url['priority'] . "</priority>\n";
            }
            echo "  </url>\n";
        }

        echo '</urlset>';
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return date('Y-m-d');
        }
        try {
            return (new \DateTime($date))->format('Y-m-d');
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }
}
