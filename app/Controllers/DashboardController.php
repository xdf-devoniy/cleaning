<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Response};
use App\Models\Order;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function index(): Response
    {
        if (!Auth::instance()->authorize('dashboard.view')) {
            return $this->redirect('/login');
        }

        $ordersToday = array_filter(Order::all(), function (array $order) {
            return str_starts_with($order['scheduled_at'] ?? '', date('Y-m-d'));
        });
        $payments = Payment::all();
        $todayRevenue = array_sum(array_map(fn($p) => $p['amount'] ?? 0, $payments));

        return $this->view('dashboard/index', [
            'ordersToday' => count($ordersToday),
            'todayRevenue' => $todayRevenue,
        ]);
    }
}
