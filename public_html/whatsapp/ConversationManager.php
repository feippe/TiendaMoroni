<?php
/**
 * ConversationManager – Gestiona el estado de cada conversación en la BD.
 *
 * Cada cliente se identifica por su número de teléfono (formato internacional
 * sin el signo +, ej: 598XXXXXXXXX). Su estado se persiste en `wa_conversations`
 * y se resetea a WELCOME si supera el tiempo de inactividad configurado.
 *
 * Estados posibles del bot:
 *   WELCOME          – Estado inicial. Muestra mensaje de bienvenida.
 *   BROWSE_MENU      – Menú principal: categoría / vendedor / búsqueda.
 *   SELECT_CATEGORY  – Lista paginada de categorías disponibles.
 *   SELECT_SELLER    – Lista paginada de vendedores disponibles.
 *   SEARCH_PROMPT    – Esperando texto libre del cliente para buscar.
 *   SHOW_PRODUCTS    – Mostrando productos según el filtro activo.
 *   PRODUCT_INTEREST – Después de que el cliente envía su carrito (order message).
 */

declare(strict_types=1);

class ConversationManager
{
    private PDO $pdo;
    private int $timeout; // segundos hasta expirar la conversación

    public function __construct(PDO $pdo, int $timeout = 1800)
    {
        $this->pdo     = $pdo;
        $this->timeout = $timeout;
    }

    /**
     * Obtiene la conversación existente o crea una nueva en estado WELCOME.
     *
     * Siempre agrega la clave 'context' con el JSON decodificado de context_data.
     *
     * @return array  Fila de wa_conversations + clave 'context' (array).
     */
    public function getOrCreate(string $phone): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM wa_conversations WHERE phone_number = ?'
        );
        $stmt->execute([$phone]);
        $conv = $stmt->fetch();

        if (!$conv) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO wa_conversations (phone_number, current_state, context_data, last_interaction, created_at)
                 VALUES (?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$phone, 'WELCOME', json_encode(new stdClass())]);

            $conv = [
                'phone_number'     => $phone,
                'current_state'    => 'WELCOME',
                'context_data'     => '{}',
                'last_interaction' => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ];
        }

        // Decodificar context_data para uso inmediato en los handlers
        $conv['context'] = json_decode($conv['context_data'] ?? '{}', true) ?: [];

        return $conv;
    }

    /**
     * Actualiza el estado y el contexto de la conversación.
     * Usa INSERT ... ON DUPLICATE KEY para evitar race conditions.
     *
     * @param string $phone    Número del cliente.
     * @param string $state    Nuevo estado de la máquina de estados.
     * @param array  $context  Datos de contexto a persistir (filtros, página, etc.).
     */
    public function setState(string $phone, string $state, array $context = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wa_conversations (phone_number, current_state, context_data, last_interaction, created_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
               current_state    = VALUES(current_state),
               context_data     = VALUES(context_data),
               last_interaction = NOW()'
        );
        $stmt->execute([
            $phone,
            $state,
            json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Actualiza solo el timestamp de última interacción (sin cambiar estado).
     */
    public function touch(string $phone): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wa_conversations SET last_interaction = NOW() WHERE phone_number = ?'
        );
        $stmt->execute([$phone]);
    }

    /**
     * Verifica si la conversación superó el tiempo máximo de inactividad.
     *
     * Protege contra fechas inválidas de MySQL ('0000-00-00' u otros valores
     * anómalos) que harían que strtotime devuelva un timestamp muy antiguo y
     * dispararían un falso timeout inmediato.
     *
     * @param array $conversation  Fila devuelta por getOrCreate().
     */
    public function isTimedOut(array $conversation): bool
    {
        $raw = $conversation['last_interaction'] ?? '';

        // Ignorar fechas claramente inválidas (MySQL puede retornar '0000-00-00 00:00:00')
        if (empty($raw) || str_starts_with((string)$raw, '0000')) {
            return false;
        }

        $lastTs = strtotime((string)$raw);

        // Si la fecha no se pudo parsear, asumir que NO expiró (no resetear por error)
        if ($lastTs === false) {
            return false;
        }

        $elapsed = time() - $lastTs;

        // Si el resultado es negativo (reloj del servidor desfasado), no expira
        if ($elapsed < 0) {
            return false;
        }

        return $elapsed > $this->timeout;
    }

    /**
     * Resetea la conversación al estado WELCOME con contexto vacío.
     */
    public function reset(string $phone): void
    {
        $this->setState($phone, 'WELCOME', []);
    }
}
