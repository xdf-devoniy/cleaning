<?php
namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../Config/config.php';
            $path = $config['db_path'];
            $dsn = 'sqlite:' . $path;
            try {
                self::$pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$pdo;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::conn();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function migrate(): void
    {
        $pdo = self::conn();
        $migrationsDir = __DIR__ . '/../../database/migrations';
        $applied = [];
        if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'")->fetchColumn() === false) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT UNIQUE, applied_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        } else {
            $stmt = $pdo->query('SELECT filename FROM migrations');
            $applied = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        }

        $files = glob($migrationsDir . '/*.sql');
        sort($files);
        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $applied, true)) {
                continue;
            }
            $sql = file_get_contents($file);
            $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $stmt->execute([$filename]);
        }
    }
}
