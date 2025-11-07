<?php
namespace App\Controllers;

use App\Core\{Controller, DB, Response, Validator};
use App\Models\{Address, Assignment, Client, Invoice, Order, OrderItem, Payment, Service, User};
use App\Services\{PricingService, SchedulerService};

class OrdersController extends Controller
{
    public function index(): Response
    {
        if ($response = $this->requireAuth('orders.manage')) {
            return $response;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $orders = Order::forDate($date);
        $clients = Client::all();
        $addresses = Address::all();
        $services = Service::active();
        $cleaners = User::byRoles(['cleaner', 'supervisor']);
        $flash = $this->consumeFlash();

        return $this->view('orders/index', [
            'date' => $date,
            'orders' => $orders,
            'clients' => $clients,
            'addresses' => $addresses,
            'services' => $services,
            'cleaners' => $cleaners,
            'flash' => $flash,
        ]);
    }

    public function store(): Response
    {
        if ($response = $this->requireAuth('orders.manage')) {
            return $response;
        }

        $validator = new Validator();
        $rules = [
            'client_id' => 'required',
            'address_id' => 'required',
            'service_id' => 'required',
            'qty' => 'required',
            'scheduled_at' => 'required',
        ];
        $dateParam = $_POST['date'] ?? date('Y-m-d');
        if (!$validator->validate($_POST, $rules)) {
            return $this->redirectWith('/orders?date=' . urlencode($dateParam), ['error' => 'Buyurtma ma\'lumotlarini tekshiring']);
        }

        $clientId = (int)$_POST['client_id'];
        $addressId = (int)$_POST['address_id'];
        $serviceId = (int)$_POST['service_id'];
        $qty = max(1, (int)$_POST['qty']);
        $scheduledAtInput = $_POST['scheduled_at'];
        $scheduledAt = date('Y-m-d H:i:s', strtotime($scheduledAtInput));
        $notes = $_POST['notes'] ?? null;
        $assigned = array_map('intval', $_POST['assigned'] ?? []);
        $service = Service::findActive($serviceId);
        if (!$service) {
            return $this->redirectWith('/orders', ['error' => 'Xizmat topilmadi']);
        }

        $duration = (int)$service['duration_min'] * $qty;
        $pricing = (new PricingService())->price([
            'scheduled_at' => $scheduledAt,
            'items' => [[
                'service_id' => $serviceId,
                'qty' => $qty,
                'unit_price' => $service['base_price'],
            ]],
        ]);

        $scheduler = new SchedulerService();
        $conflicts = $scheduler->detectConflicts([
            'scheduled_at' => $scheduledAt,
            'duration' => $duration,
            'assignments' => $assigned,
        ]);
        if (!empty($conflicts)) {
            return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($scheduledAt))), [
                'error' => 'Jadvalda mos kelmaslik bor',
            ]);
        }

        DB::transaction(function () use ($clientId, $addressId, $scheduledAt, $duration, $pricing, $assigned, $notes) {
            $orderId = Order::create([
                'client_id' => $clientId,
                'address_id' => $addressId,
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'duration' => $duration,
                'subtotal' => $pricing['subtotal'],
                'discount' => $pricing['discount'],
                'surcharge' => $pricing['surcharge'],
                'tax' => $pricing['tax'],
                'total' => $pricing['total'],
                'notes' => $notes,
            ]);

            foreach ($pricing['items'] as $item) {
                OrderItem::create([
                    'order_id' => $orderId,
                    'service_id' => $item['service_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            foreach ($assigned as $userId) {
                Assignment::create([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'role' => 'cleaner',
                    'planned_minutes' => $duration,
                ]);
            }
        });

        return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($scheduledAt))), ['success' => 'Buyurtma yaratildi']);
    }

    public function assign(string $id): Response
    {
        if ($response = $this->requireAuth('orders.manage')) {
            return $response;
        }

        $orderId = (int)$id;
        $assigned = array_map('intval', $_POST['assigned'] ?? []);
        $order = Order::findDetailed($orderId);
        if (!$order) {
            return $this->redirectWith('/orders', ['error' => 'Buyurtma topilmadi']);
        }

        $scheduler = new SchedulerService();
        $conflicts = $scheduler->detectConflicts([
            'scheduled_at' => $order['scheduled_at'],
            'duration' => (int)$order['duration'],
            'assignments' => $assigned,
            'order_id' => $orderId,
        ]);
        if (!empty($conflicts)) {
            return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), [
                'error' => 'Jadvalda mos kelmaslik bor',
            ]);
        }

        DB::transaction(function () use ($orderId, $assigned, $order) {
            Assignment::deleteForOrder($orderId);
            foreach ($assigned as $userId) {
                Assignment::create([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'role' => 'cleaner',
                    'planned_minutes' => $order['duration'],
                ]);
            }
        });

        return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['success' => 'Ijrochilar yangilandi']);
    }

    public function updateStatus(string $id): Response
    {
        if ($response = $this->requireAuth('orders.manage')) {
            return $response;
        }

        $orderId = (int)$id;
        $order = Order::findDetailed($orderId);
        if (!$order) {
            return $this->redirectWith('/orders', ['error' => 'Buyurtma topilmadi']);
        }

        $newStatus = $_POST['status'] ?? '';
        $allowed = [
            'draft' => ['scheduled'],
            'scheduled' => ['in_progress', 'completed', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => ['paid'],
            'paid' => [],
            'cancelled' => [],
        ];

        $currentStatus = $order['status'];
        if (!in_array($newStatus, $allowed[$currentStatus] ?? [], true)) {
            return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['error' => 'Ruxsat etilmagan holat']);
        }

        if ($newStatus === 'in_progress' && empty($order['assignments'])) {
            return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['error' => 'Avval ijrochilarni biriktiring']);
        }

        if ($newStatus === 'completed' && empty($order['assignments'])) {
            return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['error' => 'Ijrochilar biriktirilmagan']);
        }

        if ($newStatus === 'paid') {
            $invoice = Invoice::findByOrder($orderId);
            $payments = Payment::totalForOrder($orderId);
            if (!$invoice || $payments < (int)$order['total']) {
                return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['error' => 'To\'lov yetarli emas']);
            }
        }

        $update = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if ($newStatus === 'completed') {
            $update['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($newStatus === 'paid') {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }

        Order::update($orderId, $update);

        return $this->redirectWith('/orders?date=' . urlencode(date('Y-m-d', strtotime($order['scheduled_at']))), ['success' => 'Status yangilandi']);
    }
}
