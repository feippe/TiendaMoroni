<?php
/**
 * TiendaMoroni – Application Configuration
 * Copy this file to config.php and fill in your credentials.
 */

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_PORT',     3306);
define('DB_NAME',     'tiendamoroni');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// ── Site ──────────────────────────────────────────────────────────────────────
define('SITE_NAME',   'TiendaMoroni');
// Auto-detect scheme + host + port from the incoming request (includes port on dev servers).
// Override with a fixed value for production, e.g.: define('SITE_URL', 'https://tiendamoroni.com');
define('SITE_URL', (function (): string {
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
    return 'http://localhost';
})());
define('SITE_EMAIL',  'hola@tiendamoroni.com');
define('ADMIN_EMAIL', 'admin@tiendamoroni.com');

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_COOKIE', 'tm_session');
define('SESSION_LIFETIME', 60 * 60 * 24 * 30);  // 30 days in seconds

// ── Google OAuth 2.0 ─────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  SITE_URL . '/auth/google/callback');

// ── Mail (SMTP or PHP mail) ───────────────────────────────────────────────────
define('MAIL_DRIVER', 'mail');   // 'mail' or 'smtp'
define('SMTP_HOST',   'smtp.mailtrap.io');
define('SMTP_PORT',   587);
define('SMTP_USER',   'YOUR_SMTP_USER');
define('SMTP_PASS',   'YOUR_SMTP_PASS');
define('SMTP_FROM',   'noreply@tiendamoroni.com');
define('SMTP_FROM_NAME', 'TiendaMoroni');

// ── File Uploads ──────────────────────────────────────────────────────────────
define('UPLOAD_PATH', dirname(__DIR__) . '/public/assets/uploads/');
define('UPLOAD_URL',  SITE_URL . '/assets/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);   // 5 MB

// ── Environment ───────────────────────────────────────────────────────────────
define('APP_ENV',   'development');   // 'development' | 'production'
define('APP_DEBUG',  true);
