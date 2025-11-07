<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Client extends Model
{
    protected static string $table = 'clients';

    public static function withStats(): array
    {
        $sql = "SELECT c.*, COALESCE(SUM(o.total),0) AS lifetime_value, MAX(o.scheduled_at) AS last_order_at
                FROM clients c
                LEFT JOIN orders o ON o.client_id = c.id
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        $stmt = DB::conn()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
