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
            'pageTitle' => 'Iniciar sesión – ' . SITE_NAME,
            'metaDesc'  => 'Iniciá sesión en TiendaMoroni.',
            'canonical' => SITE_URL . '/auth/login',
            'error'     => Session::getFlash('error'),
            'redirect'  => sanitize(get('redirect', '')),
        ]);
    }

    public function loginPost(array $params = []): void
    {
        verifyCsrf();

        $email    = sanitize(post('email', ''));
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

        Session::create($user);

        redirect($redirect ?: '/mi-cuenta');
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function registerForm(array $params = []): void
    {
        if (Session::isLoggedIn()) {
            redirect('/mi-cuenta');
        }

        view('auth/register', [
            'pageTitle' => 'Crear cuenta – ' . SITE_NAME,
            'metaDesc'  => 'Creá tu cuenta en Tienda Moroni.',
            'canonical' => SITE_URL . '/auth/register',
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function registerPost(array $params = []): void
    {
        verifyCsrf();

        $name     = sanitize(post('name', ''));
        $email    = sanitize(post('email', ''));
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

        $user = UserModel::findById($userId);
        Session::create($user);
        redirect('/mi-cuenta');
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
            'client_id'     => ],
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

    // ── Private helpers ───────────────────────────────────────────────────────

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
