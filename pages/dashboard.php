<?php
require_login();
$user = current_user();

authorize(['owner','admin','accountant','dispatcher','cleaner']);

$clientCount = fetch_one('SELECT COUNT(*) AS total FROM clients')['total'] ?? 0;
$invoiceTotals = fetch_one('SELECT SUM(total) AS revenue, SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) AS paid FROM invoices');
$pendingJobs = fetch_one('SELECT COUNT(*) AS total FROM jobs WHERE status IN ("new","scheduled")')['total'] ?? 0;
$loyaltyOutstanding = fetch_one('SELECT SUM(points) AS pts FROM loyalty_transactions')['pts'] ?? 0;
$notifications = fetch_all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [$user['id']]);
$audit = fetch_all('SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 6');
$leadStatus = fetch_all('SELECT lead_status, COUNT(*) AS qty FROM clients GROUP BY lead_status');
$chartLabels = array_column($leadStatus, 'lead_status');
$chartValues = array_column($leadStatus, 'qty');

$monthlyRevenue = fetch_all('SELECT strftime("%Y-%m", COALESCE(paid_at, created_at)) AS month, SUM(total) AS total
    FROM invoices
    WHERE COALESCE(paid_at, created_at) >= date("now", "-6 months")
    GROUP BY month
    ORDER BY month ASC');
$monthlyLabels = array_column($monthlyRevenue, 'month');
$monthlyValues = array_map(fn($row) => (float)($row['total'] ?? 0), $monthlyRevenue);

$crewLoad = fetch_all('SELECT crew, COUNT(*) AS total FROM jobs WHERE scheduled_at >= datetime("now", "start of day") GROUP BY crew ORDER BY total DESC');
$maxCrewLoad = $crewLoad ? max(array_map(fn($row) => (int)$row['total'], $crewLoad)) : 0;

$recentJobs = fetch_all('SELECT j.*, c.name AS client_name, s.name AS service_name FROM jobs j
    LEFT JOIN clients c ON c.id = j.client_id
    LEFT JOIN services s ON s.id = j.service_id
    ORDER BY datetime(j.scheduled_at) DESC LIMIT 5');

$openReworks = fetch_one('SELECT COUNT(*) AS total FROM reworks WHERE created_at >= date("now", "-30 days")')['total'] ?? 0;
$completed30 = fetch_one('SELECT COUNT(*) AS total FROM jobs WHERE status = "completed" AND scheduled_at >= date("now", "-30 days")')['total'] ?? 0;
$onTime = fetch_one('SELECT COUNT(*) AS total FROM jobs WHERE status = "completed" AND end_at IS NOT NULL AND scheduled_at IS NOT NULL AND end_at <= scheduled_at')['total'] ?? 0;
$totalLeads = array_sum($chartValues);
$won = 0;
foreach ($leadStatus as $row) {
    if ($row['lead_status'] === 'yutildi') {
        $won = (int)$row['qty'];
        break;
    }
}
$conversion = $totalLeads ? round(($won / $totalLeads) * 100, 1) : 0;
$collectionRate = ($invoiceTotals['revenue'] ?? 0) ? round((($invoiceTotals['paid'] ?? 0) / ($invoiceTotals['revenue'] ?? 1)) * 100, 1) : 0;
$onTimeRate = $completed30 ? round(($onTime / $completed30) * 100, 1) : 0;
?>
<div class="space-y-8 text-slate-800">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 via-indigo-500 to-purple-500 p-6 text-white shadow-xl">
            <div class="text-xs uppercase tracking-wide text-white/70">Mijozlar</div>
            <div class="mt-3 text-4xl font-semibold"><?= number_format($clientCount) ?></div>
            <p class="mt-2 text-sm text-white/80">Faol segmentlar va yangi yetakchilar</p>
            <span class="absolute -right-10 top-4 h-24 w-24 rounded-full bg-white/20"></span>
        </div>
        <div class="relative overflow-hidden rounded-3xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Tushum</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900"><?= format_currency((float)($invoiceTotals['revenue'] ?? 0)) ?></p>
                    <p class="text-xs text-slate-500">To'langan: <?= $collectionRate ?>%</p>
                </div>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">Yopildi <?= format_currency((float)($invoiceTotals['paid'] ?? 0)) ?></span>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-3xl bg-white p-6 shadow-xl">
            <p class="text-xs font-semibold uppercase text-slate-500">Dispetcherlik</p>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-semibold text-slate-900"><?= $pendingJobs ?></span>
                <span class="rounded-full bg-sky-500/10 px-2 py-1 text-xs text-sky-600">Bugun</span>
            </div>
            <p class="mt-2 text-xs text-slate-500">Urgent chaqiriqlar: <?= fetch_one('SELECT COUNT(*) AS urgent FROM jobs WHERE urgent = 1')['urgent'] ?? 0 ?></p>
        </div>
        <div class="relative overflow-hidden rounded-3xl bg-white p-6 shadow-xl">
            <p class="text-xs font-semibold uppercase text-slate-500">Loyallik</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900"><?= number_format((int)$loyaltyOutstanding) ?> ball</p>
            <p class="mt-2 text-xs text-slate-500">Tierlar, bonus kampaniyalari va referallar</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Lead pipeline va konversiya</h2>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">Konversiya <?= $conversion ?>%</span>
            </div>
            <canvas id="leadChart" class="mt-6"></canvas>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <h2 class="text-lg font-semibold text-slate-900">Bugungi signal va bildirishnomalar</h2>
            <div class="mt-4 space-y-3">
                <?php foreach ($notifications as $note): ?>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                        <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($note['message']) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($note['created_at']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$notifications): ?>
                    <p class="text-sm text-slate-500">Bildirishnomalar yo'q</p>
                <?php endif; ?>
            </div>
            <div class="mt-4 rounded-2xl bg-sky-500/10 px-4 py-3 text-xs text-sky-700">
                <p class="font-semibold">Workflowlar faolligi</p>
                <p>30 kun ichida <?= $openReworks ?> ta rework talabi qayd etildi.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Oyma-oy tushum dinamikasi</h2>
                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs text-white">Yig'ish <?= $collectionRate ?>%</span>
            </div>
            <canvas id="revenueChart" class="mt-6"></canvas>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <h2 class="text-lg font-semibold text-slate-900">Brigadalar yuklamasi</h2>
            <ul class="mt-4 space-y-4">
                <?php foreach ($crewLoad as $row):
                    $ratio = $maxCrewLoad ? round(($row['total'] / $maxCrewLoad) * 100) : 0;
                    ?>
                    <li>
                        <div class="flex items-center justify-between text-sm font-medium text-slate-700">
                            <span><?= htmlspecialchars($row['crew'] ?: 'Noaniq brigada') ?></span>
                            <span><?= (int)$row['total'] ?> ta ish</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-sky-500" style="width: <?= $ratio ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$crewLoad): ?>
                    <li class="text-sm text-slate-500">Bugungi kunga vazifa tayinlanmagan.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <h2 class="text-lg font-semibold text-slate-900">Oxirgi faoliyat</h2>
            <ul class="mt-4 space-y-4 text-sm text-slate-600">
                <?php foreach ($recentJobs as $job): ?>
                    <li class="rounded-2xl border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800"><?= htmlspecialchars($job['client_name'] ?? 'Noma\'lum mijoz') ?></span>
                            <span class="rounded-full bg-slate-900/5 px-2 py-1 text-xs text-slate-500"><?= htmlspecialchars($job['status']) ?></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($job['scheduled_at']) ?> • <?= htmlspecialchars($job['service_name'] ?? 'Xizmat') ?></p>
                        <div class="mt-3 flex gap-2">
                            <a class="rounded-xl bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-700" href="/index.php?page=jobs&focus=<?= $job['id'] ?>">Jadval</a>
                            <a class="rounded-xl bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700" href="/index.php?page=invoices&client_id=<?= $job['client_id'] ?>">Invoys</a>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentJobs): ?>
                    <li class="text-sm text-slate-500">Hali ishlar kiritilmagan.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Audit jurnali va nazorat</h2>
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <span class="rounded-full bg-slate-900/5 px-3 py-1">Oxirgi 6 yozuv</span>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-700">On-time bajarilish: <?= $onTimeRate ?>%</span>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-2">Vaqt</th>
                        <th class="px-3 py-2">Foydalanuvchi</th>
                        <th class="px-3 py-2">Harakat</th>
                        <th class="px-3 py-2">Tafsilot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit as $item): ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($item['created_at']) ?></td>
                            <td class="px-3 py-2 font-medium text-slate-800"><?= htmlspecialchars($item['username'] ?? 'Noma\'lum') ?></td>
                            <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($item['action']) ?></td>
                            <td class="px-3 py-2 text-slate-500"><?= htmlspecialchars($item['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const leadCtx = document.getElementById('leadChart');
if (leadCtx) {
    new Chart(leadCtx, {
        type: 'polarArea',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($chartValues) ?>,
                backgroundColor: ['#38bdf8','#34d399','#fb7185','#facc15','#a855f7']
            }]
        },
        options: {
            scales: {r: {grid: {color: 'rgba(148,163,184,0.2)'}}},
            plugins: {
                legend: {position: 'bottom'}
            }
        }
    });
}

const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Tushum',
                data: <?= json_encode($monthlyValues) ?>,
                fill: true,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.1)',
                tension: 0.4,
                pointBackgroundColor: '#1d4ed8'
            }]
        },
        options: {
            plugins: {legend: {display: false}},
            scales: {
                x: {grid: {display: false}},
                y: {grid: {color: 'rgba(148,163,184,0.2)'}}
            }
        }
    });
}
</script>
