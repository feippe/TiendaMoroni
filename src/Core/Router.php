<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip query string
        $uri = strtok($uri, '?');
        // Remove trailing slash (except root)
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = $this->patternToRegex($route['pattern']);

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );

                $this->call($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        $this->call404();
    }

    private function patternToRegex(string $pattern): string
    {
        // Convert :param and {param} to named capture groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $regex);
        return '#^' . $regex . '$#';
    }

    private function call(callable|array $handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method($params);
        } else {
            $handler($params);
        }
    }

    private function call404(): void
    {
        $viewPath = dirname(__DIR__) . '/Views/errors/404.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>404 – Página no encontrada</h1>';
        }
    }
}
