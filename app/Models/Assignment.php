<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Assignment extends Model
{
    protected static string $table = 'assignments';

    public static function withUsersForOrder(int $orderId): array
    {
        $stmt = DB::conn()->prepare('SELECT a.*, u.name AS user_name, u.role FROM assignments a JOIN users u ON u.id = a.user_id WHERE a.order_id = :order');
        $stmt->execute([':order' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function deleteForOrder(int $orderId): void
    {
        $stmt = DB::conn()->prepare('DELETE FROM assignments WHERE order_id = :order');
        $stmt->execute([':order' => $orderId]);
    }
}
