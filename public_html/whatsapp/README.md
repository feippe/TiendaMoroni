# WhatsApp Bot – TiendaMoroni

Bot IVR para WhatsApp Business Cloud API. Permite a los clientes explorar el
catálogo de productos, filtrar por categoría o vendedor, buscar productos y
contactar artesanos directamente desde WhatsApp.

## Estructura

```
public_html/whatsapp/
├── webhook.php           ← Endpoint público (GET verificación / POST mensajes)
├── config.php            ← Credenciales y constantes (PROTEGIDO por .htaccess)
├── WhatsAppAPI.php       ← Cliente Cloud API (text, buttons, list, product_list, CTA)
├── ConversationManager.php ← Máquina de estados (tabla wa_conversations)
├── MessageRouter.php     ← Lógica del bot IVR (estados y transiciones)
├── ProductService.php    ← Queries BD: productos, categorías, vendedores
├── OrderService.php      ← Registro de pedidos WhatsApp (tabla wa_orders)
├── Logger.php            ← Log de mensajes en BD + archivos mensuales
├── .htaccess             ← Protección de archivos sensibles
├── logs/                 ← Logs de errores y actividad (protegido por .htaccess)
└── sql/
    └── migrations.sql    ← Crear tablas wa_conversations, wa_message_log, wa_orders
```

---

## 1. Ejecutar migraciones SQL

Importar `sql/migrations.sql` en la base de datos del proyecto:

```bash
mysql -u USUARIO -p NOMBRE_BD < public_html/whatsapp/sql/migrations.sql
```

O desde phpMyAdmin: Importar → seleccionar `migrations.sql`.

---

## 2. Configurar credenciales

Editar `public_html/whatsapp/config.php` con los valores reales:

```php
define('WA_VERIFY_TOKEN',    'elegí_un_string_secreto_cualquiera');
define('WA_ACCESS_TOKEN',    'TOKEN_PERMANENTE_DE_SYSTEM_USER');
define('WA_PHONE_NUMBER_ID', '123456789012345');
define('WA_APP_SECRET',      'abc123...');
define('WA_CATALOG_ID',      '987654321098765');
```

---

## 3. Obtener Phone Number ID y App Secret

1. Ir a [Meta for Developers](https://developers.facebook.com/)
2. Seleccionar tu aplicación → **WhatsApp** → **Configuración de API**
3. Copiar **Phone Number ID** (no es el número de teléfono, es el ID)
4. Ir a **Configuración** → **Información básica** → **Secreto de la aplicación**

---

## 4. Obtener el Catalog ID

1. Ir a [Meta Commerce Manager](https://business.facebook.com/commerce/)
2. Seleccionar tu catálogo
3. Ir a **Configuración del catálogo** → copiar el **ID del catálogo**

---

## 5. Generar System User Token (token permanente)

> Los tokens del panel de desarrolladores expiran. En producción siempre usar
> un System User Token que no expira.

1. Ir a **Meta Business Suite** → [Configuración del negocio](https://business.facebook.com/settings/)
2. **Usuarios** → **Usuarios del sistema** → **Agregar**
3. Crear usuario con rol **Administrador**
4. Clic en el usuario → **Generar nuevo token**
5. Seleccionar tu App → Permisos requeridos:
   - `whatsapp_business_messaging`
   - `whatsapp_business_management`
6. Copiar el token generado → pegar en `WA_ACCESS_TOKEN` del config

---

## 6. Registrar el webhook en Meta Developers

1. Meta for Developers → tu App → **WhatsApp** → **Configuración**
2. En **Webhook**, clic en **Configurar**
3. Completar:
   - **URL de devolución de llamada:** `https://tiendamoroni.com/whatsapp/webhook.php`
   - **Token de verificación:** el mismo valor de `WA_VERIFY_TOKEN` en config.php
4. Clic en **Verificar y guardar** (Meta hará un GET al webhook y verificará el token)
5. En **Campos de webhook**, suscribirse a: **messages**

---

## 7. Migrar número de WhatsApp Business a Cloud API

Si el número está en la app de WhatsApp Business (no en Cloud API):

1. Meta for Developers → tu App → **WhatsApp** → **Números de teléfono**
2. Clic en **Agregar número de teléfono**
3. Seguir el asistente para verificar el número con OTP por SMS o llamada
4. Una vez verificado, el número queda disponible para la Cloud API
5. **Importante:** El número NO puede estar activo en la app de WhatsApp Business
   al mismo tiempo que en la Cloud API. Hay que desregistrarlo primero.

---

## 8. Configurar autenticación del feed XML en Commerce Manager

1. Commerce Manager → tu catálogo → **Fuentes de datos** → editar el feed
2. Activar **Autenticación HTTP básica**
3. Usuario: `meta_feed_user` (valor de `WA_FEED_USER`)
4. Contraseña: el valor de `WA_FEED_PASS` configurado

---

## Flujo del bot

```
Cliente escribe → WELCOME
  [Ver en la web]       → CTA URL → https://tiendamoroni.com
  [Ver en WhatsApp]     → BROWSE_MENU

BROWSE_MENU
  [Ver por categoría]   → SELECT_CATEGORY (lista paginada)
  [Ver por vendedor]    → SELECT_SELLER   (lista paginada)
  [Buscar producto]     → SEARCH_PROMPT   (texto libre)
  [Volver al inicio]   → WELCOME

SELECT_CATEGORY → elige categoría → SHOW_PRODUCTS
SELECT_SELLER   → elige vendedor  → SHOW_PRODUCTS
SEARCH_PROMPT   → escribe texto   → SHOW_PRODUCTS

SHOW_PRODUCTS → product_list con productos del catálogo Meta
  Cliente agrega al carrito y envía → order message → PRODUCT_INTEREST
  → Bot envía link wa.me del vendedor + registra en wa_orders
```

---

## Debugging

Los logs se guardan en `logs/` (protegidos de acceso público):
- `YYYY-MM-bot.log` — actividad general
- `YYYY-MM-errors.log` — errores y excepciones

Los mensajes también se registran en la tabla `wa_message_log`:
```sql
SELECT * FROM wa_message_log ORDER BY created_at DESC LIMIT 50;
```

Para ver el estado de conversaciones activas:
```sql
SELECT * FROM wa_conversations ORDER BY last_interaction DESC;
```
