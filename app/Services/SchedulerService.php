<?php
namespace App\Services;

use App\Core\DB;
use PDO;

class SchedulerService
{
    public function detectConflicts(array $payload): array
    {
        $scheduledAt = $payload['scheduled_at'] ?? null;
        $duration = (int)($payload['duration'] ?? 0);
        $assignments = $payload['assignments'] ?? [];
        $ignoreOrderId = isset($payload['order_id']) ? (int)$payload['order_id'] : null;

        if (!$scheduledAt || !$duration || empty($assignments)) {
            return [];
        }

        $start = strtotime($scheduledAt);
        $end = $start + ($duration * 60);

        $conflicts = [];
        $pdo = DB::conn();
        foreach ($assignments as $userId) {
            $stmt = $pdo->prepare("SELECT o.id, o.scheduled_at, o.duration
                FROM assignments a
                JOIN orders o ON o.id = a.order_id
                WHERE a.user_id = :user AND o.status IN ('scheduled','in_progress','completed','paid')
                  AND (:ignore IS NULL OR o.id != :ignore)");
            $stmt->execute([
                ':user' => $userId,
                ':ignore' => $ignoreOrderId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                if (!$row['scheduled_at'] || !$row['duration']) {
                    continue;
                }
                $existingStart = strtotime($row['scheduled_at']);
                $existingEnd = $existingStart + ((int)$row['duration'] * 60);
                if ($existingStart < $end && $start < $existingEnd) {
                    $conflicts[$userId][] = [
                        'order_id' => $row['id'],
                        'scheduled_at' => $row['scheduled_at'],
                    ];
                }
            }
        }

        return $conflicts;
    }
}
