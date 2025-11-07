<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class OrderItem extends Model
{
    protected static string $table = 'order_items';

    public static function forOrder(int $orderId): array
    {
        $stmt = DB::conn()->prepare('SELECT oi.*, s.name AS service_name FROM order_items oi JOIN services s ON s.id = oi.service_id WHERE oi.order_id = :order');
        $stmt->execute([':order' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
