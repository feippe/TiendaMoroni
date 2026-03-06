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
 *  BROWSE_MENU  (espera list_reply o button_reply desde búsqueda fallida)
 *    → "menu_categorias"      → SELECT_CATEGORY (pág. 1)
 *    → "menu_vendedores"      → SELECT_SELLER   (pág. 1)
 *    → "menu_buscar"          → SEARCH_PROMPT
 *    → "menu_inicio"          → WELCOME
 *    → cualquier otro input   → reenvía el menú (NO resetea)
 *
 *  SELECT_CATEGORY  (espera list_reply)
 *    → "cat_{id}"             → SHOW_PRODUCTS (filter: category)
 *    → "nav_mas_cats"         → SELECT_CATEGORY (pág. siguiente)
 *    → "nav_volver"           → BROWSE_MENU
 *    → cualquier otro input   → reenvía la lista
 *
 *  SELECT_SELLER  (espera list_reply)
 *    → "sel_{id}"             → SHOW_PRODUCTS (filter: vendor)
 *    → "nav_mas_sels"         → SELECT_SELLER (pág. siguiente)
 *    → "nav_volver"           → BROWSE_MENU
 *    → cualquier otro input   → reenvía la lista
 *
 *  SEARCH_PROMPT  (espera text)
 *    → text libre             → SHOW_PRODUCTS (filter: search) | sin resultados → BROWSE_MENU
 *    → cualquier otro tipo    → re-solicita texto
 *
 *  SHOW_PRODUCTS  (espera button_reply)
 *    → "nav_menu"             → BROWSE_MENU
 *    → "nav_buscar"           → SEARCH_PROMPT
 *    → "nav_mas"              → SHOW_PRODUCTS (pág. siguiente)
 *    → order message          → PRODUCT_INTEREST
 *    → cualquier otro input   → reenvía botones de navegación
 *
 *  PRODUCT_INTEREST  (espera button_reply)
 *    → "pi_seguir"            → BROWSE_MENU
 *    → "menu_inicio" / otro   → WELCOME
 *
 * ─── Arquitectura del parser ─────────────────────────────────────────────────
 *
 *  parseMessage() extrae TODOS los campos del mensaje UNA SOLA VEZ en route().
 *  El resultado se pasa a cada handler como $parsed:
 *    [
 *      'type'    => 'text' | 'interactive' | 'order' | string,
 *      'subtype' => 'button_reply' | 'list_reply' | null,
 *      'id'      => string|null,   ← ID del botón o fila seleccionada
 *      'text'    => string|null,   ← Cuerpo del mensaje de texto
 *      'order'   => array|null,    ← Datos del carrito
 *    ]
 *
 *  El ID se extrae de forma EXCLUSIVA según el subtipo (sin fallbacks ambiguos):
 *    button_reply → interactive.button_reply.id
 *    list_reply   → interactive.list_reply.id
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

        // 2. Registrar mensaje entrante en BD (payload raw completo)
        $this->logger->logIncoming($phone, $message['type'] ?? 'unknown', $message);

        // 3. Parsear el mensaje UNA SOLA VEZ de forma centralizada
        $parsed = $this->parseMessage($message);

        // 4. Si la conversación expiró → resetear a WELCOME
        if ($this->conv->isTimedOut($conversation)) {
            $this->logTrans($phone, $conversation['current_state'], 'WELCOME', 'timeout');
            $this->conv->reset($phone);
            $conversation = $this->conv->getOrCreate($phone);
        }

        // 5. Log del router DESPUÉS del posible timeout-reset (refleja el estado real)
        $state = $conversation['current_state'];
        $this->logger->info(
            "ROUTER: phone={$phone} state={$state}"
            . " msgType={$parsed['type']}"
            . " interactiveType=" . ($parsed['subtype'] ?? '-')
            . " extractedId=" . ($parsed['id'] ?? 'null')
            . " extractedText=" . ($parsed['text'] !== null
                ? '"' . mb_substr($parsed['text'], 0, 50) . '"'
                : 'null')
        );

        // 6. Los order messages se procesan independientemente del estado
        if ($parsed['type'] === 'order') {
            $this->handleOrderMessage($phone, $parsed, $conversation);
            return;
        }

        // 7. Despachar según el estado actual
        switch ($state) {
            case 'WELCOME':
                $this->handleWelcome($phone, $parsed, $conversation);
                break;
            case 'BROWSE_MENU':
                $this->handleBrowseMenu($phone, $parsed, $conversation);
                break;
            case 'SELECT_CATEGORY':
                $this->handleSelectCategory($phone, $parsed, $conversation);
                break;
            case 'SELECT_SELLER':
                $this->handleSelectSeller($phone, $parsed, $conversation);
                break;
            case 'SEARCH_PROMPT':
                $this->handleSearchPrompt($phone, $parsed, $conversation);
                break;
            case 'SHOW_PRODUCTS':
                $this->handleShowProducts($phone, $parsed, $conversation);
                break;
            case 'PRODUCT_INTEREST':
                $this->handleProductInterest($phone, $parsed, $conversation);
                break;
            default:
                // Estado desconocido en BD → resetear
                $this->logTrans($phone, $state, 'WELCOME', 'estado-desconocido');
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
        }
    }

    // ── Manejadores de estado ─────────────────────────────────────────────────

    /**
     * WELCOME: espera button_reply con los dos botones de inicio.
     *
     * IDs esperados: "btn_ver_web" | "btn_ver_whatsapp"
     */
    private function handleWelcome(string $phone, array $parsed, array $conversation): void
    {
        $id = $parsed['id'];

        $this->logger->info(
            "HANDLER handleWelcome: phone={$phone}"
            . " id=" . ($id ?? 'null')
            . " subtype=" . ($parsed['subtype'] ?? '-')
        );

        if ($id === 'btn_ver_web') {
            $this->logTrans($phone, 'WELCOME', 'WELCOME', 'cta-web');
            $this->api->sendCtaUrl(
                $phone,
                'Visitá nuestra tienda online para ver todos los productos con fotos en alta calidad.',
                'Ir a TiendaMoroni.com',
                $this->config['app']['base_url']
            );
            $this->conv->setState($phone, 'WELCOME');
            return;
        }

        if ($id === 'btn_ver_whatsapp') {
            $this->logTrans($phone, 'WELCOME', 'BROWSE_MENU', 'btn_ver_whatsapp');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // Cualquier otro mensaje (primer contacto, texto libre, etc.) → bienvenida
        $this->logger->info(
            "HANDLER handleWelcome: id inesperado='" . ($id ?? 'null') . "' → mostrando bienvenida"
        );
        $this->conv->setState($phone, 'WELCOME');
        $this->sendWelcomeMessage($phone);
    }

    /**
     * BROWSE_MENU: espera una list_reply del menú de navegación.
     *
     * También acepta button_reply (ej: botones "Buscar de nuevo" / "Volver al menú"
     * enviados desde SEARCH_PROMPT cuando no hay resultados, mientras el estado
     * permanece BROWSE_MENU).
     *
     * IDs esperados: "menu_categorias" | "menu_vendedores" | "menu_buscar" | "menu_inicio"
     */
    private function handleBrowseMenu(string $phone, array $parsed, array $conversation): void
    {
        $id = $parsed['id'];

        $this->logger->info(
            "HANDLER handleBrowseMenu: phone={$phone}"
            . " id=" . ($id ?? 'null')
            . " subtype=" . ($parsed['subtype'] ?? '-')
        );

        switch ($id) {
            case 'menu_categorias':
                $this->logTrans($phone, 'BROWSE_MENU', 'SELECT_CATEGORY', $id);
                $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => 1]);
                $this->sendCategoryList($phone, 1);
                break;

            case 'menu_vendedores':
                $this->logTrans($phone, 'BROWSE_MENU', 'SELECT_SELLER', $id);
                $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => 1]);
                $this->sendSellerList($phone, 1);
                break;

            case 'menu_buscar':
            case 'search_retry':
                $this->logTrans($phone, 'BROWSE_MENU', 'SEARCH_PROMPT', $id);
                $this->conv->setState($phone, 'SEARCH_PROMPT');
                $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
                break;

            case 'menu_inicio':
                $this->logTrans($phone, 'BROWSE_MENU', 'WELCOME', 'menu_inicio');
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
                break;

            default:
                // Input no reconocido (texto libre, ID desconocido, etc.)
                // NO resetear a WELCOME: reenviar el menú para mantener al usuario en contexto.
                $this->logger->info(
                    "HANDLER handleBrowseMenu: id inesperado='"
                    . ($id ?? 'null') . "' → reenviando menú"
                );
                $this->sendBrowseMenu($phone);
                break;
        }
    }

    /**
     * SELECT_CATEGORY: espera list_reply con el ID de la categoría elegida.
     *
     * IDs esperados: "cat_{id}" | "nav_mas_cats" | "nav_volver"
     */
    private function handleSelectCategory(string $phone, array $parsed, array $conversation): void
    {
        $id  = $parsed['id'];
        $ctx = $conversation['context'];

        $this->logger->info(
            "HANDLER handleSelectCategory: phone={$phone}"
            . " id=" . ($id ?? 'null')
            . " cat_page=" . ($ctx['cat_page'] ?? 1)
        );

        if ($id === 'nav_volver') {
            $this->logTrans($phone, 'SELECT_CATEGORY', 'BROWSE_MENU', 'nav_volver');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($id === 'nav_mas_cats') {
            $nextPage = ((int)($ctx['cat_page'] ?? 1)) + 1;
            $this->logTrans($phone, 'SELECT_CATEGORY', 'SELECT_CATEGORY', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => $nextPage]);
            $this->sendCategoryList($phone, $nextPage);
            return;
        }

        if ($id !== null && str_starts_with($id, 'cat_')) {
            $categoryId = (int)substr($id, 4);
            $newCtx = ['filter_type' => 'category', 'filter_id' => $categoryId, 'page' => 1];
            $this->logTrans($phone, 'SELECT_CATEGORY', 'SHOW_PRODUCTS', "cat_id={$categoryId}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input inesperado → reenviar la misma lista sin cambiar estado
        $this->logger->info(
            "HANDLER handleSelectCategory: id inesperado='"
            . ($id ?? 'null') . "' → reenviando lista"
        );
        $this->sendCategoryList($phone, (int)($ctx['cat_page'] ?? 1));
    }

    /**
     * SELECT_SELLER: espera list_reply con el ID del vendedor elegido.
     *
     * IDs esperados: "sel_{id}" | "nav_mas_sels" | "nav_volver"
     * Nota: el prefijo del ID es "sel_", no "seller_".
     */
    private function handleSelectSeller(string $phone, array $parsed, array $conversation): void
    {
        $id  = $parsed['id'];
        $ctx = $conversation['context'];

        $this->logger->info(
            "HANDLER handleSelectSeller: phone={$phone}"
            . " id=" . ($id ?? 'null')
            . " sel_page=" . ($ctx['sel_page'] ?? 1)
        );

        if ($id === 'nav_volver') {
            $this->logTrans($phone, 'SELECT_SELLER', 'BROWSE_MENU', 'nav_volver');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($id === 'nav_mas_sels') {
            $nextPage = ((int)($ctx['sel_page'] ?? 1)) + 1;
            $this->logTrans($phone, 'SELECT_SELLER', 'SELECT_SELLER', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => $nextPage]);
            $this->sendSellerList($phone, $nextPage);
            return;
        }

        if ($id !== null && str_starts_with($id, 'sel_')) {
            $vendorId = (int)substr($id, 4);
            $newCtx = ['filter_type' => 'vendor', 'filter_id' => $vendorId, 'page' => 1];
            $this->logTrans($phone, 'SELECT_SELLER', 'SHOW_PRODUCTS', "vendor_id={$vendorId}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input inesperado → reenviar la misma lista sin cambiar estado
        $this->logger->info(
            "HANDLER handleSelectSeller: id inesperado='"
            . ($id ?? 'null') . "' → reenviando lista"
        );
        $this->sendSellerList($phone, (int)($ctx['sel_page'] ?? 1));
    }

    /**
     * SEARCH_PROMPT: el siguiente mensaje de texto libre es el término de búsqueda.
     *
     * Tipo esperado: text
     */
    private function handleSearchPrompt(string $phone, array $parsed, array $conversation): void
    {
        $text = $parsed['text'];

        $this->logger->info(
            "HANDLER handleSearchPrompt: phone={$phone}"
            . " hasText=" . ($text !== null ? 'si' : 'no')
            . " text=" . ($text !== null ? '"' . mb_substr($text, 0, 30) . '"' : 'null')
        );

        if ($text === null) {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        $term = mb_substr(trim($text), 0, 100);

        if ($term === '') {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        $this->logger->info("HANDLER handleSearchPrompt: buscando término='{$term}'");

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
            // BROWSE_MENU para que los botones anteriores funcionen en el siguiente mensaje
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
     * IDs esperados: "nav_menu" | "nav_buscar" | "nav_mas"
     * También maneja: order message (carrito de WhatsApp), procesado en route()
     */
    private function handleShowProducts(string $phone, array $parsed, array $conversation): void
    {
        $id  = $parsed['id'];
        $ctx = $conversation['context'];

        $this->logger->info(
            "HANDLER handleShowProducts: phone={$phone}"
            . " id=" . ($id ?? 'null')
            . " subtype=" . ($parsed['subtype'] ?? '-')
            . " filter=" . ($ctx['filter_type'] ?? 'none')
            . " page=" . ($ctx['page'] ?? 1)
        );

        if ($id === 'nav_menu' || $id === 'menu_inicio') {
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'BROWSE_MENU', $id);
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($id === 'nav_buscar') {
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'SEARCH_PROMPT', 'nav_buscar');
            $this->conv->setState($phone, 'SEARCH_PROMPT');
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        if ($id === 'nav_mas') {
            $nextPage = ((int)($ctx['page'] ?? 1)) + 1;
            $newCtx   = array_merge($ctx, ['page' => $nextPage]);
            $this->logTrans($phone, 'SHOW_PRODUCTS', 'SHOW_PRODUCTS', "pag-{$nextPage}");
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Input no reconocido (list_reply, texto libre, etc.) → reenviar botones sin cambiar estado
        $this->logger->info(
            "HANDLER handleShowProducts: id inesperado='"
            . ($id ?? 'null') . "' → reenviando botones"
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
     * IDs esperados: "pi_seguir" | "menu_inicio"
     */
    private function handleProductInterest(string $phone, array $parsed, array $conversation): void
    {
        $id = $parsed['id'];

        $this->logger->info(
            "HANDLER handleProductInterest: phone={$phone} id=" . ($id ?? 'null')
        );

        if ($id === 'pi_seguir') {
            $this->logTrans($phone, 'PRODUCT_INTEREST', 'BROWSE_MENU', 'pi_seguir');
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // "menu_inicio" o cualquier otro mensaje → volver al inicio
        $this->logTrans($phone, 'PRODUCT_INTEREST', 'WELCOME', $id ?? 'otro');
        $this->conv->reset($phone);
        $this->sendWelcomeMessage($phone);
    }

    /**
     * ORDER MESSAGE: el cliente envía su carrito de WhatsApp.
     * Se procesa independientemente del estado actual (interceptado en route()).
     */
    private function handleOrderMessage(string $phone, array $parsed, array $conversation): void
    {
        $orderData = $parsed['order'] ?? [];
        $catalogId = (string)($orderData['catalog_id'] ?? '');
        $items     = $orderData['product_items'] ?? [];

        $this->logger->info(
            "HANDLER handleOrderMessage: phone={$phone}"
            . " catalogId={$catalogId} items=" . count($items)
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
                            'id'    => 'menu_categorias',
                            'title' => 'Ver por categoría',
                        ],
                        [
                            'id'    => 'menu_vendedores',
                            'title' => 'Ver por vendedor',
                        ],
                        [
                            'id'    => 'menu_buscar',
                            'title' => 'Buscar producto',
                        ],
                        [
                            'id'    => 'menu_inicio',
                            'title' => 'Volver al inicio',
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
        $rows[] = ['id' => 'nav_volver', 'title' => 'Volver al menú'];

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
        $rows[] = ['id' => 'nav_volver', 'title' => 'Volver al menú'];

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

        // ── Intentar enviar como product_list (catálogo nativo de WhatsApp) ──
        $result = $this->api->sendProductList(
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

        // ── Fallback: si product_list falla, enviar como texto con links ─────
        $httpCode = $result['_http_code'] ?? 200;
        if ($httpCode !== 200 || isset($result['error'])) {
            $this->logger->info(
                "FALLBACK: product_list falló (http={$httpCode}), enviando como texto. phone={$phone}"
            );
            $this->sendProductsAsText($phone, $products, $title);
        }

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

    /**
     * Fallback: envía productos como mensajes de texto con link a la web.
     * Se usa cuando el product_list falla (productos no aprobados en el catálogo, etc.).
     */
    private function sendProductsAsText(string $phone, array $products, string $title): void
    {
        $baseUrl = $this->config['app']['base_url'];
        $lines   = ["📋 *{$title}*\n"];

        foreach (array_slice($products, 0, 10) as $p) {
            $price = number_format((float)$p['price'], 0, ',', '.');
            $name  = $p['name'] ?? 'Producto';
            $link  = $baseUrl . '/producto/' . ($p['slug'] ?? $p['id']);
            $lines[] = "• *{$name}* — \${$price}\n  {$link}";
        }

        if (count($products) > 10) {
            $lines[] = "\n_...y " . (count($products) - 10) . " productos más._";
        }

        $lines[] = "\n🌐 Ver todos: {$baseUrl}";

        $this->api->sendText($phone, implode("\n", $lines));
    }

    // ── Parser centralizado de mensajes ───────────────────────────────────────

    /**
     * Parsea el mensaje entrante y extrae todos los campos relevantes en un array.
     *
     * Estructura devuelta:
     *   'type'    → 'text' | 'interactive' | 'order' | string
     *   'subtype' → 'button_reply' | 'list_reply' | null  (solo para interactive)
     *   'id'      → ID del botón o fila seleccionada; null si no aplica
     *   'text'    → Cuerpo del mensaje de texto; null si no es type=text
     *   'order'   → Array de datos del pedido; null si no es type=order
     *
     * Garantías del parser:
     *   - 'type' y 'subtype' siempre son lowercase+trim (sin importar qué envíe Meta)
     *   - 'id' se extrae EXCLUSIVAMENTE del subtipo correcto (button_reply XOR list_reply)
     *     sin condiciones || ni fallbacks que mezclen subtipos
     *   - 'id' es null si está vacío o no aplica
     */
    private function parseMessage(array $message): array
    {
        $type    = strtolower(trim((string)($message['type'] ?? '')));
        $subtype = null;
        $id      = null;
        $text    = null;
        $order   = null;

        switch ($type) {
            case 'text':
                $body = (string)($message['text']['body'] ?? '');
                $text = $body !== '' ? $body : null;
                break;

            case 'interactive':
                $interactive = $message['interactive'] ?? [];
                $subtype     = strtolower(trim((string)($interactive['type'] ?? '')));

                if ($subtype === 'button_reply') {
                    // ID exclusivamente de button_reply
                    $rawId = $interactive['button_reply']['id'] ?? null;
                    $id    = ($rawId !== null && trim((string)$rawId) !== '')
                        ? trim((string)$rawId)
                        : null;
                } elseif ($subtype === 'list_reply') {
                    // ID exclusivamente de list_reply
                    $rawId = $interactive['list_reply']['id'] ?? null;
                    $id    = ($rawId !== null && trim((string)$rawId) !== '')
                        ? trim((string)$rawId)
                        : null;
                }
                // Cualquier otro subtipo (nfm_reply, etc.) → $id queda null
                break;

            case 'order':
                $order = $message['order'] ?? [];
                break;
        }

        return [
            'type'    => $type,
            'subtype' => $subtype,
            'id'      => $id,
            'text'    => $text,
            'order'   => $order,
        ];
    }

    // ── Helpers de logging ────────────────────────────────────────────────────

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
