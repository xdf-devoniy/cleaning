<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class Service extends Model
{
    protected static string $table = 'services';

    public static function active(): array
    {
        $stmt = DB::conn()->query('SELECT * FROM services WHERE active = 1 ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function findActive(int $id): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM services WHERE id = :id AND active = 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
