<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\UserModel;
use TiendaMoroni\Models\OrderModel;

class AuthController
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            redirect('/mi-cuenta');
        }

        view('auth/login', [
            'pageTitle'  => 'Iniciar sesión – ' . SITE_NAME,
            'metaDesc'   => 'Iniciá sesión en TiendaMoroni.',
            'canonical'  => SITE_URL . '/auth/login',
            'error'      => Session::getFlash('error'),
            'errorHtml'  => Session::getFlash('error_html'),
            'redirect'   => sanitize(get('redirect', '')),
        ]);
    }

    public function loginPost(array $params = []): void
    {
        verifyCsrf();

        $email    = strtolower(trim(sanitize(post('email', ''))));
        $password = post('password', '');
        $redirect = sanitize(post('redirect', ''));

        if (!$email || !$password) {
            Session::flash('error', 'Por favor completá todos los campos.');
            redirect('/auth/login' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
        }

        $user = UserModel::findByEmailWithPassword($email);

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            Session::flash('error', 'Email o contraseña incorrectos.');
            redirect('/auth/login' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
        }

        // Block unverified email accounts (Google OAuth users are always verified)
        if (($user['auth_provider'] ?? 'own') === 'own' && empty($user['email_verified'])) {
            Session::flash('error_html', 'Tu cuenta aún no está verificada. Revisá tu email o <a href="/auth/resend-verification" class="underline font-medium" style="color:#C6A75E">solicitá un nuevo link</a>.');
            redirect('/auth/login' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
        }

        Session::create($user);

        redirect($redirect ?: '/mi-cuenta');
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function registerForm(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            redirect('/mi-cuenta');
        }

        $isPending = (get('status') === 'pending');

        view('auth/register', [
            'pageTitle'    => 'Crear cuenta – ' . SITE_NAME,
            'metaDesc'     => 'Creá tu cuenta en Tienda Moroni.',
            'canonical'    => SITE_URL . '/auth/register',
            'error'        => Session::getFlash('error'),
            'pendingEmail' => Session::getFlash('pending_email'),
            'noindex'      => $isPending,
        ]);
    }

    public function registerPost(array $params = []): void
    {
        verifyCsrf();

        $name     = sanitize(post('name', ''));
        $email    = strtolower(trim(sanitize(post('email', ''))));
        $password = post('password', '');
        $confirm  = post('password_confirm', '');

        if (!$name || !$email || !$password) {
            Session::flash('error', 'Por favor completá todos los campos.');
            redirect('/auth/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'El email no es válido.');
            redirect('/auth/register');
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirect('/auth/register');
        }

        if ($password !== $confirm) {
            Session::flash('error', 'Las contraseñas no coinciden.');
            redirect('/auth/register');
        }

        if (UserModel::findByEmail($email)) {
            Session::flash('error', 'Ya existe una cuenta con ese email.');
            redirect('/auth/register');
        }

        $userId = UserModel::create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'auth_provider' => 'own',
        ]);

        // ── Email verification ────────────────────────────────────────────────
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        \TiendaMoroni\Core\Database::query(
            'INSERT INTO email_verifications (user_id, email, token_hash, expires_at)
             VALUES (?, ?, ?, ?)',
            [$userId, $email, $tokenHash, $expiresAt]
        );

        $this->sendVerificationEmail($email, $name, $token, $expiresAt);

        Session::flash('success', '¡Registro exitoso! Revisá tu email para activar tu cuenta. El link expira en 24 horas.');
        Session::flash('pending_email', $email);
        redirect('/auth/register?status=pending');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(array $params = []): void
    {
        Session::destroy();
        redirect('/');
    }

    // ── Google OAuth ──────────────────────────────────────────────────────────

    public function googleRedirect(array $params = []): void
    {
        $state = bin2hex(random_bytes(16));

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['oauth_state'] = $state;

        $queryString = http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);

        redirectAbsolute('https://accounts.google.com/o/oauth2/v2/auth?' . $queryString);
    }

    public function googleCallback(array $params = []): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $state = get('state', '');
        $code  = get('code', '');

        if (!$state || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
            Session::flash('error', 'error de seguridad OAuth. Intentá de nuevo.');
            redirect('/auth/login');
        }

        if (!$code) {
            Session::flash('error', 'Google canceló el inicio de sesión.');
            redirect('/auth/login');
        }

        // Exchange code for token
        $tokenResponse = $this->googlePost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($tokenResponse['access_token'])) {
            Session::flash('error', 'No se pudo obtener el token de Google.');
            redirect('/auth/login');
        }

        // Get user info
        $googleUser = $this->googleGet(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            $tokenResponse['access_token']
        );

        if (empty($googleUser['sub'])) {
            Session::flash('error', 'No se pudo obtener la información del usuario de Google.');
            redirect('/auth/login');
        }

        // Find or create user
        $user = UserModel::findByGoogleId($googleUser['sub']);

        if (!$user) {
            $user = UserModel::findByEmail($googleUser['email']);
            if ($user) {
                // Existing user – link Google ID
                UserModel::update((int) $user['id'], ['avatar_url' => $googleUser['picture'] ?? null]);
            } else {
                // New user
                $userId = UserModel::create([
                    'name'          => $googleUser['name'],
                    'email'         => $googleUser['email'],
                    'avatar_url'    => $googleUser['picture'] ?? null,
                    'auth_provider' => 'google',
                    'google_id'     => $googleUser['sub'],
                ]);
                $user = UserModel::findById($userId);
            }
        }

        Session::create($user);
        redirect('/mi-cuenta');
    }

    // ── Account ───────────────────────────────────────────────────────────────

    public function account(array $params = []): void
    {
        Session::requireAuth('/mi-cuenta');
        $user   = Session::user();
        $orders = OrderModel::byUser((int) $user['user_id']);

        view('auth/account', [
            'user'      => $user,
            'orders'    => $orders,
            'pageTitle' => 'Mi cuenta – ' . SITE_NAME,
            'metaDesc'  => 'Gestioná tu cuenta en TiendaMoroni.',
            'canonical' => SITE_URL . '/mi-cuenta',
        ]);
    }

    // ── Email Verification ────────────────────────────────────────────────────

    public function verifyEmail(array $params = []): void
    {
        $token = sanitize(get('token', ''));

        if (!$token) {
            Session::flash('error', 'Link de verificación inválido.');
            redirect('/auth/login');
        }

        $tokenHash = hash('sha256', $token);
        $row = \TiendaMoroni\Core\Database::fetchOne(
            'SELECT ev.*, u.id AS uid, u.name, u.email AS user_email, u.role, u.active, u.avatar_url, u.auth_provider
             FROM email_verifications ev
             JOIN users u ON u.id = ev.user_id
             WHERE ev.token_hash = ? AND ev.used = 0 AND ev.expires_at > NOW()',
            [$tokenHash]
        );

        if (!$row) {
            Session::flash('error', 'Este link ya expiró o no es válido. Podés solicitar uno nuevo desde el login.');
            redirect('/auth/login');
        }

        // Mark user as verified
        \TiendaMoroni\Core\Database::query(
            'UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = ?',
            [$row['uid']]
        );

        // Invalidate token
        \TiendaMoroni\Core\Database::query(
            'UPDATE email_verifications SET used = 1 WHERE token_hash = ?',
            [$tokenHash]
        );

        // Auto-login
        $user = UserModel::findById((int)$row['uid']);
        if ($user) {
            Session::create($user);
        }

        Session::flash('success', '¡Tu cuenta está activa! Bienvenido/a a Tienda Moroni.');
        redirect('/');
    }

    public function resendVerification(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            $u = Session::user();
            if (!empty($u['email_verified'])) {
                redirect('/');
            }
        }

        view('auth/resend-verification', [
            'pageTitle' => 'Reenviar verificación — ' . SITE_NAME,
            'metaDesc'  => '',
            'noindex'   => true,
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function resendVerificationSubmit(array $params = []): void
    {
        verifyCsrf();

        $email = strtolower(trim(sanitize(post('email', ''))));

        // Rate limit: max 3 per IP per hour via password_reset_attempts
        $rawIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip    = trim(explode(',', $rawIp)[0]);

        $cnt = \TiendaMoroni\Core\Database::fetchOne(
            'SELECT COUNT(*) AS n FROM password_reset_attempts
             WHERE ip_address = ? AND created_at > NOW() - INTERVAL 1 HOUR',
            [$ip]
        );
        \TiendaMoroni\Core\Database::query(
            'INSERT INTO password_reset_attempts (ip_address) VALUES (?)',
            [$ip]
        );

        Session::flash('success', 'Si ese email tiene una cuenta pendiente de verificación, recibirás un nuevo link en breve.');

        if ((int)($cnt['n'] ?? 0) >= 3) {
            redirect('/auth/resend-verification');
        }

        $user = UserModel::findByEmailWithPassword($email);

        if (!$user || !empty($user['email_verified'])) {
            redirect('/auth/resend-verification');
        }

        // Invalidate old tokens
        \TiendaMoroni\Core\Database::query(
            'UPDATE email_verifications SET used = 1 WHERE user_id = ? AND used = 0',
            [$user['id']]
        );

        // New token
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        \TiendaMoroni\Core\Database::query(
            'INSERT INTO email_verifications (user_id, email, token_hash, expires_at)
             VALUES (?, ?, ?, ?)',
            [$user['id'], $email, $tokenHash, $expiresAt]
        );

        $this->sendVerificationEmail($email, $user['name'] ?? '', $token, $expiresAt);

        redirect('/auth/resend-verification');
    }

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function forgotPassword(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            redirect('/');
        }

        view('auth/forgot-password', [
            'pageTitle' => 'Recuperar contraseña – ' . SITE_NAME,
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function forgotPasswordSubmit(array $params = []): void
    {
        verifyCsrf();

        $email = strtolower(trim(sanitize(post('email', ''))));

        // ── Rate limiting ─────────────────────────────────────────────────────
        $rawIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip    = trim(explode(',', $rawIp)[0]);

        $attempts = \TiendaMoroni\Core\Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM password_reset_attempts
             WHERE ip_address = ? AND created_at > NOW() - INTERVAL 15 MINUTE',
            [$ip]
        );

        \TiendaMoroni\Core\Database::query(
            'INSERT INTO password_reset_attempts (ip_address) VALUES (?)',
            [$ip]
        );

        // Neutral flash – never reveal rate limiting
        Session::flash('success', 'Si ese email está registrado, recibirás un enlace en breve.');

        if ((int)($attempts['cnt'] ?? 0) >= 3) {
            redirect('/auth/forgot-password');
        }

        // ── Email lookup ──────────────────────────────────────────────────────
        $user = UserModel::findByEmail($email);

        if (!$user) {
            redirect('/auth/forgot-password');
        }

        // ── Invalidate previous tokens ────────────────────────────────────────
        \TiendaMoroni\Core\Database::query(
            'UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0',
            [$email]
        );

        // ── Generate new token ────────────────────────────────────────────────
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        \TiendaMoroni\Core\Database::query(
            'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)',
            [$email, $tokenHash, $expiresAt]
        );

        // ── Send email ────────────────────────────────────────────────────────
        $resetUrl   = SITE_URL . '/auth/reset-password?token=' . $token;
        $userName   = htmlspecialchars($user['name'] ?? '', ENT_QUOTES);
        $siteName   = SITE_NAME;

        $htmlBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;background:#F8F6F2;font-family:\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F6F2;padding:40px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">'
            . '<tr><td style="background:#0F1E2E;padding:28px 40px;text-align:center;">'
            . '<img src="' . APP_URL . '/assets/img/Logo-white.svg" alt="' . $siteName . '" width="220" style="display:block;margin:0 auto;max-width:100%;height:auto;">'
            . '</td></tr>'
            . '<tr><td style="padding:40px 40px 32px;">'
            . '<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0F1E2E;">Reseteo de contraseña</h1>'
            . '<div style="width:40px;height:2px;background:#C6A75E;margin:0 0 24px;"></div>'
            . '<p style="margin:0 0 16px;font-size:15px;color:#444;line-height:1.6;">Hola ' . $userName . ',</p>'
            . '<p style="margin:0 0 28px;font-size:15px;color:#555;line-height:1.6;">'
            . 'Recibimos una solicitud para resetear la contraseña de tu cuenta en ' . $siteName . '. '
            . 'Hacé clic en el botón para crear una nueva contraseña. '
            . '<strong>Este link expira en 60 minutos.</strong></p>'
            . '<table width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><td align="center" style="padding:8px 0 32px;">'
            . '<a href="' . $resetUrl . '" style="display:inline-block;background:#C6A75E;color:#0F1E2E;text-decoration:none;font-size:14px;font-weight:700;padding:14px 36px;border-radius:100px;">Crear nueva contraseña</a>'
            . '</td></tr></table>'
            . '<p style="margin:0 0 8px;font-size:13px;color:#888;line-height:1.6;">Si no solicitaste este cambio, ignorá este email. Tu contraseña no será modificada.</p>'
            . '<p style="margin:0;font-size:12px;color:#aaa;">Por seguridad, este link es de un solo uso.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#F8F6F2;padding:20px 40px;text-align:center;border-top:1px solid #ede9e3;">'
            . '<p style="margin:0;font-size:11px;color:#aaa;">© ' . $siteName . ' · Uruguay</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $textBody = "Reseteo de contraseña – {$siteName}\n\n"
            . "Hola {$userName},\n\n"
            . "Recibimos una solicitud para resetear la contraseña de tu cuenta en {$siteName}.\n"
            . "Hacé clic en el siguiente link para crear una nueva contraseña (expira en 60 minutos):\n\n"
            . "{$resetUrl}\n\n"
            . "Si no solicitaste este cambio, ignorá este email. Tu contraseña no será modificada.\n"
            . "Por seguridad, este link es de un solo uso.\n\n"
            . "– {$siteName}";

        \TiendaMoroni\Core\Mailer::send(
            $email,
            $user['name'] ?? '',
            "Reseteo de contraseña — {$siteName}",
            $htmlBody,
            $textBody
        );

        redirect('/auth/forgot-password');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetPassword(array $params = []): void
    {
        $token = sanitize(get('token', ''));

        if (!$token) {
            Session::flash('error', 'Este link no es válido o ya expiró. Solicitá uno nuevo.');
            redirect('/auth/forgot-password');
        }

        $tokenHash = hash('sha256', $token);
        $row = \TiendaMoroni\Core\Database::fetchOne(
            'SELECT id FROM password_resets
             WHERE token_hash = ? AND used = 0 AND expires_at > NOW()',
            [$tokenHash]
        );

        if (!$row) {
            Session::flash('error', 'Este link no es válido o ya expiró. Solicitá uno nuevo.');
            redirect('/auth/forgot-password');
        }

        view('auth/reset-password', [
            'pageTitle' => 'Nueva contraseña – ' . SITE_NAME,
            'token'     => $token,
            'errors'    => [],
            'error'     => null,
        ]);
    }

    public function resetPasswordSubmit(array $params = []): void
    {
        verifyCsrf();

        $token           = sanitize(post('token', ''));
        $password        = post('password', '');
        $passwordConfirm = post('password_confirm', '');

        // Re-verify token
        $tokenHash = $token ? hash('sha256', $token) : '';
        $row = $tokenHash ? \TiendaMoroni\Core\Database::fetchOne(
            'SELECT email FROM password_resets
             WHERE token_hash = ? AND used = 0 AND expires_at > NOW()',
            [$tokenHash]
        ) : null;

        if (!$row) {
            Session::flash('error', 'Este link no es válido o ya expiró. Solicitá uno nuevo.');
            redirect('/auth/forgot-password');
        }

        // Validate
        $errors = [];
        if (strlen($password) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden.';
        }

        if ($errors) {
            view('auth/reset-password', [
                'pageTitle' => 'Nueva contraseña – ' . SITE_NAME,
                'token'     => $token,
                'errors'    => $errors,
                'error'     => null,
            ]);
            return;
        }

        $email        = $row['email'];
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Update password
        \TiendaMoroni\Core\Database::query(
            'UPDATE users SET password_hash = ? WHERE email = ?',
            [$passwordHash, $email]
        );

        // Invalidate token (single use)
        \TiendaMoroni\Core\Database::query(
            'UPDATE password_resets SET used = 1 WHERE token_hash = ?',
            [$tokenHash]
        );

        // Invalidate all active sessions for this user
        $user = UserModel::findByEmail($email);
        if ($user) {
            \TiendaMoroni\Core\Database::query(
                'DELETE FROM sessions WHERE user_id = ?',
                [$user['id']]
            );
        }

        // Send confirmation email
        $siteName = SITE_NAME;
        $userName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES);

        $htmlConfirm = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;background:#F8F6F2;font-family:\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F6F2;padding:40px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">'
            . '<tr><td style="background:#0F1E2E;padding:28px 40px;text-align:center;">'
            . '<img src="' . APP_URL . '/assets/img/Logo-white.svg" alt="' . $siteName . '" width="220" style="display:block;margin:0 auto;max-width:100%;height:auto;">'
            . '</td></tr>'
            . '<tr><td style="padding:40px 40px 32px;">'
            . '<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0F1E2E;">Contraseña actualizada</h1>'
            . '<div style="width:40px;height:2px;background:#C6A75E;margin:0 0 24px;"></div>'
            . '<p style="margin:0 0 16px;font-size:15px;color:#444;line-height:1.6;">Hola ' . $userName . ',</p>'
            . '<p style="margin:0 0 28px;font-size:15px;color:#555;line-height:1.6;">'
            . 'Tu contraseña en ' . $siteName . ' fue actualizada exitosamente. '
            . 'Si no fuiste vos, <strong>contactanos de inmediato</strong>.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#F8F6F2;padding:20px 40px;text-align:center;border-top:1px solid #ede9e3;">'
            . '<p style="margin:0;font-size:11px;color:#aaa;">© ' . $siteName . ' · Uruguay</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $textConfirm = "Contraseña actualizada – {$siteName}\n\n"
            . "Hola {$userName},\n\n"
            . "Tu contraseña en {$siteName} fue actualizada exitosamente.\n"
            . "Si no fuiste vos, contactanos de inmediato.\n\n"
            . "– {$siteName}";

        \TiendaMoroni\Core\Mailer::send(
            $email,
            $user['name'] ?? '',
            "Tu contraseña fue actualizada — {$siteName}",
            $htmlConfirm,
            $textConfirm
        );

        Session::flash('success', '¡Contraseña actualizada! Ya podés iniciar sesión.');
        redirect('/auth/login');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sendVerificationEmail(string $email, string $name, string $token, string $expiresAt): void
    {
        $verifyUrl  = SITE_URL . '/auth/verify-email?token=' . $token;
        $siteName   = SITE_NAME;
        $userName   = htmlspecialchars($name, ENT_QUOTES);
        $expiryFmt  = date('d/m/Y \a \l\a\s H:i', strtotime($expiresAt));

        $htmlBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;background:#F8F6F2;font-family:\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F6F2;padding:40px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">'
            . '<tr><td style="background:#0F1E2E;padding:28px 40px;text-align:center;">'
            . '<img src="' . APP_URL . '/assets/img/Logo-white.svg" alt="' . $siteName . '" width="220" style="display:block;margin:0 auto;max-width:100%;height:auto;">'
            . '</td></tr>'
            . '<tr><td style="padding:40px 40px 32px;">'
            . '<h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#0F1E2E;">¡Ya casi estás!</h1>'
            . '<div style="width:40px;height:2px;background:#C6A75E;margin:0 0 24px;"></div>'
            . '<p style="margin:0 0 16px;font-size:15px;color:#444;line-height:1.6;">Hola ' . $userName . ',</p>'
            . '<p style="margin:0 0 28px;font-size:15px;color:#555;line-height:1.6;">'
            . 'Gracias por registrarte en ' . $siteName . '. '
            . 'Hacé clic en el botón para activar tu cuenta. '
            . '<strong>Este link es válido por 24 horas.</strong></p>'
            . '<table width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><td align="center" style="padding:8px 0 32px;">'
            . '<a href="' . $verifyUrl . '" style="display:inline-block;background:#C6A75E;color:#0F1E2E;text-decoration:none;font-size:14px;font-weight:700;padding:14px 36px;border-radius:100px;">Activar mi cuenta</a>'
            . '</td></tr></table>'
            . '<p style="margin:0 0 8px;font-size:13px;color:#888;line-height:1.6;">Si no creaste una cuenta en ' . $siteName . ', ignorá este email.</p>'
            . '<p style="margin:0;font-size:12px;color:#aaa;">Este link expira el ' . $expiryFmt . '.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#F8F6F2;padding:20px 40px;text-align:center;border-top:1px solid #ede9e3;">'
            . '<p style="margin:0;font-size:11px;color:#aaa;">© ' . $siteName . ' · Uruguay</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $textBody = "¡Ya casi estás! – {$siteName}\n\n"
            . "Hola {$userName},\n\n"
            . "Gracias por registrarte en {$siteName}. Activá tu cuenta con el siguiente link:\n\n"
            . "{$verifyUrl}\n\n"
            . "Este link expira el {$expiryFmt}.\n"
            . "Si no creaste una cuenta en {$siteName}, ignorá este email.\n\n"
            . "– {$siteName}";

        \TiendaMoroni\Core\Mailer::send(
            $email,
            $name,
            "Activá tu cuenta en {$siteName}",
            $htmlBody,
            $textBody
        );
    }

    private function googlePost(string $url, array $data): array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return $response ? (json_decode($response, true) ?? []) : [];
    }

    private function googleGet(string $url, string $accessToken): array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer $accessToken\r\n",
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return $response ? (json_decode($response, true) ?? []) : [];
    }
}
