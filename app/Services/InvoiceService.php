<?php
namespace App\Services;

use App\Core\DB;
use App\Models\{Invoice, Order};
use DateTimeImmutable;
use RuntimeException;

class InvoiceService
{
    public function issueForOrder(int $orderId): array
    {
        $order = Order::findDetailed($orderId);
        if (!$order) {
            throw new RuntimeException('Order not found');
        }

        if ($order['status'] === 'draft') {
            throw new RuntimeException('Order must be scheduled before invoicing');
        }

        $existing = Invoice::findByOrder($orderId);
        if ($existing) {
            return $existing;
        }

        $config = require __DIR__ . '/../Config/config.php';
        $prefix = $config['invoice_prefix'] ?? 'CLN';
        $now = new DateTimeImmutable('now');
        $monthKey = $now->format('Ym');

        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE strftime('%Y%m', issued_at) = :month");
        $stmt->execute([':month' => $now->format('Ym')]);
        $sequence = ((int)$stmt->fetchColumn()) + 1;
        $invoiceNo = sprintf('%s-%s-%04d', $prefix, $monthKey, $sequence);

        $issuedAt = $now->format('Y-m-d H:i:s');
        $dueAt = $now->modify('+7 days')->format('Y-m-d H:i:s');

        $invoiceId = Invoice::create([
            'order_id' => $orderId,
            'invoice_no' => $invoiceNo,
            'status' => 'issued',
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'total' => $order['total'],
        ]);

        return Invoice::find((int)$invoiceId) ?? [];
    }
}
