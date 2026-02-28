<?php
declare(strict_types=1);

/**
 * Global helper functions for TiendaMoroni.
 */

/** Redirect to a URL and exit. */
function redirect(string $url): never
{
    header('Location: ' . SITE_URL . $url);
    exit;
}

/** Redirect to an absolute URL and exit. */
function redirectAbsolute(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** HTML-escape a value. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format a price as Uruguayan pesos. */
function formatPrice(float|string $price): string
{
    return '$ ' . number_format((float) $price, 0, ',', '.');
}

/** Generate a URL slug from a string. */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $map  = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
        'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
        'â'=>'a','ê'=>'e','î'=>'i','ô'=>'o','û'=>'u',
        'ã'=>'a','õ'=>'o','ñ'=>'n','ç'=>'c',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

/** Return the current full URL. */
function currentUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/** Return the request path without query string. */
function requestPath(): string
{
    return strtok($_SERVER['REQUEST_URI'], '?');
}

/** Truncate text to a given length, appending an ellipsis. */
function truncate(string $text, int $length = 120): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

/** Sanitize a string (trim + strip tags). */
function sanitize(string $input): string
{
    return strip_tags(trim($input));
}

/** Check if a POST request is being made. */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Get POST value safely. */
function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

/** Get GET value safely. */
function get(string $key, mixed $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}

/** Render a JSON response and exit. */
function jsonResponse(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Generate a CSRF token and store it in session. */
function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify CSRF token from POST. */
function verifyCsrf(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $submitted = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!$stored || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        // Return JSON for AJAX requests so the client can show a proper alert
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                  str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor recargá la página e intentá de nuevo.']);
            exit;
        }
        die('Token de seguridad inválido. Por favor recargá la página e intentá de nuevo.');
    }
}

/** Render a view file with output buffering and optional layout. */
function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $path = dirname(__DIR__) . '/Views/' . $template . '.php';
    if (!file_exists($path)) {
        throw new \RuntimeException("View not found: $template");
    }

    // Capture the inner view
    ob_start();
    require $path;
    $content = ob_get_clean();

    // Determine layout — set inside view via $layout = 'layout/xxx'.
    // If the view (or partial) doesn't set $layout, render with no layout.
    $layoutName = $layout ?? null;

    if ($layoutName !== null) {
        $layoutPath = dirname(__DIR__) . '/Views/' . $layoutName . '.php';
        if (file_exists($layoutPath)) {
            // Pass all original data + $content to the layout
            extract($data, EXTR_SKIP);
            require $layoutPath;
            return;
        }
    }

    echo $content;
}

/** Render a partial (no layout). */
function partial(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    $path = dirname(__DIR__) . '/Views/' . $template . '.php';
    if (!file_exists($path)) return '';
    ob_start();
    require $path;
    return ob_get_clean();
}

/** Simple pagination helper. Returns array with offset, page, total_pages, etc. */
function paginate(int $total, int $perPage = 20, string $paramName = 'page'): array
{
    $page      = max(1, (int) ($_GET[$paramName] ?? 1));
    $totalPages = (int) ceil($total / $perPage);
    $page      = min($page, max(1, $totalPages));
    $offset    = ($page - 1) * $perPage;

    return [
        'page'        => $page,
        'per_page'    => $perPage,
        'total'       => $total,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}
