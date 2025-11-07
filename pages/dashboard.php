<?php
require_login();
$user = current_user();

authorize(['owner','admin','accountant','dispatcher','cleaner']);

$clientCount = fetch_one('SELECT COUNT(*) AS total FROM clients')['total'] ?? 0;
$invoiceTotals = fetch_one('SELECT SUM(total) AS revenue, SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) AS paid FROM invoices');
$pendingJobs = fetch_one('SELECT COUNT(*) AS total FROM jobs WHERE status IN ("new","scheduled")')['total'] ?? 0;
$loyaltyOutstanding = fetch_one('SELECT SUM(points) AS pts FROM loyalty_transactions')['pts'] ?? 0;
$notifications = fetch_all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [$user['id']]);
$audit = fetch_all('SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 5');
$leadStatus = fetch_all('SELECT lead_status, COUNT(*) AS qty FROM clients GROUP BY lead_status');
$chartLabels = array_column($leadStatus, 'lead_status');
$chartValues = array_column($leadStatus, 'qty');
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-blue-100 text-blue-800 rounded p-4">
        <p class="text-xs uppercase">Mijozlar</p>
        <p class="text-2xl font-bold"><?= $clientCount ?></p>
    </div>
    <div class="bg-emerald-100 text-emerald-800 rounded p-4">
        <p class="text-xs uppercase">Jami tushum</p>
        <p class="text-2xl font-bold"><?= format_currency((float)($invoiceTotals['revenue'] ?? 0)) ?></p>
    </div>
    <div class="bg-amber-100 text-amber-800 rounded p-4">
        <p class="text-xs uppercase">Kutilayotgan ishlar</p>
        <p class="text-2xl font-bold"><?= $pendingJobs ?></p>
    </div>
    <div class="bg-purple-100 text-purple-800 rounded p-4">
        <p class="text-xs uppercase">Loyallik ballari</p>
        <p class="text-2xl font-bold"><?= (int)$loyaltyOutstanding ?></p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <h2 class="font-semibold mb-2">Leed statusi bo'yicha taqsimot</h2>
        <canvas id="leadChart"></canvas>
    </div>
    <div>
        <h2 class="font-semibold mb-2">So'nggi bildirishnomalar</h2>
        <div class="space-y-2">
            <?php foreach ($notifications as $note): ?>
                <div class="border rounded px-3 py-2">
                    <p class="text-sm"><?= htmlspecialchars($note['message']) ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($note['created_at']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$notifications): ?>
                <p class="text-sm text-slate-500">Bildirishnomalar yo'q</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-6">
    <h2 class="font-semibold mb-2">Audit izlari</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="px-3 py-2">Vaqt</th>
                    <th class="px-3 py-2">Foydalanuvchi</th>
                    <th class="px-3 py-2">Harakat</th>
                    <th class="px-3 py-2">Tafsilot</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit as $item): ?>
                    <tr class="border-b">
                        <td class="px-3 py-2"><?= htmlspecialchars($item['created_at']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($item['username'] ?? 'Noma\'lum') ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($item['action']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($item['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('leadChart');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($chartValues) ?>,
                backgroundColor: ['#60a5fa','#34d399','#fbbf24','#fb7185','#a855f7']
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
</script>
