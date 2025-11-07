<?php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('App\\', '', $class);
        $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

$config = require __DIR__ . '/app/Config/config.php';

date_default_timezone_set($config['timezone'] ?? 'Asia/Tashkent');

return $config;
