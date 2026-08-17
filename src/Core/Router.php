<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $controllerAction): void
    {
        // Převod cesty s parametry (např. /api/shifts/{id}) na regulární výraz
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'controller' => $controllerAction[0],
            'action' => $controllerAction[1]
        ];
    }

    public function handle(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Odfiltrování pouze pojmenovaných parametrů z matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $controllerName = $route['controller'];
                $actionName = $route['action'];

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $actionName)) {
                        call_user_func_array([$controller, $actionName], [$params]);
                        return;
                    }
                }
                
                $this->sendJsonError("Metoda kontroleru nebyla nalezena", 500);
                return;
            }
        }

        $this->sendJsonError("Endpoint nebyl nalezen", 404);
    }

    private function sendJsonError(string $message, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}
