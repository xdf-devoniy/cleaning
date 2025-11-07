<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function byRoles(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = DB::conn()->prepare("SELECT * FROM users WHERE role IN ($placeholders) ORDER BY name");
        $stmt->execute($roles);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
