<?php
/**
 * Logger – Registro de mensajes y errores del bot de WhatsApp.
 *
 * Guarda en la tabla `wa_message_log` todos los mensajes intercambiados
 * y escribe errores en archivos de log mensuales dentro de /logs/.
 */

declare(strict_types=1);

class Logger
{
    private PDO    $pdo;
    private string $logDir;

    public function __construct(PDO $pdo, string $logDir)
    {
        $this->pdo    = $pdo;
        $this->logDir = rtrim($logDir, '/');
    }

    /**
     * Registra un mensaje entrante (del cliente hacia el bot).
     */
    public function logIncoming(string $phone, string $type, array $payload): void
    {
        $this->insertLog($phone, 'incoming', $type, $payload);
    }

    /**
     * Registra un mensaje saliente (del bot hacia el cliente).
     */
    public function logOutgoing(string $phone, string $type, array $payload): void
    {
        $this->insertLog($phone, 'outgoing', $type, $payload);
    }

    /**
     * Registra un error. Escribe en archivo porque la BD puede no estar disponible.
     *
     * @param string $message  Descripción del error.
     * @param array  $context  Datos adicionales para el debugging.
     */
    public function error(string $message, array $context = []): void
    {
        $entry = sprintf(
            "[%s] ERROR: %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        $this->writeToFile($entry, 'errors.log');
    }

    /**
     * Registra información general de actividad del bot.
     */
    public function info(string $message): void
    {
        $entry = sprintf("[%s] INFO: %s\n", date('Y-m-d H:i:s'), $message);
        $this->writeToFile($entry, 'bot.log');
    }

    // ── Métodos privados ──────────────────────────────────────────────────────

    /**
     * Inserta un registro en la tabla wa_message_log.
     */
    private function insertLog(string $phone, string $direction, string $type, array $payload): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO wa_message_log (phone_number, direction, message_type, payload)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $phone,
                $direction,
                $type,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (PDOException $e) {
            // Si falla la BD, al menos loguear en archivo
            $this->error('No se pudo insertar en wa_message_log: ' . $e->getMessage());
        }
    }

    /**
     * Escribe una entrada en un archivo de log mensual (YYYY-MM-nombre.log).
     * Crea el directorio si no existe.
     */
    private function writeToFile(string $entry, string $filename): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0750, true);
        }
        $path = $this->logDir . '/' . date('Y-m') . '-' . $filename;
        file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
    }
}
