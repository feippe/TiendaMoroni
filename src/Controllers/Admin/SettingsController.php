<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Mailer;
use TiendaMoroni\Models\SettingModel;

class SettingsController
{
    /** Allowlist of keys that can be toggled via the API endpoint. */
    private const ALLOWED_KEYS = ['maintenance_mode'];

    /** SMTP setting keys stored in site_settings. */
    private const SMTP_KEYS = ['smtp_driver', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_from_name'];

    /**
     * Helper: read a DB setting, fall back to a PHP constant.
     */
    private function smtpCfg(string $key, string $constant): string
    {
        $v = SettingModel::get($key);
        return ($v !== null && $v !== '') ? $v : (defined($constant) ? (string) constant($constant) : '');
    }

    /**
     * GET /admin/configuracion
     */
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $maintenanceMode = SettingModel::get('maintenance_mode') === '1';

        $smtp = [
            'driver'    => $this->smtpCfg('smtp_driver',    'MAIL_DRIVER'),
            'host'      => $this->smtpCfg('smtp_host',      'SMTP_HOST'),
            'port'      => $this->smtpCfg('smtp_port',      'SMTP_PORT'),
            'user'      => $this->smtpCfg('smtp_user',      'SMTP_USER'),
            'pass'      => $this->smtpCfg('smtp_pass',      'SMTP_PASS'),
            'from'      => $this->smtpCfg('smtp_from',      'SMTP_FROM'),
            'from_name' => $this->smtpCfg('smtp_from_name', 'SMTP_FROM_NAME'),
        ];

        view('admin/settings', [
            'pageTitle'       => 'Configuración — Admin ' . SITE_NAME,
            'maintenanceMode' => $maintenanceMode,
            'smtp'            => $smtp,
        ]);
    }

    /**
     * POST /admin/configuracion/smtp
     * Saves SMTP configuration to site_settings.
     */
    public function saveSmtp(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $fields = [
            'smtp_driver'    => trim((string) ($_POST['driver']    ?? '')),
            'smtp_host'      => trim((string) ($_POST['host']      ?? '')),
            'smtp_port'      => trim((string) ($_POST['port']      ?? '')),
            'smtp_user'      => trim((string) ($_POST['user']      ?? '')),
            'smtp_from'      => trim((string) ($_POST['from']      ?? '')),
            'smtp_from_name' => trim((string) ($_POST['from_name'] ?? '')),
        ];

        // Password: only update if the user submitted a non-empty value
        $newPass = trim((string) ($_POST['pass'] ?? ''));
        if ($newPass !== '') {
            $fields['smtp_pass'] = $newPass;
        }

        // Basic validation
        if (!in_array($fields['smtp_driver'], ['smtp', 'mail'], true)) {
            jsonResponse(['success' => false, 'error' => 'Driver inválido.']);
            return;
        }

        foreach ($fields as $key => $value) {
            SettingModel::set($key, $value);
        }

        jsonResponse(['success' => true]);
    }

    /**
     * POST /admin/configuracion/smtp/test
     * Sends a test e-mail to the current admin using the current (possibly unsaved) settings.
     */
    public function testMail(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $adminEmail = $_SESSION['user_email'] ?? ADMIN_EMAIL;
        $adminName  = $_SESSION['user_name']  ?? 'Administrador';

        $html = '<div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px">'
              . '<h2 style="color:#0F1E2E">✓ Conexión SMTP funcionando</h2>'
              . '<p style="color:#555">Este es un correo de prueba enviado desde el panel de administración de <strong>' . SITE_NAME . '</strong>.</p>'
              . '<p style="color:#888;font-size:13px">Fecha: ' . date('d/m/Y H:i:s') . '</p>'
              . '</div>';

        $ok = Mailer::send($adminEmail, $adminName, 'Prueba de correo — ' . SITE_NAME, $html);

        if ($ok) {
            jsonResponse(['success' => true, 'message' => 'Email enviado a ' . $adminEmail]);
        } else {
            jsonResponse(['success' => false, 'error' => 'No se pudo enviar el email. Revisá los datos SMTP.'], 500);
        }
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
