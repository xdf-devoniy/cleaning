<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Invoice extends Model
{
    protected static string $table = 'invoices';

    public static function findByOrder(int $orderId): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM invoices WHERE order_id = :order LIMIT 1');
        $stmt->execute([':order' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
