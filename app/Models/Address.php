<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Address extends Model
{
    protected static string $table = 'addresses';

    public static function allGroupedByClient(): array
    {
        $stmt = DB::conn()->query('SELECT * FROM addresses ORDER BY client_id, id');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['client_id']][] = $row;
        }
        return $grouped;
    }
}
