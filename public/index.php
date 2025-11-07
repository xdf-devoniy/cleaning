<?php
$config = require __DIR__ . '/../bootstrap.php';

use App\Core\{DB, Router};

DB::conn();

$router = new Router();
$routes = require __DIR__ . '/../app/Config/routes.php';
foreach ($routes as [$method, $path, $handler]) {
    $router->register($method, $path, $handler);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$response = $router->dispatch($method, $path);
$response->send();
