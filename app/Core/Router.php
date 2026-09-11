<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router mínimo con patrones {param}. Resuelve el primer patrón que coincide
 * con el método y la ruta; si la ruta existe pero el método no, responde 405.
 */
final class Router
{
    /** @var array<int,array{method:string,regex:string,params:array<int,string>,handler:callable|array}> */
    private array $routes = [];

    public function get(string $path, $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): void   { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, $handler): void
    {
        $params = [];
        $regex  = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '([^/]+)';
            },
            $path
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($m);
            $args = [];
            foreach ($route['params'] as $i => $nameParam) {
                $args[$nameParam] = urldecode($m[$i] ?? '');
            }

            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->$action($args);
                return;
            }
            $handler($args);
            return;
        }

        if ($pathMatched) {
            http_response_code(405);
            View::render('errors/error', ['code' => 405, 'title' => 'Método no permitido',
                'message' => 'La acción solicitada no admite este método.'], 'Método no permitido');
            return;
        }

        http_response_code(404);
        View::render('errors/error', ['code' => 404, 'title' => 'Página no encontrada',
            'message' => 'La dirección que buscas no existe o fue movida.'], 'No encontrado');
    }
}
