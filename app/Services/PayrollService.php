<?php
namespace App\Services;

use App\Core\DB;
use App\Models\Payout;
use DateTimeImmutable;
use PDO;

class PayrollService
{
    public function compute(string $month, bool $persist = false): array
    {
        $monthDate = DateTimeImmutable::createFromFormat('Y-m', $month) ?: new DateTimeImmutable('first day of this month');
        $monthKey = $monthDate->format('Y-m');
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT u.id AS user_id, u.name, u.role,
                COUNT(DISTINCT o.id) AS jobs,
                COALESCE(SUM(o.total),0) AS order_total,
                COALESCE(AVG(r.score),0) AS avg_rating
            FROM assignments a
            JOIN users u ON u.id = a.user_id
            JOIN orders o ON o.id = a.order_id
            LEFT JOIN ratings r ON r.order_id = o.id
            WHERE o.status IN ('completed','paid')
              AND o.completed_at IS NOT NULL
              AND strftime('%Y-%m', o.completed_at) = :month
              AND u.role IN ('cleaner','supervisor')
            GROUP BY u.id, u.name, u.role
            ORDER BY u.name");
        $stmt->execute([':month' => $monthKey]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $config = require __DIR__ . '/../Config/config.php';
        $defaultPct = (int)($config['default_commission_pct'] ?? 40);

        $results = [];
        foreach ($rows as $row) {
            $base = (int)round(((int)$row['order_total']) * $defaultPct / 100);
            $bonus = 0;
            if ((float)$row['avg_rating'] >= 4.8 && (int)$row['jobs'] >= 20) {
                $bonus = (int)round($base * 0.05);
            }
            $total = $base + $bonus;
            $results[] = [
                'user_id' => (int)$row['user_id'],
                'name' => $row['name'],
                'jobs' => (int)$row['jobs'],
                'order_total' => (int)$row['order_total'],
                'avg_rating' => round((float)$row['avg_rating'], 2),
                'base' => $base,
                'bonus' => $bonus,
                'total' => $total,
            ];
        }

        if ($persist) {
            DB::transaction(function () use ($results, $monthKey) {
                $pdo = DB::conn();
                $delete = $pdo->prepare('DELETE FROM payouts WHERE period = :period');
                $delete->execute([':period' => $monthKey]);
                foreach ($results as $result) {
                    Payout::create([
                        'user_id' => $result['user_id'],
                        'period' => $monthKey,
                        'amount' => $result['total'],
                        'method' => 'transfer',
                        'note' => 'Auto computed',
                    ]);
                }
            });
        }

        return $results;
    }
}
