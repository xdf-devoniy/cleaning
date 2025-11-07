<?php
require_login();
authorize(['owner','admin','accountant']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$team = $_GET['team'] ?? '';
$serviceFilter = $_GET['service_id'] ?? '';

$params = [$from, $to];
$revenue = fetch_one('SELECT SUM(total) AS revenue FROM invoices WHERE date(created_at) BETWEEN ? AND ?', $params)['revenue'] ?? 0;
$utilization = fetch_one('SELECT COUNT(*) AS total FROM jobs WHERE date(scheduled_at) BETWEEN ? AND ? AND status = "completed"', $params)['total'] ?? 0;
$churn = fetch_one('SELECT COUNT(*) AS lost FROM clients WHERE lead_status = "yo\'qotildi" AND date(created_at) BETWEEN ? AND ?', $params)['lost'] ?? 0;
$inventoryCost = fetch_one('SELECT SUM(CASE WHEN change_qty < 0 THEN -change_qty ELSE 0 END) AS usage FROM inventory_logs WHERE date(created_at) BETWEEN ? AND ?', $params)['usage'] ?? 0;

$serviceBreakdown = fetch_all('SELECT s.name, COUNT(j.id) AS jobs FROM jobs j LEFT JOIN services s ON s.id = j.service_id WHERE date(j.scheduled_at) BETWEEN ? AND ? GROUP BY s.name', $params);
$cleanerPerformance = fetch_all('SELECT staff_id, COUNT(*) AS shifts FROM attendance WHERE date(check_in) BETWEEN ? AND ? GROUP BY staff_id', $params);
?>
<form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <input type="hidden" name="page" value="reports">
    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="border rounded px-3 py-2">
    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="border rounded px-3 py-2">
    <input name="team" value="<?= htmlspecialchars($team) ?>" placeholder="Jamoa" class="border rounded px-3 py-2">
    <input name="service_id" value="<?= htmlspecialchars($serviceFilter) ?>" placeholder="Xizmat ID" class="border rounded px-3 py-2">
    <button class="bg-blue-600 text-white px-3 py-2 rounded">Filtrlash</button>
</form>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-blue-100 p-4 rounded">
        <p class="text-xs uppercase">Daromad</p>
        <p class="text-2xl font-bold"><?= format_currency((float)$revenue) ?></p>
    </div>
    <div class="bg-emerald-100 p-4 rounded">
        <p class="text-xs uppercase">Bajarilgan ishlar</p>
        <p class="text-2xl font-bold"><?= $utilization ?></p>
    </div>
    <div class="bg-rose-100 p-4 rounded">
        <p class="text-xs uppercase">Yo'qotilgan mijozlar</p>
        <p class="text-2xl font-bold"><?= $churn ?></p>
    </div>
    <div class="bg-amber-100 p-4 rounded">
        <p class="text-xs uppercase">Inventar sarfi</p>
        <p class="text-2xl font-bold"><?= (int)$inventoryCost ?></p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Xizmatlar bo'yicha ishlar</h2>
        <canvas id="serviceChart"></canvas>
    </section>
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Xodimlar bo'yicha davomat</h2>
        <ul class="text-sm space-y-1">
            <?php foreach ($cleanerPerformance as $row): ?>
                <li>Xodim #<?= $row['staff_id'] ?> - <?= $row['shifts'] ?> marotaba</li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<script>
const serviceChart = document.getElementById('serviceChart');
if (serviceChart) {
    new Chart(serviceChart, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($serviceBreakdown, 'name'), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Ishlar soni',
                data: <?= json_encode(array_column($serviceBreakdown, 'jobs')) ?>,
                backgroundColor: '#60a5fa'
            }]
        }
    });
}
</script>
