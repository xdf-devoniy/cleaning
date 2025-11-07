<?php
namespace App\Core;

use App\Core\DB;
use PDO;

class Queue
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::conn();
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS queue_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT, payload TEXT, available_at INTEGER, attempts INTEGER DEFAULT 0, created_at INTEGER)');
    }

    public function push(string $queue, array $payload, int $delaySeconds = 0): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO queue_jobs (queue, payload, available_at, attempts, created_at) VALUES (:queue, :payload, :available_at, 0, :created_at)');
        $stmt->execute([
            ':queue' => $queue,
            ':payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ':available_at' => time() + $delaySeconds,
            ':created_at' => time(),
        ]);
    }

    public function pop(string $queue): ?array
    {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare('SELECT * FROM queue_jobs WHERE queue = :queue AND available_at <= :now ORDER BY id LIMIT 1');
        $stmt->execute([':queue' => $queue, ':now' => time()]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            $this->pdo->commit();
            return null;
        }
        $this->pdo->prepare('UPDATE queue_jobs SET attempts = attempts + 1 WHERE id = :id')->execute([':id' => $job['id']]);
        $this->pdo->commit();
        $job['payload'] = json_decode($job['payload'], true, 512, JSON_THROW_ON_ERROR);
        return $job;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM queue_jobs WHERE id = :id')->execute([':id' => $id]);
    }
}
