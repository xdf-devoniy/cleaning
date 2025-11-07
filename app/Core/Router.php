<?php
namespace App\Core;

use ReflectionFunction;
use ReflectionMethod;

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
                return $this->invoke($handler, $params ?? []);
            }
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    private function match(string $routeMethod, string $routePath, string $method, string $path, ?array &$params): bool
    {
        if (strcasecmp($routeMethod, $method) !== 0) {
            return false;
        }

        $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $routePath);
        if ($pattern === null) {
            return false;
        }

        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $path, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    private function invoke(callable|array $handler, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $reflection = new ReflectionMethod($instance, $method);
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $params)) {
                    $arguments[] = $params[$name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                } else {
                    $arguments[] = null;
                }
            }
            $result = $reflection->invokeArgs($instance, $arguments);
        } else {
            $reflection = new ReflectionFunction($handler);
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                $name = $parameter->getName();
                $arguments[] = $params[$name] ?? null;
            }
            $result = $handler(...$arguments);
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
