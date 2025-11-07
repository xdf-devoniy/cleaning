<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function register(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function dispatch(string $method, string $path): Response
    {
        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($this->match($routeMethod, $routePath, $method, $path, $params)) {
                return $this->invoke($handler, $params);
            }
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    private function match(string $routeMethod, string $routePath, string $method, string $path, ?array &$params): bool
    {
        if ($routeMethod !== $method) {
            return false;
        }

        $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $routePath);
        if ($pattern === null) {
            return false;
        }
        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    private function invoke(callable|array $handler, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $result = $instance->$method($params);
        } else {
            $result = $handler($params);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }
}
