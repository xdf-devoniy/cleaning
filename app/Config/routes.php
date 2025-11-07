<?php
use App\Controllers;

return [
    ['GET', '/', [Controllers\DashboardController::class, 'index']],
    ['GET', '/login', [Controllers\AuthController::class, 'showLogin']],
    ['POST', '/login', [Controllers\AuthController::class, 'login']],
    ['POST', '/logout', [Controllers\AuthController::class, 'logout']],

    ['GET', '/clients', [Controllers\ClientsController::class, 'index']],
    ['POST', '/clients', [Controllers\ClientsController::class, 'store']],

    ['GET', '/orders', [Controllers\OrdersController::class, 'index']],
    ['POST', '/orders', [Controllers\OrdersController::class, 'store']],
    ['POST', '/orders/{id}/assign', [Controllers\OrdersController::class, 'assign']],
    ['POST', '/orders/{id}/status', [Controllers\OrdersController::class, 'updateStatus']],

    ['POST', '/invoices/{orderId}/issue', [Controllers\InvoicesController::class, 'issue']],
    ['POST', '/payments', [Controllers\PaymentsController::class, 'store']],

    ['GET', '/payroll', [Controllers\PayrollController::class, 'index']],
    ['POST', '/payroll/compute', [Controllers\PayrollController::class, 'compute']],
];
