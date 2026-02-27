<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\SettingModel;

class SettingsController
{
    /** Allowlist of keys that can be toggled via the API endpoint. */
    private const ALLOWED_KEYS = ['maintenance_mode'];

    /**
     * GET /admin/configuracion
     */
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $maintenanceMode = SettingModel::get('maintenance_mode') === '1';

        view('admin/settings', [
            'pageTitle'       => 'Configuración — Admin ' . SITE_NAME,
            'maintenanceMode' => $maintenanceMode,
        ]);
    }

    /**
     * POST /admin/configuracion/toggle
     * Accepts JSON: { key: string, value: '0'|'1' }
     * Returns JSON: { success: bool, key: string, value: string }
     */
    public function toggle(array $params = []): void
    {
        Session::requireAdmin();

        // Validate Content-Type.
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($contentType, 'application/json')) {
            http_response_code(415);
            jsonResponse(['success' => false, 'error' => 'Content-Type must be application/json']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body)) {
            http_response_code(400);
            jsonResponse(['success' => false, 'error' => 'Invalid JSON body']);
            return;
        }

        $key   = trim((string) ($body['key']   ?? ''));
        $value = trim((string) ($body['value'] ?? ''));

        // Key allowlist check.
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            http_response_code(422);
            jsonResponse(['success' => false, 'error' => 'Invalid setting key']);
            return;
        }

        // Value must be '0' or '1' for boolean settings.
        if (!in_array($value, ['0', '1'], true)) {
            http_response_code(422);
            jsonResponse(['success' => false, 'error' => 'Value must be 0 or 1']);
            return;
        }

        try {
            SettingModel::set($key, $value);
        } catch (\Throwable $e) {
            http_response_code(500);
            jsonResponse(['success' => false, 'error' => 'Database error']);
            return;
        }

        jsonResponse(['success' => true, 'key' => $key, 'value' => $value]);
    }
}
