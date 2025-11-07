<?php
use App\Controllers; 

return [
    ['GET', '/', [Controllers\DashboardController::class, 'index']],
    ['GET', '/login', [Controllers\AuthController::class, 'showLogin']],
    ['POST', '/login', [Controllers\AuthController::class, 'login']],
    ['POST', '/logout', [Controllers\AuthController::class, 'logout']],
];
