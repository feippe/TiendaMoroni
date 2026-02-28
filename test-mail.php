<?php
/**
 * Mail test script — run from CLI:
 *   php test-mail.php [destinatario@email.com] [smtp|mail]
 *
 * Defaults: ADMIN_EMAIL, driver from config.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Core/Mailer.php';

// ── Args ──────────────────────────────────────────────────────────────────────
$to     = $argv[1] ?? ADMIN_EMAIL;
$driver = $argv[2] ?? MAIL_DRIVER;   // override driver without touching config

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: email inválido: $to\n";
    exit(1);
}

// ── Override MAIL_DRIVER for this run ─────────────────────────────────────────
if (!defined('_MAIL_DRIVER_OVERRIDE')) {
    define('_MAIL_DRIVER_OVERRIDE', $driver);
    // Patch the constant at runtime via runkit or just re-route in the test send below
}

// ── Config summary ────────────────────────────────────────────────────────────
echo "\n";
echo "══════════════════════════════════════════\n";
echo "  TiendaMoroni — Test de envío de email\n";
echo "══════════════════════════════════════════\n";
echo "  Destinatario : $to\n";
echo "  Driver       : $driver\n";
echo "  SMTP_HOST    : " . SMTP_HOST . "\n";
echo "  SMTP_PORT    : " . SMTP_PORT . "\n";
echo "  SMTP_USER    : " . SMTP_USER . "\n";
echo "  SMTP_FROM    : " . SMTP_FROM . "\n";
echo "──────────────────────────────────────────\n\n";

// ── Build test email ──────────────────────────────────────────────────────────
$subject = 'Test de email — ' . SITE_NAME . ' (' . date('H:i:s') . ')';

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8F6F2;font-family:Georgia,serif;">
  <div style="max-width:560px;margin:40px auto;background:#0F1E2E;border-radius:16px;padding:40px;color:#F8F6F2;text-align:center;">
    <div style="font-size:36px;margin-bottom:12px;">✉️</div>
    <h1 style="margin:0 0 12px;font-size:24px;color:#C6A75E;">Test de email</h1>
    <p style="margin:0 0 8px;color:#cbd0d8;font-size:15px;">
      Este email fue enviado desde el script de prueba de <strong>Tienda Moroni</strong>.
    </p>
    <p style="margin:0 0 24px;color:#8a9bb0;font-size:13px;">
      Fecha: <?= date('d/m/Y H:i:s') ?><br>
      Driver: <strong style="color:#C6A75E;">$driver</strong><br>
      Host SMTP: <strong style="color:#C6A75E;"><?= SMTP_HOST ?>:<?= SMTP_PORT ?></strong>
    </p>
    <div style="border-top:1px solid #1e3a5a;padding-top:20px;color:#8a9bb0;font-size:12px;">
      Si ves este mensaje, el envío de emails está funcionando correctamente.
    </div>
  </div>
</body>
</html>
HTML;

$htmlBody = str_replace('<?= date(\'d/m/Y H:i:s\') ?>', date('d/m/Y H:i:s'), $htmlBody);

$textBody = "Test de email — Tienda Moroni\n\n"
          . "Este email fue enviado desde el script de prueba.\n"
          . "Fecha: " . date('d/m/Y H:i:s') . "\n"
          . "Driver: $driver\n"
          . "Host SMTP: " . SMTP_HOST . ":" . SMTP_PORT . "\n\n"
          . "Si ves este mensaje, el envío de emails está funcionando.";

// ── Send ──────────────────────────────────────────────────────────────────────
echo "Enviando...\n";
$start = microtime(true);

try {
    if ($driver === 'smtp') {
        $result = sendSmtpDirect($to, 'Test Admin', $subject, $htmlBody, $textBody);
    } else {
        $result = \TiendaMoroni\Core\Mailer::send($to, 'Test Admin', $subject, $htmlBody, $textBody);
    }

    $elapsed = round((microtime(true) - $start) * 1000);

    if ($result) {
        echo "✅  Email enviado correctamente ({$elapsed}ms)\n";
        echo "    Revisá la bandeja de $to\n\n";
    } else {
        echo "❌  Mailer::send() retornó false ({$elapsed}ms)\n";
        echo "    Revisá los logs de PHP o las credenciales SMTP.\n\n";
    }
} catch (\Throwable $e) {
    $elapsed = round((microtime(true) - $start) * 1000);
    echo "❌  Excepción ({$elapsed}ms): " . $e->getMessage() . "\n";
    echo "    " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

// ── Direct SMTP helper (bypasses MAIL_DRIVER constant) ───────────────────────
function sendSmtpDirect(string $to, string $toName, string $subject, string $html, string $text): bool
{
    $boundary = md5(uniqid((string)time(), true));

    echo "  Conectando a ssl://" . SMTP_HOST . ":" . SMTP_PORT . " ...\n";

    $socket = @fsockopen('ssl://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$socket) {
        throw new \RuntimeException("No se pudo conectar: $errstr ($errno)");
    }

    $read = function () use ($socket): string {
        $buf = '';
        while ($line = fgets($socket, 1024)) {
            $buf .= $line;
            echo "  ← " . trim($line) . "\n";
            if (substr($line, 3, 1) === ' ') break;
        }
        return $buf;
    };

    $write = function (string $cmd) use ($socket): void {
        $display = str_starts_with($cmd, 'AUTH') ? 'AUTH LOGIN [credenciales ocultas]' : trim($cmd);
        echo "  → $display\n";
        fwrite($socket, $cmd);
    };

    $read(); // greeting
    $ehlo = defined('SMTP_FROM') ? substr(strrchr(SMTP_FROM, '@'), 1) : 'tiendamoroni.com';
    $write("EHLO " . $ehlo . "\r\n"); $read();
    $write("AUTH LOGIN\r\n"); $read();
    $write(base64_encode(SMTP_USER) . "\r\n"); $read();
    $write(base64_encode(SMTP_PASS) . "\r\n"); $read();
    $write("MAIL FROM:<" . SMTP_FROM . ">\r\n"); $read();
    $write("RCPT TO:<$to>\r\n"); $read();
    $write("DATA\r\n"); $read();

    $msg  = "From: =?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?= <" . SMTP_FROM . ">\r\n";
    $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$to>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $msg .= "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n\r\n";
    $msg .= "--$boundary--\r\n";
    $msg .= ".\r\n";

    fwrite($socket, $msg);
    $response = $read();

    $write("QUIT\r\n"); $read();
    fclose($socket);

    return str_starts_with(trim($response), '250');
}
