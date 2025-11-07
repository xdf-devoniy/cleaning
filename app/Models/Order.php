<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Order extends Model
{
    protected static string $table = 'orders';

    public static function forDate(string $date): array
    {
        $stmt = DB::conn()->prepare("SELECT o.*, c.name AS client_name, a.label AS address_label
            FROM orders o
            JOIN clients c ON c.id = o.client_id
            JOIN addresses a ON a.id = o.address_id
            WHERE date(o.scheduled_at) = :date
            ORDER BY o.scheduled_at");
        $stmt->execute([':date' => $date]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($orders as &$order) {
            $order['items'] = OrderItem::forOrder((int)$order['id']);
            $order['assignments'] = Assignment::withUsersForOrder((int)$order['id']);
            $order['invoice'] = Invoice::findByOrder((int)$order['id']);
            $order['payments_total'] = Payment::totalForOrder((int)$order['id']);
        }
        return $orders;
    }

    public static function findDetailed(int $id): ?array
    {
        $stmt = DB::conn()->prepare("SELECT o.*, c.name AS client_name, a.label AS address_label
            FROM orders o
            JOIN clients c ON c.id = o.client_id
            JOIN addresses a ON a.id = o.address_id
            WHERE o.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }
        $order['items'] = OrderItem::forOrder((int)$order['id']);
        $order['assignments'] = Assignment::withUsersForOrder((int)$order['id']);
        $order['invoice'] = Invoice::findByOrder((int)$order['id']);
        $order['payments_total'] = Payment::totalForOrder((int)$order['id']);
        return $order;
    }
}
