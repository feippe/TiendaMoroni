<?php
/**
 * MessageRouter – Lógica central de la máquina de estados del bot IVR.
 *
 * Recibe el mensaje entrante + estado actual de la conversación y decide
 * qué acción tomar y a qué estado transicionar.
 *
 * ─── Diagrama de transiciones ────────────────────────────────────────────────
 *
 *  WELCOME
 *    → cualquier mensaje             → muestra bienvenida (permanece en WELCOME)
 *    → btn "btn_ver_web"             → envía CTA URL → permanece en WELCOME
 *    → btn "btn_ver_whatsapp"        → BROWSE_MENU
 *
 *  BROWSE_MENU
 *    → list "menu_categorias"        → SELECT_CATEGORY (pág. 1)
 *    → list "menu_vendedores"        → SELECT_SELLER   (pág. 1)
 *    → list "menu_buscar"            → SEARCH_PROMPT
 *    → list/btn "menu_inicio"        → WELCOME
 *
 *  SELECT_CATEGORY
 *    → list "cat_{id}"               → SHOW_PRODUCTS (filter: category)
 *    → list "nav_mas_cats"           → SELECT_CATEGORY (pág. siguiente)
 *    → list "nav_volver"             → BROWSE_MENU
 *
 *  SELECT_SELLER
 *    → list "sel_{id}"               → SHOW_PRODUCTS (filter: vendor)
 *    → list "nav_mas_sels"           → SELECT_SELLER (pág. siguiente)
 *    → list "nav_volver"             → BROWSE_MENU
 *
 *  SEARCH_PROMPT
 *    → texto libre                   → SHOW_PRODUCTS (filter: search) | sin resultados → BROWSE_MENU
 *    → otro tipo de mensaje          → re-solicita texto
 *
 *  SHOW_PRODUCTS
 *    → btn "nav_menu"                → BROWSE_MENU
 *    → btn "nav_buscar"              → SEARCH_PROMPT
 *    → btn "nav_mas"                 → SHOW_PRODUCTS (pág. siguiente)
 *    → order message                 → PRODUCT_INTEREST
 *
 *  PRODUCT_INTEREST
 *    → btn "pi_seguir"               → BROWSE_MENU
 *    → cualquier otro mensaje        → WELCOME
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

        // 2. Registrar mensaje entrante
        $this->logger->logIncoming($phone, $message['type'] ?? 'unknown', $message);

        // 3. Si la conversación expiró → resetear a WELCOME
        if ($this->conv->isTimedOut($conversation)) {
            $this->logger->info("Timeout para {$phone}, reseteando a WELCOME");
            $this->conv->reset($phone);
            $conversation = $this->conv->getOrCreate($phone);
        }

        // 4. Los order messages se procesan en cualquier estado
        if (($message['type'] ?? '') === 'order') {
            $this->handleOrderMessage($phone, $message, $conversation);
            return;
        }

        // 5. Despachar según el estado actual
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
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
        }
    }

    // ── Manejadores de estado ─────────────────────────────────────────────────

    /**
     * WELCOME: muestra bienvenida con los dos botones principales.
     */
    private function handleWelcome(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);

        if ($btnId === 'btn_ver_web') {
            // Redirigir a la web → volver al inicio
            $this->api->sendCtaUrl(
                $phone,
                'Visitá nuestra tienda online para ver todos los productos con fotos en alta calidad.',
                'Ir a TiendaMoroni.com',
                $this->config['app']['base_url']
            );
            $this->conv->reset($phone);
            return;
        }

        if ($btnId === 'btn_ver_whatsapp') {
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // Cualquier otro mensaje (primer contacto, hola, etc.) → mostrar bienvenida
        $this->sendWelcomeMessage($phone);
        $this->conv->setState($phone, 'WELCOME');
    }

    /**
     * BROWSE_MENU: menú principal de navegación.
     */
    private function handleBrowseMenu(string $phone, array $message, array $conversation): void
    {
        // Acepta tanto list_reply como button_reply (para navegación desde otros estados)
        $replyId = $this->getListReplyId($message) ?? $this->getButtonReplyId($message);

        switch ($replyId) {
            case 'menu_categorias':
                $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => 1]);
                $this->sendCategoryList($phone, 1);
                break;

            case 'menu_vendedores':
                $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => 1]);
                $this->sendSellerList($phone, 1);
                break;

            case 'menu_buscar':
            case 'search_retry':
                $this->conv->setState($phone, 'SEARCH_PROMPT');
                $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
                break;

            case 'menu_inicio':
            default:
                $this->conv->reset($phone);
                $this->sendWelcomeMessage($phone);
                break;
        }
    }

    /**
     * SELECT_CATEGORY: el cliente elige una categoría de la lista interactiva.
     */
    private function handleSelectCategory(string $phone, array $message, array $conversation): void
    {
        $replyId = $this->getListReplyId($message);
        $ctx     = $conversation['context'];

        if ($replyId === 'nav_volver') {
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($replyId === 'nav_mas_cats') {
            $nextPage = ((int)($ctx['cat_page'] ?? 1)) + 1;
            $this->conv->setState($phone, 'SELECT_CATEGORY', ['cat_page' => $nextPage]);
            $this->sendCategoryList($phone, $nextPage);
            return;
        }

        if ($replyId && str_starts_with($replyId, 'cat_')) {
            $categoryId = (int)substr($replyId, 4);
            $newCtx = ['filter_type' => 'category', 'filter_id' => $categoryId, 'page' => 1];
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Respuesta inesperada → reenviar la misma lista
        $this->sendCategoryList($phone, (int)($ctx['cat_page'] ?? 1));
    }

    /**
     * SELECT_SELLER: el cliente elige un vendedor de la lista interactiva.
     */
    private function handleSelectSeller(string $phone, array $message, array $conversation): void
    {
        $replyId = $this->getListReplyId($message);
        $ctx     = $conversation['context'];

        if ($replyId === 'nav_volver') {
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($replyId === 'nav_mas_sels') {
            $nextPage = ((int)($ctx['sel_page'] ?? 1)) + 1;
            $this->conv->setState($phone, 'SELECT_SELLER', ['sel_page' => $nextPage]);
            $this->sendSellerList($phone, $nextPage);
            return;
        }

        if ($replyId && str_starts_with($replyId, 'sel_')) {
            $vendorId = (int)substr($replyId, 4);
            $newCtx = ['filter_type' => 'vendor', 'filter_id' => $vendorId, 'page' => 1];
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Respuesta inesperada → reenviar la misma lista
        $this->sendSellerList($phone, (int)($ctx['sel_page'] ?? 1));
    }

    /**
     * SEARCH_PROMPT: el siguiente mensaje de texto libre es el término de búsqueda.
     */
    private function handleSearchPrompt(string $phone, array $message, array $conversation): void
    {
        $text = $this->getMessageText($message);

        // Si no es texto libre, recordar al cliente lo que debe hacer
        if ($text === null) {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        // Sanitizar y limitar longitud del término de búsqueda
        $term = mb_substr(trim($text), 0, 100);

        if ($term === '') {
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        $count = $this->products->countSearchProducts($term);

        if ($count === 0) {
            // Sin resultados → ofrecer volver al menú o buscar de nuevo
            $this->api->sendReplyButtons(
                $phone,
                "No encontré productos con \"*{$term}*\". Intentá con otra palabra.",
                [
                    ['id' => 'menu_buscar', 'title' => 'Buscar de nuevo'],
                    ['id' => 'menu_inicio', 'title' => 'Volver al menú'],
                ]
            );
            // Volver a BROWSE_MENU para que los botones anteriores funcionen
            $this->conv->setState($phone, 'BROWSE_MENU');
            return;
        }

        $newCtx = ['filter_type' => 'search', 'search_term' => $term, 'page' => 1];
        $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
        $this->sendProductsDisplay($phone, $newCtx);
    }

    /**
     * SHOW_PRODUCTS: navega por páginas de productos o vuelve al menú.
     */
    private function handleShowProducts(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);
        $ctx   = $conversation['context'];

        if ($btnId === 'nav_menu' || $btnId === 'menu_inicio') {
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        if ($btnId === 'nav_buscar') {
            $this->conv->setState($phone, 'SEARCH_PROMPT');
            $this->api->sendText($phone, 'Escribí el nombre o palabra clave del producto que buscás:');
            return;
        }

        if ($btnId === 'nav_mas') {
            $nextPage = ((int)($ctx['page'] ?? 1)) + 1;
            $newCtx   = array_merge($ctx, ['page' => $nextPage]);
            $this->conv->setState($phone, 'SHOW_PRODUCTS', $newCtx);
            $this->sendProductsDisplay($phone, $newCtx);
            return;
        }

        // Respuesta inesperada → reenviar botones de navegación
        $this->api->sendReplyButtons(
            $phone,
            '¿Qué querés hacer?',
            [
                ['id' => 'nav_menu',    'title' => 'Volver al menú'],
                ['id' => 'nav_buscar',  'title' => 'Nueva búsqueda'],
            ]
        );
    }

    /**
     * PRODUCT_INTEREST: después de un order message procesado.
     */
    private function handleProductInterest(string $phone, array $message, array $conversation): void
    {
        $btnId = $this->getButtonReplyId($message);

        if ($btnId === 'pi_seguir') {
            $this->conv->setState($phone, 'BROWSE_MENU');
            $this->sendBrowseMenu($phone);
            return;
        }

        // "Volver al inicio" o cualquier otro mensaje
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
            $vendorPhone  = (string)($group['vendor']['vendor_phone'] ?? '');
            $vendorName   = (string)($group['vendor']['vendor_name']  ?? 'el artesano');
            $productList  = implode(', ', $group['product_names']);

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

    /**
     * Envía el mensaje de bienvenida con los dos botones de inicio.
     */
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

    /**
     * Envía el menú principal de navegación como lista interactiva.
     */
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
                            'description' => 'Filtrá por tipo de producto',
                        ],
                        [
                            'id'          => 'menu_vendedores',
                            'title'       => 'Ver por vendedor',
                            'description' => 'Explorá por artesano',
                        ],
                        [
                            'id'          => 'menu_buscar',
                            'title'       => 'Buscar producto',
                            'description' => 'Escribí una palabra clave',
                        ],
                        [
                            'id'          => 'menu_inicio',
                            'title'       => 'Volver al inicio',
                            'description' => 'Ver pantalla inicial',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Envía la lista paginada de categorías con productos activos.
     */
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

    /**
     * Envía la lista paginada de vendedores con productos activos.
     */
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

    /**
     * Muestra la lista de productos según el filtro activo del contexto.
     * Usa product_list (multi-product message) para mostrar los productos
     * con imagen, nombre y precio del catálogo de Meta.
     *
     * @param string $phone  Número del cliente.
     * @param array  $ctx    Contexto con 'filter_type', 'filter_id'/'search_term', 'page'.
     */
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

        // Enviar el product_list (WhatsApp toma imagen/precio del catálogo de Meta)
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

        // Botones de navegación enviados justo después del product_list
        $navButtons = [
            ['id' => 'nav_menu', 'title' => 'Volver al menú'],
        ];
        if ($hasMore) {
            array_unshift($navButtons, ['id' => 'nav_mas', 'title' => 'Ver más']);
        }
        $navButtons[] = ['id' => 'nav_buscar', 'title' => 'Nueva búsqueda'];

        $shown   = count($products);
        $pageInfo = $page > 1 ? " (pág. {$page})" : '';
        $navText = "Mostrando {$shown} de {$total} productos{$pageInfo}.";

        // sendReplyButtons acepta máx. 3 botones
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
     */
    private function getButtonReplyId(array $message): ?string
    {
        if (
            ($message['type'] ?? '') === 'interactive' &&
            ($message['interactive']['type'] ?? '') === 'button_reply'
        ) {
            return $message['interactive']['button_reply']['id'] ?? null;
        }
        return null;
    }

    /**
     * Extrae el ID de la fila seleccionada de una lista interactiva (interactive → list_reply).
     */
    private function getListReplyId(array $message): ?string
    {
        if (
            ($message['type'] ?? '') === 'interactive' &&
            ($message['interactive']['type'] ?? '') === 'list_reply'
        ) {
            return $message['interactive']['list_reply']['id'] ?? null;
        }
        return null;
    }
}
