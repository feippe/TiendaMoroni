<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

/**
 * Lightweight mailer – supports PHP mail() and basic SMTP via sockets.
 */
class Mailer
{
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): bool {
        if (MAIL_DRIVER === 'smtp') {
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
        $from     = SMTP_FROM;
        $fromName = SMTP_FROM_NAME;

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
            $useStartTls = (SMTP_PORT === 587);
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                ],
            ]);

            // Port 465 → direct SSL; port 587 → plain then STARTTLS
            $prefix = $useStartTls ? '' : 'ssl://';
            $socket = stream_socket_client(
                $prefix . SMTP_HOST . ':' . SMTP_PORT,
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $ctx
            );

            if (!$socket) {
                throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
            }

            $ehlo = defined('SMTP_FROM') ? substr(strrchr(SMTP_FROM, '@'), 1) : 'tiendamoroni.com';

            self::smtpExpect($socket, 220);
            self::smtpCmd($socket, 'EHLO ' . $ehlo, 250);

            if ($useStartTls) {
                self::smtpCmd($socket, 'STARTTLS', 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                // Re-send EHLO after TLS upgrade (required by RFC)
                self::smtpCmd($socket, 'EHLO ' . $ehlo, 250);
            }

            self::smtpCmd($socket, 'AUTH LOGIN', 334);
            self::smtpCmd($socket, base64_encode(SMTP_USER), 334);
            self::smtpCmd($socket, base64_encode(SMTP_PASS), 235);
            self::smtpCmd($socket, 'MAIL FROM:<' . SMTP_FROM . '>', 250);
            self::smtpCmd($socket, 'RCPT TO:<' . $to . '>', 250);
            self::smtpCmd($socket, 'DATA', 354);

            $boundary  = md5(uniqid((string) time(), true));
            $plain     = $textBody ?: strip_tags($htmlBody);
            $date      = date('r');
            $plainB64  = chunk_split(base64_encode($plain), 76, "\r\n");
            $htmlB64   = chunk_split(base64_encode($htmlBody), 76, "\r\n");

            $msg  = "Date: $date\r\n";
            $msg .= "From: =?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?= <" . SMTP_FROM . ">\r\n";
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
