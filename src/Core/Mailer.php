<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

/**
 * Lightweight mailer – supports PHP mail() and basic SMTP via sockets.
 */
class Mailer
{
    /**
     * Read a setting from DB (via SettingModel) if available,
     * otherwise fall back to the PHP constant.
     */
    private static function cfg(string $constant, string $settingKey): string
    {
        try {
            $v = \TiendaMoroni\Models\SettingModel::get($settingKey);
            if ($v !== null && $v !== '') return $v;
        } catch (\Throwable) {}
        return defined($constant) ? (string) constant($constant) : '';
    }

    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): bool {
        $driver = self::cfg('MAIL_DRIVER', 'smtp_driver');
        if ($driver === 'smtp') {
            return self::sendSmtp($to, $toName, $subject, $htmlBody, $textBody);
        }

        return self::sendNative($to, $toName, $subject, $htmlBody, $textBody);
    }

    // ── PHP mail() ────────────────────────────────────────────────────────────

    private static function sendNative(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        $boundary = md5(uniqid((string) time(), true));
        $from     = self::cfg('SMTP_FROM',      'smtp_from');
        $fromName = self::cfg('SMTP_FROM_NAME', 'smtp_from_name');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: TiendaMoroni/1.0\r\n";

        $plain = $textBody ?: strip_tags($htmlBody);

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $plain . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= "--$boundary--";

        $fullTo = $toName ? "=?UTF-8?B?" . base64_encode($toName) . "?= <$to>" : $to;

        return mail($fullTo, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }

    // ── SMTP via socket ───────────────────────────────────────────────────────

    private static function sendSmtp(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        try {
            $smtpHost = self::cfg('SMTP_HOST', 'smtp_host');
            $smtpPort = (int) self::cfg('SMTP_PORT', 'smtp_port');
            $smtpUser = self::cfg('SMTP_USER', 'smtp_user');
            $smtpPass = self::cfg('SMTP_PASS', 'smtp_pass');
            $smtpFrom = self::cfg('SMTP_FROM', 'smtp_from');
            $smtpFromName = self::cfg('SMTP_FROM_NAME', 'smtp_from_name');

            $useStartTls = ($smtpPort === 587);
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                ],
            ]);

            // Port 465 → direct SSL; port 587 → plain then STARTTLS
            $prefix = $useStartTls ? '' : 'ssl://';
            $socket = stream_socket_client(
                $prefix . $smtpHost . ':' . $smtpPort,
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $ctx
            );

            if (!$socket) {
                throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
            }

            $ehlo = $smtpFrom ? substr(strrchr($smtpFrom, '@'), 1) : 'tiendamoroni.com';

            self::smtpExpect($socket, 220);
            self::smtpCmd($socket, 'EHLO ' . $ehlo, 250);

            if ($useStartTls) {
                self::smtpCmd($socket, 'STARTTLS', 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                // Re-send EHLO after TLS upgrade (required by RFC)
                self::smtpCmd($socket, 'EHLO ' . $ehlo, 250);
            }

            self::smtpCmd($socket, 'AUTH LOGIN', 334);
            self::smtpCmd($socket, base64_encode($smtpUser), 334);
            self::smtpCmd($socket, base64_encode($smtpPass), 235);
            self::smtpCmd($socket, 'MAIL FROM:<' . $smtpFrom . '>', 250);
            self::smtpCmd($socket, 'RCPT TO:<' . $to . '>', 250);
            self::smtpCmd($socket, 'DATA', 354);

            $boundary  = md5(uniqid((string) time(), true));
            $plain     = $textBody ?: strip_tags($htmlBody);
            $date      = date('r');
            $plainB64  = chunk_split(base64_encode($plain), 76, "\r\n");
            $htmlB64   = chunk_split(base64_encode($htmlBody), 76, "\r\n");

            $msg  = "Date: $date\r\n";
            $msg .= "From: =?UTF-8?B?" . base64_encode($smtpFromName) . "?= <" . $smtpFrom . ">\r\n";
            $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$to>\r\n";
            $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
            $msg .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n$plainB64\r\n";
            $msg .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n$htmlB64\r\n";
            $msg .= "--$boundary--\r\n.\r\n";

            fwrite($socket, $msg);
            self::smtpExpect($socket, 250);
            self::smtpCmd($socket, 'QUIT', 221);

            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            if (APP_DEBUG) error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    private static function smtpCmd($socket, string $cmd, int $expectedCode): string
    {
        fwrite($socket, $cmd . "\r\n");
        return self::smtpExpect($socket, $expectedCode);
    }

    private static function smtpExpect($socket, int $code): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if ((int) substr($response, 0, 3) !== $code) {
            throw new \RuntimeException("SMTP unexpected response: $response");
        }
        return $response;
    }
}
