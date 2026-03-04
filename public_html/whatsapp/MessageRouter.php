<?php
/**
 * MessageRouter – Lógica central de la máquina de estados del bot IVR.
 *
 * ─── Diagrama de transiciones ────────────────────────────────────────────────
 *
 *  WELCOME
 *    → button_reply "btn_ver_web"        → envía CTA URL → permanece en WELCOME
 *    → button_reply "btn_ver_whatsapp"   → BROWSE_MENU
 *    → cualquier otro mensaje            → muestra bienvenida (permanece en WELCOME)
 *
 *  BROWSE_MENU  (espera list_reply)
 *    → list_reply "menu_categorias"      → SELECT_CATEGORY (pág. 1)
 *    → list_reply "menu_vendedores"      → SELECT_SELLER   (pág. 1)
 *    → list_reply "menu_buscar"          → SEARCH_PROMPT
 *    → list_reply "menu_inicio"          → WELCOME
 *    → cualquier otro input              → reenvía el menú (NO resetea)
 *
 *  SELECT_CATEGORY  (espera list_reply)
 *    → list_reply "cat_{id}"             → SHOW_PRODUCTS (filter: category)
 *    → list_reply "nav_mas_cats"         → SELECT_CATEGORY (pág. siguiente)
 *    → list_reply "nav_volver"           → BROWSE_MENU
 *    → cualquier otro input              → reenvía la lista
 *
 *  SELECT_SELLER  (espera list_reply)
 *    → list_reply "sel_{id}"             → SHOW_PRODUCTS (filter: vendor)
 *    → list_reply "nav_mas_sels"         → SELECT_SELLER (pág. siguiente)
 *    → list_reply "nav_volver"           → BROWSE_MENU
 *    → cualquier otro input              → reenvía la lista
 *
 *  SEARCH_PROMPT  (espera text)
 *    → text libre                        → SHOW_PRODUCTS (filter: search) | sin resultados → BROWSE_MENU
 *    → cualquier otro tipo               → re-solicita texto
 *
 *  SHOW_PRODUCTS  (espera button_reply)
 *    → button_reply "nav_menu"           → BROWSE_MENU
 *    → button_reply "nav_buscar"         → SEARCH_PROMPT
 *    → button_reply "nav_mas"            → SHOW_PRODUCTS (pág. siguiente)
 *    → order message                     → PRODUCT_INTEREST
 *    → cualquier otro input              → reenvía botones de navegación
 *
 *  PRODUCT_INTEREST  (espera button_reply)
 *    → button_reply "pi_seguir"          → BROWSE_MENU
 *    → button_reply "menu_inicio" / otro → WELCOME
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class MessageRouter
{
    private WhatsAppAPI         $api;
    private ConversationManager $conv;
    private ProductService      $products;
    private OrderService        $orders;
    private Logger              $logger;
    private array               $config;

    public function __construct(
        WhatsAppAPI         $api,
        ConversationManager $conv,
        ProductService      $products,
        OrderService        $orders,
        Logger              $logger,
        array               $config
    ) {
        $this->api      = $api;
        $this->conv     = $conv;
        $this->products = $products;
        $this->orders   = $orders;
        $this->logger   = $logger;
        $this->config   = $config;
    }

    // ── Punto de entrada ──────────────────────────────────────────────────────

    /**
     * Procesa un mensaje entrante y responde según el estado de la conversación.
     *
     * @param string $phone    Número del cliente (formato internacional, sin +).
     * @param array  $message  Objeto mensaje decodificado del payload de Meta.
     */
    public function route(string $phone, array $message): void
    {
        // 1. Obtener o crear conversación
        $conversation = $this->conv->getOrCreate($phone);

        // 2. Registrar mensaje entrante en BD
        $this->logger->logIncoming($phone, $message['type'] ?? 'unknown', $message);

        // 3. Logging detallado para diagnóstico
        $this->logIn($phone, $conversation['current_state'], $message);

        // 4. Si la conversación expiró → resetear a WELCOME
        if ($this->conv->isTimedOut($conversation)) {
            $this->logTrans($phone, $conversation['current_state'], 'WELCOME', 'timeout');
            $this->conv->reset($phone);
            $conversation = $this->conv->getOrCreate($phone);
        }

        // 5. Los order messages se procesan independientemente del estado
        if (($message['type'] ?? '') === 'order') {
            $this->handleOrderMessage($phone, $message, $conversation);
            return;
        }

        // 6. Despachar según el estado actual
        $state = $conversation['current_state'];
        switch ($state) {
            case 'WELCOME':
                $this->handleWelcome($phone, $message, $conversation);
                break;
            case 'BROWSE_MENU':
                $this->handleBrowseMenu($phone, $message, $conversation);
                break;
            case 'SELECT_CATEGORY':
                $this->handleSelectCategory($phone, $message, $conversation);
                break;
            case 'SELECT_SELLER':
                $this->handleSelectSeller($phone, $message, $conversation);
                break;
            case 'SEARCH_PROMPT':
                $this->handleSearchPrompt($phone, $message, $conversation);
                break;
            case 'SHOW_PRODUCTS':
                $this->handleShowProducts($phone, $message, $conversation);
                break;
            case 'PRODUCT_INTEREST':
                $this->handleProductInterest($phone, $message, $conversation);
                break;
            default:
                // Estado desconocido → resetear
                $this->logTrans($phone, $state, 'WELCOME', 'estado-desconocido');
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
        }
    }

    // ── Manejadores de estado ─────────────────────────────────────────────────

    /**
     * WELCOME: espera button_reply con los dos botones de inicio.
     *
     * Tipo esperado: button_reply
     * IDs esperados: "btn_ver_web" | "btn_ver_whatsapp"
     */
    private function handleWelcome(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);

        if ($btnId === 'btn_ver_web') {
            $this->logTrans($phone, 'WELCOME', 'WELCOME', 'cta-web');
            $this->api->sendCtaUrl(
                $phone,
                'Visitá nuestra tienda online para ver todos los productos con fotos en alta calidad.',
                'Ir a TiendaMoroni.com',
                $this->config['app']['base_url']
            );
            // Volver a WELCOME después de redirigir
            $this->conv->setState($phone, 'WELCOME');
            return;
        }

        if ($btnId === 'btn_ver_whatsapp') {
            $this->logTrans($phone, 'WELCOME', 'BROWSE_MENU', 'btn_ver_whatsapp');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // Cualquier otro mensaje (primer contacto, saludo, lista inesperada, etc.)
        // → mostrar bienvenida y asegurar que el estado sea WELCOME
        $this->logger->info(
            "WELCOME [{$phone}] input inesperado tipo=" . ($message['type'] ?? 'unknown')
            . " btnId=" . ($btnId ?? 'null') . " → mostrando bienvenida"
        );
        $this->conv->setState($phone, 'WELCOME');
        $this->sendWelcomeMessage($phone);
    }

    /**
     * BROWSE_MENU: espera una list_reply del menú de navegación.
     *
     * Tipo esperado: list_reply
     * IDs esperados: "menu_categorias" | "menu_vendedores" | "menu_buscar" | "menu_inicio"
     *
     * FIX: el `default` ya NO resetea al inicio — reenvía el menú para evitar
     * que cualquier input inesperado (texto libre, ID desconocido) destruya el flujo.
     */
    private function handleBrowseMenu(string $phone, array $message, array $conversation): void
    {
        // BROWSE_MENU usa lista interactiva → esperar list_reply.
        // También acepta button_reply por si otros estados derivan aquí con botones.
        $replyId = $this->getListReplyId($message) ?? $this->getButtonReplyId($message);

        $this->logger->info(
            "BROWSE_MENU [{$phone}] replyId=" . ($replyId ?? 'null')
            . " msgType=" . ($message['type'] ?? 'unknown')
            . " interactiveType=" . ($message['interactive']['type'] ?? 'none')
        );

        switch ($replyId) {
            case 'menu_categorias':
                $this->logTrans($phone, 'BROWSE_MENU', 'SELECT_CATEGORY', $replyId);
                $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => 1]);
                $this->sendCategoryList($phone, 1);
                break;

            case 'menu_vendedores':
                $this->logTrans($phone, 'BROWSE_MENU', 'SELECT_SELLER', $replyId);
                $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => 1]);
                $this->sendSellerList($phone, 1);
                break;

            case 'menu_buscar':
            case 'search_retry':
                $this->logTrans($phone, 'BROWSE_MENU', 'SEARCH_PROMPT', $replyId);
                $this->conv->setState($phone, 'SEARCH_PROMPT');
                $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
                break;

            case 'menu_inicio':
                // El usuario eligió explícitamente volver al inicio
                $this->logTrans($phone, 'BROWSE_MENU', 'WELCOME', 'menu_inicio');
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
                break;

            default:
                // Input no reconocido (texto libre, ID desconocido, notificación de lectura, etc.)
                // NO resetear a WELCOME: reenviar el menú para mantener al usuario en contexto.
                $this->logger->info(
                    "BROWSE_MENU [{$phone}] replyId inesperado='"
                    . ($replyId ?? 'null') . "' → reenviando menú"
                );
                $this->sendBrowseMenu($phone);
                break;
        }
    }

    /**
     * SELECT_CATEGORY: espera list_reply con el ID de la categoría elegida.
     *
     * Tipo esperado: list_reply
     * IDs esperados: "cat_{id}" | "nav_mas_cats" | "nav_volver"
     */
    private function handleSelectCategory(string $phone, array $message, array $conversation): void
    {
        $replyId = $this->getListReplyId($message);
        $ctx     = $conversation['context'];

        $this->logger->info(
            "SELECT_CATEGORY [{$phone}] replyId=" . ($replyId ?? 'null')
            . " cat_page=" . ($ctx['cat_page'] ?? 1)
        );

        if ($replyId === 'nav_volver') {
            $this->logTrans($phone, 'SELECT_CATEGORY', 'BROWSE_MENU', 'nav_volver');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($replyId === 'nav_mas_cats') {
            $nextPage = ((int)($ctx['cat_page'] ?? 1)) + 1;
            $this->logTrans($phone, 'SELECT_CATEGORY', 'SELECT_CATEGORY', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => $nextPage]);
            $this->sendCategoryList($phone, $nextPage);
            return;
        }

        if ($replyId !== null && str_starts_with($replyId, 'cat_')) {
            $categoryId = (int)substr($replyId, 4);
            $newCtx = ['filter_type' => 'category', 'filter_id' => $categoryId, 'page' => 1];
            $this->logTrans($phone, 'SELECT_CATEGORY', 'SHOW_PRODUCTS', "cat_id={$categoryId}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input inesperado (texto libre, button_reply, etc.) → reenviar la misma lista
        $this->logger->info(
            "SELECT_CATEGORY [{$phone}] replyId inesperado='"
            . ($replyId ?? 'null') . "' → reenviando lista"
        );
        $this->sendCategoryList($phone, (int)($ctx['cat_page'] ?? 1));
    }

    /**
     * SELECT_SELLER: espera list_reply con el ID del vendedor elegido.
     *
     * Tipo esperado: list_reply
     * IDs esperados: "sel_{id}" | "nav_mas_sels" | "nav_volver"
     */
    private function handleSelectSeller(string $phone, array $message, array $conversation): void
    {
        $replyId = $this->getListReplyId($message);
        $ctx     = $conversation['context'];

        $this->logger->info(
            "SELECT_SELLER [{$phone}] replyId=" . ($replyId ?? 'null')
            . " sel_page=" . ($ctx['sel_page'] ?? 1)
        );

        if ($replyId === 'nav_volver') {
            $this->logTrans($phone, 'SELECT_SELLER', 'BROWSE_MENU', 'nav_volver');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($replyId === 'nav_mas_sels') {
            $nextPage = ((int)($ctx['sel_page'] ?? 1)) + 1;
            $this->logTrans($phone, 'SELECT_SELLER', 'SELECT_SELLER', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => $nextPage]);
            $this->sendSellerList($phone, $nextPage);
            return;
        }

        if ($replyId !== null && str_starts_with($replyId, 'sel_')) {
            $vendorId = (int)substr($replyId, 4);
            $newCtx = ['filter_type' => 'vendor', 'filter_id' => $vendorId, 'page' => 1];
            $this->logTrans($phone, 'SELECT_SELLER', 'SHOW_PRODUCTS', "vendor_id={$vendorId}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input inesperado → reenviar la misma lista
        $this->logger->info(
            "SELECT_SELLER [{$phone}] replyId inesperado='"
            . ($replyId ?? 'null') . "' → reenviando lista"
        );
        $this->sendSellerList($phone, (int)($ctx['sel_page'] ?? 1));
    }

    /**
     * SEARCH_PROMPT: el siguiente mensaje de texto libre es el término de búsqueda.
     *
     * Tipo esperado: text
     */
    private function handleSearchPrompt(string $phone, array $message, array $conversation): void
    {
        $text = $this->getMessageText($message);

        $this->logger->info(
            "SEARCH_PROMPT [{$phone}] msgType=" . ($message['type'] ?? 'unknown')
            . " hasText=" . ($text !== null ? 'si' : 'no')
        );

        // Si no es texto libre, re-solicitar al usuario
        if ($text === null) {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        $term = mb_substr(trim($text), 0, 100);

        if ($term === '') {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        $this->logger->info("SEARCH_PROMPT [{$phone}] buscando término='{$term}'");

        $count = $this->products->countSearchProducts($term);

        if ($count === 0) {
            $this->logTrans($phone, 'SEARCH_PROMPT', 'BROWSE_MENU', "sin-resultados:{$term}");
            $this->api->sendReplyButtons(
                $phone,
                "No encontré productos con \"*{$term}*\". Intentá con otra palabra.",
                [
                    ['id' => 'menu_buscar', 'title' => 'Buscar de nuevo'],
                    ['id' => 'menu_inicio', 'title' => 'Volver al menú'],
                ]
            );
            // Ir a BROWSE_MENU para que los botones de arriba funcionen
            $this->conv->setState($phone, 'BROWSE_MENU');
            return;
        }

        $newCtx = ['filter_type' => 'search', 'search_term' => $term, 'page' => 1];
        $this->logTrans($phone, 'SEARCH_PROMPT', 'SHOW_PRODUCTS', "resultados={$count}");
        $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
        $this->sendProductsDisplay($phone, $newCtx);
    }

    /**
     * SHOW_PRODUCTS: navega por páginas de productos o regresa al menú.
     *
     * Tipo esperado: button_reply
     * IDs esperados: "nav_menu" | "nav_buscar" | "nav_mas"
     * También maneja: order message (carrito de WhatsApp)
     */
    private function handleShowProducts(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);
        $ctx   = $conversation['context'];

        $this->logger->info(
            "SHOW_PRODUCTS [{$phone}] btnId=" . ($btnId ?? 'null')
            . " filter=" . ($ctx['filter_type'] ?? 'none')
            . " page=" . ($ctx['page'] ?? 1)
        );

        if ($btnId === 'nav_menu' || $btnId === 'menu_inicio') {
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'BROWSE_MENU', $btnId);
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($btnId === 'nav_buscar') {
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'SEARCH_PROMPT', 'nav_buscar');
            $this->conv->setState($phone, 'SEARCH_PROMPT');
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        if ($btnId === 'nav_mas') {
            $nextPage = ((int)($ctx['page'] ?? 1)) + 1;
            $newCtx   = array_merge($ctx, ['page' => $nextPage]);
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'SHOW_PRODUCTS', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input no reconocido → reenviar botones de navegación sin cambiar estado
        $this->logger->info(
            "SHOW_PRODUCTS [{$phone}] btnId inesperado='"
            . ($btnId ?? 'null') . "' → reenviando botones"
        );
        $this->api->sendReplyButtons(
            $phone,
            '¿Qué querés hacer?',
            [
                ['id' => 'nav_menu',   'title' => 'Volver al menú'],
                ['id' => 'nav_buscar', 'title' => 'Nueva búsqueda'],
            ]
        );
    }

    /**
     * PRODUCT_INTEREST: después de procesar un order message.
     *
     * Tipo esperado: button_reply
     * IDs esperados: "pi_seguir" | "menu_inicio"
     */
    private function handleProductInterest(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);

        $this->logger->info("PRODUCT_INTEREST [{$phone}] btnId=" . ($btnId ?? 'null'));

        if ($btnId === 'pi_seguir') {
            $this->logTrans($phone, 'PRODUCT_INTEREST', 'BROWSE_MENU', 'pi_seguir');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // "menu_inicio" o cualquier otro mensaje → volver al inicio
        $this->logTrans($phone, 'PRODUCT_INTEREST', 'WELCOME', $btnId ?? 'otro');
        $this->conv->reset($phone);
        $this->sendWelcomeMessage($phone);
    }

    /**
     * ORDER MESSAGE: el cliente envía su carrito de WhatsApp.
     * Se procesa independientemente del estado actual.
     */
    private function handleOrderMessage(string $phone, array $message, array $conversation): void
    {
        $orderData = $message['order'] ?? [];
        $catalogId = (string)($orderData['catalog_id'] ?? '');
        $items     = $orderData['product_items'] ?? [];

        $this->logger->info(
            "ORDER [{$phone}] catalogId={$catalogId} items=" . count($items)
        );

        if (empty($items)) {
            $this->api->sendText(
                $phone,
                'Recibimos tu solicitud pero no pudimos identificar los productos. '
                . 'Por favor escribinos directamente para ayudarte.'
            );
            return;
        }

        // Resolver productos desde la BD usando los product_retailer_id
        $retailerIds = array_column($items, 'product_retailer_id');
        $productRows = $this->products->getProductsByRetailerIds($retailerIds);

        // Indexar por retailer_id para búsqueda rápida
        $productMap = [];
        foreach ($productRows as $row) {
            $productMap[(string)$row['id']] = $row;
        }

        // Registrar pedidos en wa_orders
        $this->orders->createFromOrderMessage($phone, $items, $catalogId, $productMap);

        // Confirmar recepción del pedido
        $this->api->sendText(
            $phone,
            "¡Excelente elección! 📦 Registramos tu pedido.\n\n"
            . 'Para coordinar la entrega y el pago, contactá directamente al artesano:'
        );

        // Agrupar productos por vendedor y enviar un link wa.me por vendedor
        $vendorGroups = [];
        foreach ($productRows as $row) {
            $vid = (string)$row['vendor_id'];
            if (!isset($vendorGroups[$vid])) {
                $vendorGroups[$vid] = ['vendor' => $row, 'product_names' => []];
            }
            $vendorGroups[$vid]['product_names'][] = $row['name'];
        }

        foreach ($vendorGroups as $group) {
            $vendorPhone = (string)($group['vendor']['vendor_phone'] ?? '');
            $vendorName  = (string)($group['vendor']['vendor_name']  ?? 'el artesano');
            $productList = implode(', ', $group['product_names']);

            if ($vendorPhone) {
                $waText = rawurlencode(
                    "Hola! Vi {$productList} en TiendaMoroni y me interesa. "
                    . '¿Podés darme más información sobre precio y entrega?'
                );
                $waLink = "https://wa.me/{$vendorPhone}?text={$waText}";
            } else {
                $waLink = $this->config['app']['base_url'];
            }

            $this->api->sendText(
                $phone,
                "🧵 *{$vendorName}*\n"
                . "Producto/s: {$productList}\n\n"
                . "👉 {$waLink}"
            );
        }

        // Botones post-pedido
        $this->logTrans($phone, $conversation['current_state'], 'PRODUCT_INTEREST', 'order-message');
        $this->conv->setState($phone, 'PRODUCT_INTEREST');
        $this->api->sendReplyButtons(
            $phone,
            '¿Qué querés hacer ahora?',
            [
                ['id' => 'pi_seguir',   'title' => 'Seguir comprando'],
                ['id' => 'menu_inicio', 'title' => 'Volver al inicio'],
            ]
        );
    }

    // ── Constructores de mensajes ─────────────────────────────────────────────

    private function sendWelcomeMessage(string $phone): void
    {
        $this->api->sendReplyButtons(
            $phone,
            "¡Hola! 👋 Bienvenido a *TiendaMoroni*, donde encontrás productos únicos "
            . "hechos a mano por artesanos de nuestra comunidad.\n\n"
            . '¿Cómo querés explorar el catálogo?',
            [
                ['id' => 'btn_ver_web',      'title' => 'Ver en la web'],
                ['id' => 'btn_ver_whatsapp', 'title' => 'Ver en WhatsApp'],
            ]
        );
    }

    private function sendBrowseMenu(string $phone): void
    {
        $this->api->sendList(
            $phone,
            'Explorar catálogo',
            'Elegí cómo querés navegar los productos de TiendaMoroni.',
            'TiendaMoroni - Productos únicos para tu fe',
            'Ver opciones',
            [
                [
                    'title' => 'Navegación',
                    'rows'  => [
                        [
                            'id'          => 'menu_categorias',
                            'title'       => 'Ver por categoría',
                            //'description' => 'Filtrá por tipo de producto',
                        ],
                        [
                            'id'          => 'menu_vendedores',
                            'title'       => 'Ver por vendedor',
                            //'description' => 'Explorá por artesano o vendedor',
                        ],
                        [
                            'id'          => 'menu_buscar',
                            'title'       => 'Buscar producto',
                            //'description' => 'Escribí una palabra clave',
                        ],
                        [
                            'id'          => 'menu_inicio',
                            'title'       => 'Volver al inicio',
                            //'description' => 'Ver pantalla inicial',
                        ],
                    ],
                ],
            ]
        );
    }

    private function sendCategoryList(string $phone, int $page): void
    {
        $perPage = ProductService::ITEMS_PER_LIST_PAGE;
        $offset  = ($page - 1) * $perPage;
        $total   = $this->products->countActiveCategories();
        $cats    = $this->products->getActiveCategories($offset, $perPage);
        $hasMore = ($offset + $perPage) < $total;

        if (empty($cats)) {
            $this->api->sendText($phone, 'No hay categorías con productos disponibles por el momento.');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        $rows = [];
        foreach ($cats as $cat) {
            $count  = (int)$cat['product_count'];
            $rows[] = [
                'id'          => 'cat_' . $cat['id'],
                'title'       => $cat['name'],
                'description' => $count . ' ' . ($count === 1 ? 'producto' : 'productos'),
            ];
        }

        if ($hasMore) {
            $rows[] = [
                'id'          => 'nav_mas_cats',
                'title'       => 'Ver más categorías',
                'description' => 'Pág. ' . ($page + 1),
            ];
        }
        $rows[] = ['id' => 'nav_volver', 'title' => 'Volver al menú', 'description' => ''];

        $header = $page > 1 ? "Categorías – pág. {$page}" : 'Categorías disponibles';

        $this->api->sendList(
            $phone,
            $header,
            'Elegí una categoría para ver sus productos:',
            'TiendaMoroni · ' . $total . ' categorías',
            'Ver categorías',
            [['title' => 'Categorías', 'rows' => $rows]]
        );
    }

    private function sendSellerList(string $phone, int $page): void
    {
        $perPage = ProductService::ITEMS_PER_LIST_PAGE;
        $offset  = ($page - 1) * $perPage;
        $total   = $this->products->countActiveVendors();
        $sellers = $this->products->getActiveVendors($offset, $perPage);
        $hasMore = ($offset + $perPage) < $total;

        if (empty($sellers)) {
            $this->api->sendText($phone, 'No hay artesanos con productos disponibles por el momento.');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        $rows = [];
        foreach ($sellers as $sel) {
            $count  = (int)$sel['product_count'];
            $rows[] = [
                'id'          => 'sel_' . $sel['id'],
                'title'       => $sel['name'],
                'description' => $count . ' ' . ($count === 1 ? 'producto' : 'productos'),
            ];
        }

        if ($hasMore) {
            $rows[] = [
                'id'          => 'nav_mas_sels',
                'title'       => 'Ver más artesanos',
                'description' => 'Pág. ' . ($page + 1),
            ];
        }
        $rows[] = ['id' => 'nav_volver', 'title' => 'Volver al menú', 'description' => ''];

        $header = $page > 1 ? "Artesanos – pág. {$page}" : 'Nuestros artesanos';

        $this->api->sendList(
            $phone,
            $header,
            'Elegí un artesano para ver sus creaciones:',
            'TiendaMoroni · ' . $total . ' artesanos',
            'Ver artesanos',
            [['title' => 'Artesanos', 'rows' => $rows]]
        );
    }

    private function sendProductsDisplay(string $phone, array $ctx): void
    {
        $filter = $ctx['filter_type'] ?? 'search';
        $page   = (int)($ctx['page'] ?? 1);
        $offset = ($page - 1) * ProductService::PRODUCTS_PER_PAGE;
        $limit  = ProductService::PRODUCTS_PER_PAGE;

        $products = [];
        $total    = 0;
        $title    = 'Productos';

        switch ($filter) {
            case 'category':
                $catId    = (int)($ctx['filter_id'] ?? 0);
                $products = $this->products->getProductsByCategory($catId, $offset, $limit);
                $total    = $this->products->countProductsByCategory($catId);
                $title    = $this->products->getCategoryName($catId);
                break;

            case 'vendor':
                $venId    = (int)($ctx['filter_id'] ?? 0);
                $products = $this->products->getProductsByVendor($venId, $offset, $limit);
                $total    = $this->products->countProductsByVendor($venId);
                $title    = $this->products->getVendorName($venId);
                break;

            case 'search':
                $term     = (string)($ctx['search_term'] ?? '');
                $products = $this->products->searchProducts($term, $offset, $limit);
                $total    = $this->products->countSearchProducts($term);
                $title    = "Resultados: \"{$term}\"";
                break;
        }

        if (empty($products)) {
            $this->api->sendText($phone, 'No hay productos disponibles en esta sección por el momento.');
            $this->api->sendReplyButtons(
                $phone,
                '¿Qué querés hacer?',
                [
                    ['id' => 'nav_menu',   'title' => 'Volver al menú'],
                    ['id' => 'nav_buscar', 'title' => 'Nueva búsqueda'],
                ]
            );
            return;
        }

        $hasMore   = ($offset + $limit) < $total;
        $catalogId = $this->config['whatsapp']['catalog_id'];

        $this->api->sendProductList(
            $phone,
            wa_truncate($title, 60),
            'Deslizá para ver todos los productos:',
            'TiendaMoroni - Productos únicos para tu fe',
            $catalogId,
            [
                [
                    'title'    => wa_truncate($title, 24),
                    'products' => array_map(
                        fn($p) => ['retailer_id' => (string)$p['id']],
                        $products
                    ),
                ],
            ]
        );

        $navButtons = [['id' => 'nav_menu', 'title' => 'Volver al menú']];
        if ($hasMore) {
            array_unshift($navButtons, ['id' => 'nav_mas', 'title' => 'Ver más']);
        }
        $navButtons[] = ['id' => 'nav_buscar', 'title' => 'Nueva búsqueda'];

        $shown    = count($products);
        $pageInfo = $page > 1 ? " (pág. {$page})" : '';
        $navText  = "Mostrando {$shown} de {$total} productos{$pageInfo}.";

        $this->api->sendReplyButtons($phone, $navText, array_slice($navButtons, 0, 3));
    }

    // ── Helpers de extracción de datos del mensaje ────────────────────────────

    /**
     * Extrae el texto de un mensaje de tipo "text".
     * Devuelve null si el mensaje no es texto libre.
     */
    private function getMessageText(array $message): ?string
    {
        if (($message['type'] ?? '') === 'text') {
            return $message['text']['body'] ?? null;
        }
        return null;
    }

    /**
     * Extrae el ID del botón de respuesta rápida (interactive → button_reply).
     * Devuelve null si el mensaje no es button_reply.
     * Usa trim() y comparación case-insensitive para tolerar variaciones de la API.
     */
    private function getButtonReplyId(array $message): ?string
    {
        if (strtolower(trim($message['type'] ?? '')) !== 'interactive') {
            return null;
        }
        $interactive = $message['interactive'] ?? [];
        $intType     = strtolower(trim($interactive['type'] ?? ''));

        if ($intType === 'button_reply' || isset($interactive['button_reply']['id'])) {
            $id = $interactive['button_reply']['id'] ?? null;
            return $id !== null ? trim((string)$id) : null;
        }
        return null;
    }

    /**
     * Extrae el ID de la fila seleccionada (interactive → list_reply).
     * Devuelve null si el mensaje no es list_reply.
     * Usa trim() y comparación case-insensitive para tolerar variaciones de la API.
     */
    private function getListReplyId(array $message): ?string
    {
        if (strtolower(trim($message['type'] ?? '')) !== 'interactive') {
            return null;
        }
        $interactive = $message['interactive'] ?? [];
        $intType     = strtolower(trim($interactive['type'] ?? ''));

        if ($intType === 'list_reply' || isset($interactive['list_reply']['id'])) {
            $id = $interactive['list_reply']['id'] ?? null;
            return $id !== null ? trim((string)$id) : null;
        }
        return null;
    }

    // ── Helpers de logging ────────────────────────────────────────────────────

    /**
     * Registra los detalles del mensaje entrante para diagnóstico.
     * Formato: IN phone=X state=Y type=Z btnId=W listId=V
     */
    private function logIn(string $phone, string $state, array $message): void
    {
        $type        = $message['type'] ?? 'unknown';
        $intType     = $message['interactive']['type'] ?? '-';
        $btnId       = $this->getButtonReplyId($message) ?? 'null';
        $listId      = $this->getListReplyId($message)   ?? 'null';
        $hasText     = $this->getMessageText($message) !== null ? 'si' : 'no';

        $interactiveRaw = isset($message['interactive'])
            ? json_encode($message['interactive'], JSON_UNESCAPED_UNICODE)
            : 'null';

        $this->logger->info(
            "IN phone={$phone} state={$state} type={$type}"
            . " interactiveType={$intType} btnId={$btnId} listId={$listId} hasText={$hasText}"
            . " interactive_raw={$interactiveRaw}"
        );
    }

    /**
     * Registra una transición de estado.
     * Formato: TRANS phone=X FROM → TO reason=Z
     */
    private function logTrans(string $phone, string $from, string $to, string $reason): void
    {
        $this->logger->info(
            "TRANS phone={$phone} {$from} → {$to} reason={$reason}"
        );
    }
}
