<?php
namespace App\Controllers;

use App\Core\{Controller, DB, Response};
use PDO;

class DashboardController extends Controller
{
    public function index(): Response
    {
        if ($response = $this->requireAuth('dashboard.view')) {
            return $response;
        }

        $pdo = DB::conn();
        $month = date('Y-m');
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

        $jobsToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE date(scheduled_at) = date('now', 'localtime')")->fetchColumn();
        $revenueDay = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE date(received_at) = date('now', 'localtime')")->fetchColumn();
        $revenueWeekStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE date(received_at) BETWEEN :start AND :end");
        $revenueWeekStmt->execute([':start' => $startOfWeek, ':end' => $endOfWeek]);
        $revenueWeek = (int)$revenueWeekStmt->fetchColumn();
        $revenueMonthStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE strftime('%Y-%m', received_at) = :month");
        $revenueMonthStmt->execute([':month' => $month]);
        $revenueMonth = (int)$revenueMonthStmt->fetchColumn();

        $completionStmt = $pdo->query("SELECT SUM(CASE WHEN status IN ('completed','paid') THEN 1 ELSE 0 END) AS completed, COUNT(*) AS total FROM orders WHERE date(scheduled_at) >= date('now','-7 day')");
        $completionRow = $completionStmt ? $completionStmt->fetch(PDO::FETCH_ASSOC) : ['completed' => 0, 'total' => 0];
        $completionRate = ($completionRow['total'] ?? 0) > 0 ? round(($completionRow['completed'] / $completionRow['total']) * 100, 1) : 0.0;

        $topCleanerStmt = $pdo->query("SELECT u.name, COUNT(*) AS jobs FROM assignments a JOIN users u ON u.id = a.user_id JOIN orders o ON o.id = a.order_id WHERE o.status IN ('completed','paid') AND o.completed_at IS NOT NULL AND date(o.completed_at) >= date('now','-30 day') GROUP BY u.id ORDER BY jobs DESC LIMIT 1");
        $topCleaner = $topCleanerStmt ? $topCleanerStmt->fetch(PDO::FETCH_ASSOC) : null;

        return $this->view('dashboard/index', [
            'metrics' => [
                'jobs_today' => $jobsToday,
                'revenue_day' => $revenueDay,
                'revenue_week' => $revenueWeek,
                'revenue_month' => $revenueMonth,
                'completion_rate' => $completionRate,
                'top_cleaner' => $topCleaner['name'] ?? '—',
                'top_cleaner_jobs' => $topCleaner['jobs'] ?? 0,
            ],
        ]);
    }
}
