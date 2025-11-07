<?php
namespace App\Models;

use App\Core\DB;
use PDO;

abstract class Model
{
    protected static string $table;

    public static function all(): array
    {
        $stmt = DB::conn()->query('SELECT * FROM ' . static::$table);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function find(int $id): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $attributes): int
    {
        $columns = array_keys($attributes);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = 'INSERT INTO ' . static::$table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($attributes);
        return (int)DB::conn()->lastInsertId();
    }

    public static function update(int $id, array $attributes): void
    {
        $set = [];
        foreach ($attributes as $column => $value) {
            $set[] = $column . ' = :' . $column;
        }
        $sql = 'UPDATE ' . static::$table . ' SET ' . implode(',', $set) . ' WHERE id = :id';
        $attributes['id'] = $id;
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($attributes);
    }
}
