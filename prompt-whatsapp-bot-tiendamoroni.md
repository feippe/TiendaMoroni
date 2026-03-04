# Prompt: WhatsApp Business Cloud API – Bot IVR para TiendaMoroni

## Contexto del proyecto

Sos el desarrollador backend de **TiendaMoroni**, un marketplace de productos artesanales hechos a mano por la comunidad de La Iglesia de Jesucristo de los Santos de los Últimos Días en Uruguay. La plataforma ya está en producción en `https://tiendamoroni.com` y funciona con **PHP puro (sin frameworks), MySQL, Tailwind CSS y Alpine.js**, desplegado en un **hosting cPanel con SSL activo** mediante CI/CD con GitHub Actions + FTP.

El catálogo de productos se alimenta desde un **feed XML** que se sincroniza con **Meta Commerce Manager**. El catálogo ya está vinculado a la cuenta de WhatsApp Business.

Tu tarea es crear **toda la infraestructura backend en PHP** para integrar la **WhatsApp Cloud API de Meta** (directa, sin BSP) e implementar un bot conversacional estilo IVR que permita a los clientes explorar el catálogo de productos cuando escriben al WhatsApp del negocio.

---

## Stack tecnológico obligatorio

- **Lenguaje:** PHP 8.2 (el servidor usa esta versión)
- **Base de datos:** MySQL (la misma que ya usa TiendaMoroni)
- **Sin frameworks:** PHP puro, sin Laravel, Symfony ni similares
- **Sin Composer ni dependencias externas:** Usar solo funciones nativas de PHP (`curl`, `json_encode/decode`, `file_get_contents`, etc.)
- **Hosting:** cPanel con SSL (HTTPS activo)
- **Versionado:** Git + GitHub (el proyecto ya tiene CI/CD configurado)

---

## Arquitectura general

### Archivos y estructura de carpetas

```
/whatsapp/
├── webhook.php              ← Endpoint público (recibe GET para verificación y POST para mensajes)
├── config.php               ← Constantes de configuración (tokens, IDs, verify token)
├── WhatsAppAPI.php           ← Clase para enviar mensajes vía Cloud API (text, interactive list, reply buttons, product_list, CTA URL)
├── ConversationManager.php   ← Clase que gestiona el estado de cada conversación (máquina de estados)
├── MessageRouter.php         ← Clase que recibe el mensaje entrante y decide qué hacer según el estado actual
├── ProductService.php        ← Clase que consulta la base de datos para obtener productos, categorías, vendedores y hacer búsquedas
├── OrderService.php          ← Clase que crea pedidos de compra en el sistema existente
├── Logger.php                ← Clase simple para logging (guardar en archivo o tabla de BD)
└── sql/
    └── migrations.sql        ← SQL para crear las tablas necesarias
```

### Base de datos – Tablas nuevas

#### `wa_conversations` (estado de la conversación)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PK | — |
| phone_number | VARCHAR(20) UNIQUE | Número del cliente (formato internacional sin +) |
| current_state | VARCHAR(50) DEFAULT 'WELCOME' | Estado actual en la máquina de estados |
| context_data | JSON | Datos de contexto (categoría seleccionada, vendedor seleccionado, término de búsqueda, página actual, etc.) |
| last_interaction | DATETIME | Timestamp de la última interacción |
| created_at | DATETIME | — |

#### `wa_message_log` (log de mensajes para debugging)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PK | — |
| phone_number | VARCHAR(20) | — |
| direction | ENUM('incoming','outgoing') | — |
| message_type | VARCHAR(30) | text, interactive, button_reply, list_reply, etc. |
| payload | JSON | Payload completo del mensaje |
| created_at | DATETIME | — |

### Tablas existentes a considerar

El sistema ya tiene tablas de productos, categorías y vendedores. Asumí la siguiente estructura (adaptá si difiere):

```sql
-- Productos (ya existe, campos relevantes)
products.id
products.name
products.description
products.price              -- En pesos uruguayos (UYU)
products.image_url          -- URL pública de la imagen principal
products.category_id        -- FK a categories
products.seller_id          -- FK a sellers (o users)
products.content_id         -- Equivale al product_retailer_id del catálogo de Meta
products.status             -- 'active', 'inactive', etc.

-- Categorías (ya existe)
categories.id
categories.name

-- Vendedores/Usuarios (ya existe, agregar campo si no existe)
sellers.id                  -- o users.id
sellers.name
sellers.phone               -- ← AGREGAR ESTE CAMPO si no existe. Número de WhatsApp del vendedor en formato internacional (ej: 598XXXXXXXXX)
```

**IMPORTANTE:** El campo `sellers.phone` (o equivalente) es necesario para generar el link `wa.me` que conecta al comprador con el vendedor. Si no existe, creá la migración para agregarlo.

---

## Feed XML – Agregar autenticación y campo de teléfono del vendedor

### Autenticación del feed

Meta Commerce Manager permite configurar usuario y contraseña para acceder al feed. Implementar **HTTP Basic Authentication** en el endpoint del feed:

```php
// Al inicio del archivo que sirve el feed XML
$expected_user = 'meta_feed_user';     // Configurar en config
$expected_pass = 'contraseña_segura';  // Configurar en config

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $expected_user || 
    $_SERVER['PHP_AUTH_PW'] !== $expected_pass) {
    header('WWW-Authenticate: Basic realm="Product Feed"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Acceso denegado');
}
```

### Agregar teléfono del vendedor al feed (campo personalizado)

Meta no expone campos custom del feed en WhatsApp. El teléfono del vendedor **NO se agrega al feed XML**. Se obtiene directamente de la base de datos cuando el bot lo necesita. No modifiques el feed XML para este propósito.

---

## Máquina de estados – Flujo del IVR

### Estados y transiciones

```
WELCOME (estado inicial / primer mensaje o "volver al inicio")
  → El cliente envía cualquier mensaje
  → Responder con mensaje de bienvenida + 2 reply buttons:
      [🌐 Ver en la web]  [📱 Ver en WhatsApp]
  → Si elige "Ver en la web" → Enviar mensaje CTA URL a https://tiendamoroni.com → Volver a WELCOME
  → Si elige "Ver en WhatsApp" → Ir a BROWSE_MENU

BROWSE_MENU
  → Enviar interactive list con 3 opciones + opción de volver:
      - 📂 Ver por categoría
      - 👤 Ver por vendedor
      - 🔍 Buscar producto
      - ↩️ Volver al inicio
  → Si elige "Ver por categoría" → Ir a SELECT_CATEGORY
  → Si elige "Ver por vendedor" → Ir a SELECT_SELLER
  → Si elige "Buscar producto" → Ir a SEARCH_PROMPT
  → Si elige "Volver al inicio" → Ir a WELCOME

SELECT_CATEGORY
  → Consultar categorías de la BD (solo las que tengan productos activos)
  → Enviar interactive list con las categorías disponibles + opción "↩️ Volver"
  → LÍMITE: Las interactive lists soportan máximo 10 rows en total. Si hay más de 9 categorías (reservando 1 row para "Volver"), implementar PAGINACIÓN:
      - Rows 1-8: categorías de la página actual
      - Row 9: "➡️ Ver más categorías"
      - Row 10: "↩️ Volver"
      - Guardar página actual en context_data
  → Al seleccionar una categoría → Guardar category_id en context_data → Ir a SHOW_PRODUCTS

SELECT_SELLER
  → Consultar vendedores de la BD (solo los que tengan productos activos)
  → Enviar interactive list con los vendedores disponibles + opción "↩️ Volver"
  → Misma lógica de paginación que SELECT_CATEGORY si hay más de 9 vendedores
  → Al seleccionar un vendedor → Guardar seller_id en context_data → Ir a SHOW_PRODUCTS

SEARCH_PROMPT
  → Enviar mensaje de texto: "Escribí el nombre o palabra clave del producto que buscás:"
  → El siguiente mensaje de texto que envíe el usuario se interpreta como término de búsqueda
  → Buscar en la BD: WHERE products.name LIKE '%termino%' OR products.description LIKE '%termino%' AND products.status = 'active'
  → Si hay resultados → Guardar search_term en context_data → Ir a SHOW_PRODUCTS
  → Si NO hay resultados → Enviar: "No encontré productos con ese término. Intentá con otra palabra." + reply buttons [🔍 Buscar de nuevo] [↩️ Volver al menú]

SHOW_PRODUCTS
  → Obtener productos según el filtro activo (por categoría, vendedor o búsqueda)
  → Enviar un mensaje de tipo **product_list** (multi-product message) con los productos agrupados en secciones:
      - Usar el catalog_id del catálogo de Meta Commerce Manager
      - Cada producto se referencia por su product_retailer_id (campo content_id en la BD)
      - LÍMITE: Máximo 30 productos por mensaje product_list y máximo 10 secciones
      - Si hay más de 30 productos, paginar con botones "Ver más" / "Volver"
  → IMPORTANTE sobre product_list: WhatsApp muestra los productos con imagen, nombre, precio y descripción automáticamente tomándolos del catálogo de Meta. El cliente puede ver el detalle de cada producto y agregarlo al carrito nativo de WhatsApp.
  → Después del product_list, enviar un segundo mensaje con reply buttons:
      [↩️ Volver al menú]  [🔍 Nueva búsqueda]
  → Si el producto se referencia con un mensaje individual (single product), después de la interacción del usuario con el producto, enviar:
      - "¿Te interesa este producto? Contactá al vendedor: https://wa.me/598XXXXXXXXX?text=Hola!%20Vi%20[nombre_producto]%20en%20TiendaMoroni%20y%20me%20interesa"
      - Registrar el pedido/interés en la tabla de pedidos del sistema

PRODUCT_INTEREST (cuando el usuario indica interés en un producto)
  → Crear registro de pedido en el sistema (tabla orders o equivalente)
  → Enviar mensaje con link wa.me al vendedor correspondiente
  → Enviar reply buttons: [📂 Seguir comprando] [↩️ Volver al inicio]
```

### Detección de "interés en un producto"

Cuando el cliente agrega un producto al carrito de WhatsApp o envía el carrito como mensaje (order message), la Cloud API envía un webhook con `type: "order"`. Capturá ese evento para registrar el pedido y redirigir al vendedor.

Si no se genera un order message (porque el cliente simplemente mira), el flujo sigue con los reply buttons del SHOW_PRODUCTS.

---

## Especificaciones técnicas de la WhatsApp Cloud API

### Verificación del webhook (GET)

Cuando configurás el webhook en la Meta App, Meta envía un GET con estos parámetros:

```
GET /whatsapp/webhook.php?hub.mode=subscribe&hub.verify_token=TU_TOKEN&hub.challenge=CHALLENGE_STRING
```

Tu endpoint debe:
1. Verificar que `hub.mode` sea `subscribe`
2. Verificar que `hub.verify_token` coincida con tu token configurado
3. Responder con el valor de `hub.challenge` como body (200 OK, content-type text/plain)
4. Si la verificación falla, responder 403

### Recepción de mensajes (POST)

Meta envía un POST con el payload del mensaje. Tu endpoint debe:
1. Responder **inmediatamente** con `200 OK` (Meta espera respuesta en menos de 5 segundos, si falla 5 veces seguidas desactiva el webhook)
2. Procesar el mensaje de forma asíncrona o rápida después del response

### Estructura del payload entrante (webhook POST)

```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "TU_NUMERO",
          "phone_number_id": "TU_PHONE_NUMBER_ID"
        },
        "contacts": [{ "profile": { "name": "Nombre del cliente" }, "wa_id": "598XXXXXXXXX" }],
        "messages": [{
          "from": "598XXXXXXXXX",
          "id": "wamid.XXXX",
          "timestamp": "1234567890",
          "type": "text",
          "text": { "body": "Hola" }
        }]
      },
      "field": "messages"
    }]
  }]
}
```

**Para interactive reply buttons**, el mensaje viene así:
```json
{
  "type": "interactive",
  "interactive": {
    "type": "button_reply",
    "button_reply": {
      "id": "btn_ver_whatsapp",
      "title": "Ver en WhatsApp"
    }
  }
}
```

**Para interactive list replies**, el mensaje viene así:
```json
{
  "type": "interactive",
  "interactive": {
    "type": "list_reply",
    "list_reply": {
      "id": "cat_3",
      "title": "Tapas para libros",
      "description": "6 productos"
    }
  }
}
```

**Para order messages** (cuando el cliente envía su carrito):
```json
{
  "type": "order",
  "order": {
    "catalog_id": "CATALOG_ID",
    "product_items": [
      {
        "product_retailer_id": "SKU_001",
        "quantity": 1,
        "item_price": 180,
        "currency": "UYU"
      }
    ]
  }
}
```

### Envío de mensajes – Tipos que necesitás

Todos los envíos se hacen con POST a:
```
https://graph.facebook.com/v21.0/{PHONE_NUMBER_ID}/messages
```

Headers:
```
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json
```

#### 1. Mensaje de texto simple

```json
{
  "messaging_product": "whatsapp",
  "to": "598XXXXXXXXX",
  "type": "text",
  "text": { "body": "Texto del mensaje" }
}
```

#### 2. Reply buttons (máximo 3 botones, título máximo 20 caracteres)

```json
{
  "messaging_product": "whatsapp",
  "to": "598XXXXXXXXX",
  "type": "interactive",
  "interactive": {
    "type": "button",
    "body": { "text": "Texto del cuerpo del mensaje" },
    "action": {
      "buttons": [
        { "type": "reply", "reply": { "id": "btn_id_1", "title": "Texto botón 1" } },
        { "type": "reply", "reply": { "id": "btn_id_2", "title": "Texto botón 2" } }
      ]
    }
  }
}
```

#### 3. Interactive list (máximo 10 rows TOTAL entre todas las secciones, título de row máximo 24 caracteres, descripción máximo 72 caracteres)

```json
{
  "messaging_product": "whatsapp",
  "to": "598XXXXXXXXX",
  "type": "interactive",
  "interactive": {
    "type": "list",
    "header": { "type": "text", "text": "Texto del header (max 60 chars)" },
    "body": { "text": "Texto del body" },
    "footer": { "text": "Texto del footer (max 60 chars)" },
    "action": {
      "button": "Texto del botón que abre la lista (max 20 chars)",
      "sections": [
        {
          "title": "Título sección (max 24 chars)",
          "rows": [
            { "id": "row_id_1", "title": "Título (max 24ch)", "description": "Descripción (max 72ch)" },
            { "id": "row_id_2", "title": "Título (max 24ch)", "description": "Descripción (max 72ch)" }
          ]
        }
      ]
    }
  }
}
```

#### 4. Multi-product message / Product list (máximo 30 productos, máximo 10 secciones)

```json
{
  "messaging_product": "whatsapp",
  "to": "598XXXXXXXXX",
  "type": "interactive",
  "interactive": {
    "type": "product_list",
    "header": { "type": "text", "text": "Nuestros productos" },
    "body": { "text": "Mirá lo que tenemos para vos:" },
    "footer": { "text": "TiendaMoroni - Productos únicos para tu fe" },
    "action": {
      "catalog_id": "TU_CATALOG_ID",
      "sections": [
        {
          "title": "Nombre categoría",
          "product_items": [
            { "product_retailer_id": "CONTENT_ID_1" },
            { "product_retailer_id": "CONTENT_ID_2" }
          ]
        }
      ]
    }
  }
}
```

**IMPORTANTE:** Los product_retailer_id deben coincidir exactamente con los IDs de los productos en el catálogo de Meta Commerce Manager. En el feed XML esto corresponde a la etiqueta `<g:id>` de cada item.

#### 5. CTA URL button (para redirigir a la web)

```json
{
  "messaging_product": "whatsapp",
  "to": "598XXXXXXXXX",
  "type": "interactive",
  "interactive": {
    "type": "cta_url",
    "body": { "text": "Visitá nuestra web para ver todos los productos con fotos en alta calidad." },
    "action": {
      "name": "cta_url",
      "parameters": {
        "display_text": "Ir a TiendaMoroni.com",
        "url": "https://tiendamoroni.com"
      }
    }
  }
}
```

---

## Consideraciones de escalabilidad

1. **Paginación en listas:** Las interactive lists tienen un máximo de 10 rows. Implementar paginación basada en `context_data` para categorías, vendedores y resultados de búsqueda que excedan ese límite.

2. **Paginación en product_list:** El product_list soporta máximo 30 productos. Si una categoría o vendedor tiene más de 30, paginar.

3. **Timeout de conversación:** Si pasan más de 30 minutos sin interacción, resetear el estado a WELCOME. Verificar esto al recibir cada mensaje comparando `last_interaction`.

4. **Concurrencia:** Múltiples clientes pueden escribir al mismo tiempo. Cada conversación se identifica por `phone_number` y tiene su propio estado. Usar transacciones SQL donde sea necesario.

5. **Rate limiting:** La Cloud API permite 80 mensajes por segundo como base. No debería ser un problema con el volumen actual, pero no enviar mensajes en loops sin control.

6. **El access token expira.** Para producción, usá un **System User Token** (permanent token) generado desde el Business Manager en vez del token temporal del panel de desarrolladores. Esto se configura en Meta Business Suite → Configuración del negocio → Usuarios del sistema.

---

## Expiración de la ventana de 24 horas

Los mensajes interactivos (reply buttons, lists, product_list) **solo se pueden enviar dentro de las 24 horas posteriores al último mensaje del cliente**. Si el cliente no escribe nada en 24 horas y vos querés contactarlo, necesitás usar un **template message** pre-aprobado por Meta. Para este MVP, no es necesario implementar templates ya que el bot solo responde cuando el cliente escribe primero.

---

## Seguridad

1. **Validar la firma del webhook:** Meta envía un header `X-Hub-Signature-256` con cada POST. Validar el HMAC-SHA256 del body contra tu app secret:

```php
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
```

2. **No exponer tokens en el código.** Usar un archivo `config.php` fuera del document root o con protección `.htaccess`.

3. **Sanitizar inputs:** Todo texto que venga del usuario debe sanitizarse antes de usarse en queries SQL (usar prepared statements) o en mensajes de respuesta.

---

## Configuración necesaria (config.php)

```php
<?php
return [
    'whatsapp' => [
        'verify_token'    => 'TU_VERIFY_TOKEN_PERSONALIZADO',    // Lo definís vos, cualquier string
        'access_token'    => 'TU_ACCESS_TOKEN_PERMANENTE',       // System User Token
        'phone_number_id' => 'TU_PHONE_NUMBER_ID',               // Del panel de Meta
        'app_secret'      => 'TU_APP_SECRET',                    // Para validar firma del webhook
        'catalog_id'      => 'TU_CATALOG_ID',                    // ID del catálogo en Commerce Manager
        'api_version'     => 'v21.0',                            // Versión de la Graph API
    ],
    'app' => [
        'base_url'             => 'https://tiendamoroni.com',
        'conversation_timeout' => 1800,  // 30 minutos en segundos
    ],
    'db' => [
        'host'     => 'localhost',
        'name'     => 'tu_base_de_datos',
        'user'     => 'tu_usuario',
        'password' => 'tu_contraseña',
        'charset'  => 'utf8mb4',
    ],
    'feed' => [
        'username' => 'meta_feed_user',
        'password' => 'contraseña_segura_del_feed',
    ],
];
```

---

## Entregables esperados

1. **Todos los archivos PHP** de la estructura definida arriba, completos y funcionales.
2. **El archivo SQL** con las migraciones (tablas nuevas + ALTER TABLE si es necesario).
3. **Un archivo .htaccess** para proteger `config.php` y el directorio de logs.
4. **Instrucciones breves** en un README.md sobre:
   - Cómo configurar el webhook en el panel de Meta Developers
   - Cómo obtener el Phone Number ID y el Catalog ID
   - Cómo generar el System User Token permanente
   - Cómo migrar el número de WhatsApp Business de la app a la Cloud API

---

## Restricciones y reglas

- **NO uses frameworks ni librerías externas.** Todo PHP nativo.
- **NO uses Composer.** No hay acceso a `composer install` en el hosting cPanel.
- **Respondé siempre 200 OK rápido** en el webhook antes de procesar. Si el procesamiento es pesado, considerá usar `fastcgi_finish_request()` o `ignore_user_abort(true)` + `ob_end_flush()` para responder rápido y seguir procesando.
- **Respetá estrictamente los límites de caracteres** de la API de WhatsApp (títulos de botón: 20 chars, títulos de row: 24 chars, descripciones de row: 72 chars, etc.). Truncá con `mb_substr()` si es necesario.
- **Usá prepared statements** para todas las queries SQL.
- **Logeá todo:** mensajes entrantes, mensajes salientes, errores de API. Esto es crítico para debugging.
- **El código debe estar bien comentado** en español.
- **Usá emojis con moderación** en los mensajes al cliente. Uno por opción está bien, no más.
