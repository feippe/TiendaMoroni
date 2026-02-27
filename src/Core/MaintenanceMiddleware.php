<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

use TiendaMoroni\Models\SettingModel;

class MaintenanceMiddleware
{
    /**
     * Paths always allowed through regardless of maintenance mode.
     * Admin routes are also allowed but handled separately by role check.
     */
    private const ALLOWED_PATHS = [
        '/admin',
        '/auth/login',
        '/auth/logout',
        '/auth/google',
        '/auth/google/callback',
    ];

    /**
     * Run the middleware. Call this before the router dispatches.
     * If maintenance mode is on and the visitor is not an admin, render
     * the maintenance page (503) and exit.
     */
    public static function handle(): void
    {
        try {
            $isOn = SettingModel::get('maintenance_mode') === '1';
        } catch (\Throwable) {
            // If DB is unavailable, never block the site.
            return;
        }

        if (!$isOn) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        // Always allow admin paths and auth paths.
        foreach (self::ALLOWED_PATHS as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return;
            }
        }

        // Also allow any /admin/* subpath.
        if (str_starts_with($path, '/admin')) {
            return;
        }

        // If an admin is logged in, let them through.
        $user = Session::user();
        if ($user && ($user['role'] ?? '') === 'admin') {
            return;
        }

        // Render maintenance page and stop.
        http_response_code(503);
        header('Retry-After: 3600');

        $maintenanceView = BASE_PATH . '/src/Views/maintenance.php';
        if (file_exists($maintenanceView)) {
            require $maintenanceView;
        } else {
            echo '<!DOCTYPE html><html><head><title>Mantenimiento</title></head>'
               . '<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;">'
               . '<p>Sitio en mantenimiento. Volvé pronto.</p></body></html>';
        }
        exit;
    }
}
