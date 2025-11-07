<?php
namespace App\Controllers;

use App\Core\{Controller, DB, Response, Validator};
use App\Models\{Invoice, Order, Payment};

class PaymentsController extends Controller
{
    public function store(): Response
    {
        if ($response = $this->requireAuth('finance.manage')) {
            return $response;
        }

        $validator = new Validator();
        $rules = [
            'order_id' => 'required',
            'amount' => 'required',
            'method' => 'required',
        ];
        if (!$validator->validate($_POST, $rules)) {
            return $this->redirectWith('/orders', ['error' => 'To\'lov ma\'lumotlarini to\'ldiring']);
        }

        $orderId = (int)$_POST['order_id'];
        $amount = (int)$_POST['amount'];
        if ($amount <= 0) {
            return $this->redirectWith('/orders', ['error' => 'To\'lov summasini kiriting']);
        }
        $method = $_POST['method'];
        $invoiceId = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
        $txnRef = $_POST['txn_ref'] ?? null;
        $receivedAt = $_POST['received_at'] ?? date('Y-m-d H:i:s');

        $order = Order::find($orderId);
        if (!$order) {
            return $this->redirectWith('/orders', ['error' => 'Buyurtma topilmadi']);
        }

        DB::transaction(function () use ($orderId, $invoiceId, $amount, $method, $txnRef, $receivedAt, $order) {
            $invoice = $invoiceId ? Invoice::find($invoiceId) : Invoice::findByOrder($orderId);
            $invoiceIdToUse = $invoice['id'] ?? null;
            Payment::create([
                'order_id' => $orderId,
                'invoice_id' => $invoiceIdToUse,
                'amount' => $amount,
                'method' => $method,
                'txn_ref' => $txnRef,
                'received_at' => $receivedAt,
            ]);

            if ($invoiceIdToUse) {
                $totalPaid = Payment::totalForInvoice($invoiceIdToUse);
                if ($totalPaid >= (int)$invoice['total']) {
                    Invoice::update($invoiceIdToUse, ['status' => 'paid', 'paid_at' => $receivedAt]);
                }
            }

            $totalOrderPaid = Payment::totalForOrder($orderId);
            if ($totalOrderPaid >= (int)$order['total']) {
                Order::update($orderId, ['status' => 'paid', 'paid_at' => $receivedAt, 'updated_at' => $receivedAt]);
            }
        });

        return $this->redirectWith('/orders', ['success' => 'To\'lov qayd etildi']);
    }
}
