<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Payment extends Model
{
    protected static string $table = 'payments';

    public static function totalForOrder(int $orderId): int
    {
        $stmt = DB::conn()->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE order_id = :order');
        $stmt->execute([':order' => $orderId]);
        return (int)$stmt->fetchColumn();
    }

    public static function totalForInvoice(int $invoiceId): int
    {
        $stmt = DB::conn()->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = :invoice');
        $stmt->execute([':invoice' => $invoiceId]);
        return (int)$stmt->fetchColumn();
    }
}
