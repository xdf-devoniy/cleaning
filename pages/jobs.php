<?php
require_login();
authorize(['owner','admin','dispatcher']);

$stages = [
    'planned' => [
        'label' => 'Boshlanish rejalashtirildi',
        'accent' => 'sky',
        'description' => 'Mijoz bilan kelishilgan start vaqti'
    ],
    'started' => [
        'label' => 'Ish boshlandi',
        'accent' => 'amber',
        'description' => 'Brigada joyida va ish jarayoni boshlandi'
    ],
    'done' => [
        'label' => 'Ish yakunlandi',
        'accent' => 'emerald',
        'description' => 'QC, fotosuratlar va tozalash yakunlandi'
    ],
];
$stageOrder = array_keys($stages);
$stageRank = array_flip($stageOrder);

$today = date('Y-m-d');
$selectedDate = $_GET['date'] ?? $today;
$filterClientId = isset($_GET['client_id']) && $_GET['client_id'] !== '' ? (int)$_GET['client_id'] : null;
$clientQueryString = $filterClientId ? '&client_id=' . $filterClientId : '';
$focusJob = isset($_GET['focus']) ? (int)$_GET['focus'] : null;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $user = current_user();
    $redirectDate = $_POST['return_date'] ?? $selectedDate;
    $redirectFocus = '';
    $redirectClient = $_POST['return_client_id'] ?? ($filterClientId ? (string)$filterClientId : '');

    if ($action === 'create_job') {
        $scheduledAt = $_POST['scheduled_at'] ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_at'])) : date('Y-m-d H:i:s');
        $endAt = $_POST['end_at'] ? date('Y-m-d H:i:s', strtotime($_POST['end_at'])) : null;
        execute(
            'INSERT INTO jobs (client_id, service_id, crew, status, scheduled_at, end_at, recurring_rule, geo_start, geo_end, route_url, urgent, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$_POST['client_id'],
                (int)$_POST['service_id'],
                trim($_POST['crew'] ?? ''),
                $_POST['status'] ?? 'new',
                $scheduledAt,
                $endAt,
                trim($_POST['recurring_rule'] ?? ''),
                trim($_POST['geo_start'] ?? ''),
                trim($_POST['geo_end'] ?? ''),
                trim($_POST['route_url'] ?? ''),
                !empty($_POST['urgent']) ? 1 : 0,
                trim($_POST['notes'] ?? '')
            ]
        );
        $jobId = (int)get_db()->lastInsertId();
        execute(
            'INSERT INTO job_progress (job_id, stage, happened_at, note, created_by) VALUES (?, ?, ?, ?, ?)',
            [$jobId, 'planned', $scheduledAt, 'Boshlanish vaqti belgilandi', $user['id'] ?? null]
        );
        $amount = (float)($_POST['amount'] ?? 0);
        $dueDate = $_POST['payment_due'] ? date('Y-m-d', strtotime($_POST['payment_due'])) : null;
        $paymentStatus = $_POST['payment_status'] ?? 'awaiting';
        execute(
            'INSERT INTO job_financials (job_id, amount, due_date, status, paid_at) VALUES (?, ?, ?, ?, CASE WHEN ? = "paid" THEN CURRENT_TIMESTAMP ELSE NULL END)',
            [$jobId, $amount, $dueDate, $paymentStatus, $paymentStatus]
        );
        if ($amount > 0) {
            execute('INSERT INTO job_financial_events (job_id, amount, status, note) VALUES (?, ?, ?, ?)', [$jobId, $amount, $paymentStatus, 'Boshlang\'ich qiymat']);
        }
        $redirectFocus = '&focus=' . $jobId;
        flash('success', 'Yangi ish muvaffaqiyatli rejalashtirildi.');
    } elseif ($action === 'update_job') {
        $jobId = (int)$_POST['job_id'];
        $scheduledAt = $_POST['scheduled_at'] ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_at'])) : date('Y-m-d H:i:s');
        $endAt = $_POST['end_at'] ? date('Y-m-d H:i:s', strtotime($_POST['end_at'])) : null;
        execute(
            'UPDATE jobs SET crew = ?, status = ?, scheduled_at = ?, end_at = ?, service_id = ?, notes = ? WHERE id = ?',
            [
                trim($_POST['crew'] ?? ''),
                $_POST['status'] ?? 'new',
                $scheduledAt,
                $endAt,
                (int)$_POST['service_id'],
                trim($_POST['notes'] ?? ''),
                $jobId
            ]
        );
        execute('UPDATE job_progress SET happened_at = ? WHERE job_id = ? AND stage = "planned"', [$scheduledAt, $jobId]);
        if (!empty($_POST['notify_cleaner'])) {
            $crewUsers = fetch_all('SELECT id FROM users WHERE role = "cleaner"');
            foreach ($crewUsers as $crewUser) {
                notify((int)$crewUser['id'], 'Yangi ish #' . $jobId . ' jadvalingizga qo\'shildi');
            }
        }
        $redirectFocus = '&focus=' . $jobId;
        flash('success', 'Ish tafsilotlari yangilandi.');
    } elseif ($action === 'upload_photo' && !empty($_FILES['photo'])) {
        $jobId = (int)$_POST['job_id'];
        $path = store_upload($_FILES['photo']);
        if ($path) {
            execute('INSERT INTO job_photos (job_id, path, type) VALUES (?, ?, ?)', [$jobId, $path, $_POST['photo_type'] ?? 'before']);
            flash('success', 'Foto muvaffaqiyatli yuklandi.');
        }
        $redirectFocus = '&focus=' . $jobId;
    } elseif ($action === 'log_stage') {
        $jobId = (int)$_POST['job_id'];
        $stage = $_POST['stage'] ?? '';
        if (isset($stages[$stage])) {
            $stageTime = $_POST['stage_time'] ? date('Y-m-d H:i:s', strtotime($_POST['stage_time'])) : date('Y-m-d H:i:s');
            execute(
                'INSERT INTO job_progress (job_id, stage, happened_at, note, created_by) VALUES (?, ?, ?, ?, ?)',
                [$jobId, $stage, $stageTime, trim($_POST['note'] ?? ''), $user['id'] ?? null]
            );
            if ($stage === 'started') {
                execute('UPDATE jobs SET status = "in-progress" WHERE id = ?', [$jobId]);
            }
            if ($stage === 'done') {
                execute('UPDATE jobs SET status = "completed" WHERE id = ?', [$jobId]);
            }
            $redirectFocus = '&focus=' . $jobId;
            flash('success', 'Bosqich qayd qilindi.');
        }
    } elseif ($action === 'update_payment') {
        $jobId = (int)$_POST['job_id'];
        $amount = (float)($_POST['amount'] ?? 0);
        $dueDate = $_POST['due_date'] ? date('Y-m-d', strtotime($_POST['due_date'])) : null;
        $status = $_POST['status'] ?? 'awaiting';
        $note = trim($_POST['note'] ?? '');
        execute(
            'INSERT INTO job_financials (job_id, amount, due_date, status, paid_at) VALUES (?, ?, ?, ?, CASE WHEN ? = "paid" THEN CURRENT_TIMESTAMP ELSE NULL END)
             ON CONFLICT(job_id) DO UPDATE SET amount = excluded.amount, due_date = excluded.due_date, status = excluded.status,
             paid_at = CASE WHEN excluded.status = "paid" THEN COALESCE(job_financials.paid_at, CURRENT_TIMESTAMP) ELSE job_financials.paid_at END',
            [$jobId, $amount, $dueDate, $status, $status]
        );
        execute('INSERT INTO job_financial_events (job_id, amount, status, note) VALUES (?, ?, ?, ?)', [$jobId, $amount, $status, $note ?: 'Status yangilandi']);
        if ($status === 'paid') {
            flash('success', 'To\'lov holati "To\'langan" deb belgilandi.');
        } else {
            flash('success', 'To\'lov ma\'lumotlari yangilandi.');
        }
        $redirectFocus = '&focus=' . $jobId;
    }

    $redirectUrl = 'index.php?page=jobs';
    if ($redirectDate) {
        $redirectUrl .= '&date=' . urlencode($redirectDate);
    }
    if ($redirectClient !== '') {
        $redirectUrl .= '&client_id=' . urlencode($redirectClient);
    }
    if ($redirectFocus) {
        $redirectUrl .= $redirectFocus;
    }
    redirect($redirectUrl);
}

$jobQuery = 'SELECT j.*, c.name AS client_name, c.phone, s.name AS service_name FROM jobs j
     LEFT JOIN clients c ON c.id = j.client_id
     LEFT JOIN services s ON s.id = j.service_id
     WHERE date(j.scheduled_at) = ?';
$jobParams = [$selectedDate];
if ($filterClientId) {
    $jobQuery .= ' AND j.client_id = ?';
    $jobParams[] = $filterClientId;
}
$jobQuery .= ' ORDER BY j.scheduled_at';
$jobs = fetch_all($jobQuery, $jobParams);

$jobIds = array_column($jobs, 'id');
$progressMap = [];
$financialMap = [];
$financialEvents = [];
if ($jobIds) {
    $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
    $progressRows = fetch_all('SELECT * FROM job_progress WHERE job_id IN (' . $placeholders . ') ORDER BY happened_at', $jobIds);
    foreach ($progressRows as $row) {
        $progressMap[$row['job_id']][] = $row;
    }
    $financialRows = fetch_all('SELECT * FROM job_financials WHERE job_id IN (' . $placeholders . ')', $jobIds);
    foreach ($financialRows as $row) {
        $progressMap[$row['job_id']] = $progressMap[$row['job_id']] ?? [];
        $financialMap[$row['job_id']] = $row;
    }
    $financialEventsRows = fetch_all('SELECT * FROM job_financial_events WHERE job_id IN (' . $placeholders . ') ORDER BY created_at DESC', $jobIds);
    foreach ($financialEventsRows as $row) {
        $financialEvents[$row['job_id']][] = $row;
    }
}
$financialMap = $financialMap ?? [];
$financialEvents = $financialEvents ?? [];

$clients = fetch_all('SELECT id, name FROM clients ORDER BY name');
$services = fetch_all('SELECT id, name FROM services ORDER BY name');

$board = array_fill_keys($stageOrder, []);
foreach ($jobs as $job) {
    $jobProgress = $progressMap[$job['id']] ?? [];
    $latestStage = 'planned';
    foreach ($jobProgress as $progress) {
        if (isset($stageRank[$progress['stage']])) {
            if ($stageRank[$progress['stage']] >= $stageRank[$latestStage]) {
                $latestStage = $progress['stage'];
            }
        }
    }
    $job['progress'] = $jobProgress;
    $job['financial'] = $financialMap[$job['id']] ?? null;
    $job['financial_events'] = $financialEvents[$job['id']] ?? [];
    $board[$latestStage][] = $job;
}

$monthDate = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate) ?: new DateTimeImmutable($today);
$monthStart = $monthDate->modify('first day of this month');
$monthEnd = $monthDate->modify('last day of this month');
$calendarCounts = fetch_all(
    'SELECT date(scheduled_at) as day, COUNT(*) as total FROM jobs WHERE date(scheduled_at) BETWEEN ? AND ? GROUP BY date(scheduled_at)',
    [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]
);
$calendarMap = [];
foreach ($calendarCounts as $row) {
    $calendarMap[$row['day']] = (int)$row['total'];
}

$firstWeekday = (int)$monthStart->format('N');
$daysInMonth = (int)$monthEnd->format('j');
$calendarCells = [];
for ($i = 1; $i < $firstWeekday; $i++) {
    $calendarCells[] = null;
}
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateKey = $monthStart->format('Y-m-') . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
    $calendarCells[] = [
        'date' => $dateKey,
        'count' => $calendarMap[$dateKey] ?? 0,
    ];
}
while (count($calendarCells) % 7 !== 0) {
    $calendarCells[] = null;
}
?>
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <form method="get" action="<?= htmlspecialchars(app_url('index.php')) ?>" class="glass flex flex-wrap items-center gap-3 rounded-2xl border border-white/10 px-4 py-3">
            <input type="hidden" name="page" value="jobs">
            <label class="flex flex-col text-xs uppercase tracking-wide text-slate-400">
                Sana
                <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" class="mt-1 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none">
            </label>
            <label class="flex flex-col text-xs uppercase tracking-wide text-slate-400">
                Mijoz
                <select name="client_id" class="mt-1 min-w-[160px] rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    <option value="">Barchasi</option>
                    <?php foreach ($clients as $clientOption): ?>
                        <option value="<?= $clientOption['id'] ?>" <?= $filterClientId === (int)$clientOption['id'] ? 'selected' : '' ?>><?= htmlspecialchars($clientOption['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="flex gap-2">
                <?php
                $prev = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate) ?: new DateTimeImmutable($selectedDate);
                $prev = $prev->modify('-1 day')->format('Y-m-d');
                $next = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate) ?: new DateTimeImmutable($selectedDate);
                $next = $next->modify('+1 day')->format('Y-m-d');
                $clientParam = $clientQueryString;
                ?>
                <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $prev . $clientParam)) ?>" class="rounded-xl bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/10">← Kecha</a>
                <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $today . $clientParam)) ?>" class="rounded-xl bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-emerald-100 hover:bg-emerald-500/30">Bugun</a>
                <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $next . $clientParam)) ?>" class="rounded-xl bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/10">Ertaga →</a>
            </div>
            <div class="ml-auto flex items-center gap-2 text-xs text-slate-300">
                <span class="inline-flex items-center gap-1 rounded-full bg-sky-500/10 px-3 py-1 text-sky-200"><span class="h-2 w-2 rounded-full bg-sky-400"></span>Start</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-3 py-1 text-amber-200"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Boshladi</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-200"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Tugadi</span>
            </div>
        </form>
        <div class="glass flex flex-wrap items-center gap-3 rounded-2xl border border-white/10 px-4 py-3 text-xs text-slate-300">
            <span class="font-semibold text-white">Kunlik holat</span>
            <span class="rounded-full bg-white/5 px-3 py-1">Rejalangan: <?= count($board['planned']) ?></span>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-amber-200">Boshlandi: <?= count($board['started']) ?></span>
            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-200">Yakunlandi: <?= count($board['done']) ?></span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <section class="glass space-y-6 rounded-3xl border border-white/10 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">DISPETCHER PANELI</p>
                    <h2 class="text-2xl font-semibold text-white">Ish oqimi bosqichlari</h2>
                </div>
                <a href="#new-job" class="rounded-2xl bg-emerald-500/20 px-4 py-2 text-sm font-semibold text-emerald-100 shadow-glow">+ Yangi ish</a>
            </header>
            <div class="grid gap-4 lg:grid-cols-3">
                <?php foreach ($board as $stageKey => $jobsInStage): ?>
                    <?php $accent = $stages[$stageKey]['accent']; ?>
                    <div class="flex flex-col rounded-2xl border border-white/10 bg-white/5">
                        <div class="border-b border-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400"><?= htmlspecialchars($stages[$stageKey]['label']) ?></p>
                            <p class="text-sm text-slate-300"><?= htmlspecialchars($stages[$stageKey]['description']) ?></p>
                        </div>
                        <div class="flex-1 space-y-3 overflow-y-auto px-4 py-3" style="max-height: 420px;">
                            <?php if (!$jobsInStage): ?>
                                <p class="text-xs text-slate-500">Hozircha ish mavjud emas.</p>
                            <?php endif; ?>
                            <?php foreach ($jobsInStage as $job): ?>
                                <?php $financial = $job['financial'] ?? ['status' => 'awaiting', 'amount' => 0, 'due_date' => null]; ?>
                                <article class="rounded-2xl border border-white/5 bg-slate-950/40 p-3 text-sm text-slate-200 shadow-inner shadow-<?= $accent ?>-500/20">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-base font-semibold text-white">#<?= $job['id'] ?> · <?= htmlspecialchars($job['client_name'] ?? 'Aniqlanmagan') ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($job['service_name'] ?? 'Xizmat tanlanmagan') ?></p>
                                        </div>
                                        <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $selectedDate . $clientQueryString . '&focus=' . $job['id'])) ?>" class="rounded-full bg-white/10 px-3 py-1 text-[11px] text-white hover:bg-white/20">Tafsilotlar</a>
                                    </div>
                                    <dl class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-slate-300">
                                        <div class="rounded-xl bg-white/5 px-3 py-2">
                                            <dt class="uppercase tracking-wide text-[10px] text-slate-500">Start</dt>
                                            <dd><?= htmlspecialchars(date('H:i', strtotime($job['scheduled_at']))) ?></dd>
                                        </div>
                                        <div class="rounded-xl bg-white/5 px-3 py-2">
                                            <dt class="uppercase tracking-wide text-[10px] text-slate-500">Brigada</dt>
                                            <dd><?= htmlspecialchars($job['crew']) ?: '—' ?></dd>
                                        </div>
                                        <div class="rounded-xl bg-white/5 px-3 py-2 col-span-2">
                                            <dt class="uppercase tracking-wide text-[10px] text-slate-500">To\'lov holati</dt>
                                            <dd class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold <?= $financial['status'] === 'paid' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-100' ?>">
                                                    <?= $financial['status'] === 'paid' ? 'To\'langan' : 'To\'lov kutilmoqda' ?>
                                                </span>
                                                <?php if ($financial['amount'] > 0): ?>
                                                    <span><?= number_format((float)$financial['amount'], 2, '.', ' ') ?> UZS</span>
                                                <?php endif; ?>
                                            </dd>
                                        </div>
                                    </dl>
                                    <div class="mt-3 flex flex-wrap gap-2 text-[10px]">
                                        <?php foreach (array_slice(array_reverse($job['progress']), 0, 3) as $progress): ?>
                                            <span class="rounded-full bg-white/5 px-3 py-1 text-slate-300"><?= htmlspecialchars($stages[$progress['stage']]['label'] ?? $progress['stage']) ?> · <?= htmlspecialchars(date('d.m H:i', strtotime($progress['happened_at']))) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($jobs): ?>
                <div class="space-y-4">
                    <?php foreach ($jobs as $job): ?>
                        <?php $isFocused = $focusJob === (int)$job['id']; ?>
                        <details id="job-<?= $job['id'] ?>" class="rounded-3xl border border-white/10 bg-white/5" <?= $isFocused ? 'open' : '' ?>>
                            <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <p class="text-sm uppercase tracking-wide text-slate-400">Ish #<?= $job['id'] ?> · <?= htmlspecialchars($job['client_name'] ?? '') ?></p>
                                    <p class="text-base font-semibold text-white"><?= htmlspecialchars($job['service_name'] ?? 'Xizmat tanlanmagan') ?></p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-300">
                                    <span class="rounded-full bg-white/10 px-3 py-1">Start <?= htmlspecialchars(date('d.m H:i', strtotime($job['scheduled_at']))) ?></span>
                                    <?php if ($job['end_at']): ?>
                                        <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-200">Yakun <?= htmlspecialchars(date('d.m H:i', strtotime($job['end_at']))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </summary>
                            <div class="grid gap-5 border-t border-white/5 px-5 py-5 lg:grid-cols-2">
                                <form method="post" class="space-y-3 rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-200">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="update_job">
                                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                    <input type="hidden" name="return_client_id" value="<?= htmlspecialchars((string)$filterClientId) ?>">
                                    <label class="block text-xs uppercase tracking-wide text-slate-400">Xizmat
                                        <select name="service_id" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?= $service['id'] ?>" <?= $service['id'] == $job['service_id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="block text-xs uppercase tracking-wide text-slate-400">Brigada
                                        <input name="crew" value="<?= htmlspecialchars($job['crew']) ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                    </label>
                                    <label class="block text-xs uppercase tracking-wide text-slate-400">Holat
                                        <select name="status" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                            <?php
                                            $statuses = [
                                                'new' => 'Yangi',
                                                'scheduled' => 'Rejalangan',
                                                'in-progress' => 'Jarayonda',
                                                'completed' => 'Bajarildi',
                                                'canceled' => 'Bekor qilindi',
                                                'no-show' => 'Kelmagan'
                                            ];
                                            ?>
                                            <?php foreach ($statuses as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $job['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="block text-xs uppercase tracking-wide text-slate-400">Boshlanish
                                            <input type="datetime-local" name="scheduled_at" value="<?= date('Y-m-d\TH:i', strtotime($job['scheduled_at'])) ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                        </label>
                                        <label class="block text-xs uppercase tracking-wide text-slate-400">Yakun
                                            <input type="datetime-local" name="end_at" value="<?= $job['end_at'] ? date('Y-m-d\TH:i', strtotime($job['end_at'])) : '' ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                        </label>
                                    </div>
                                    <label class="block text-xs uppercase tracking-wide text-slate-400">Izoh
                                        <textarea name="notes" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" rows="3"><?= htmlspecialchars($job['notes']) ?></textarea>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="notify_cleaner" value="1" class="rounded border-white/10 bg-white/5"> Brigadaga ogohlantirish yuborish</label>
                                    <button class="w-full rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-glow">O\'zgartirishlarni saqlash</button>
                                </form>

                                <div class="space-y-4">
                                    <section class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                        <h3 class="text-sm font-semibold text-white">Bosqichlarni boshqarish</h3>
                                        <div class="mt-3 space-y-2 text-xs text-slate-300">
                                            <?php foreach ($stageOrder as $stageKey): ?>
                                                <?php
                                                $record = null;
                                                foreach ($job['progress'] as $progress) {
                                                    if ($progress['stage'] === $stageKey) {
                                                        $record = $progress;
                                                    }
                                                }
                                                ?>
                                                <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                                                    <div>
                                                        <p class="text-[11px] font-semibold text-white"><?= htmlspecialchars($stages[$stageKey]['label']) ?></p>
                                                        <p class="text-[10px] text-slate-400"><?= $record ? date('d.m.Y H:i', strtotime($record['happened_at'])) : 'Hali qayd etilmagan' ?></p>
                                                    </div>
                                                    <form method="post" class="ml-2 flex items-center gap-2">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="log_stage">
                                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                        <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                                        <input type="hidden" name="return_client_id" value="<?= htmlspecialchars((string)$filterClientId) ?>">
                                                        <input type="datetime-local" name="stage_time" value="<?= date('Y-m-d\TH:i') ?>" class="rounded-xl border border-white/10 bg-white/5 px-2 py-1 text-[11px] text-white">
                                                        <input type="hidden" name="stage" value="<?= $stageKey ?>">
                                                        <input type="hidden" name="note" value="<?= htmlspecialchars($stages[$stageKey]['label']) ?>">
                                                        <button class="rounded-xl bg-white/10 px-3 py-1 text-[11px] text-white hover:bg-white/20">Qayd etish</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>

                                    <section class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-200">
                                        <h3 class="text-sm font-semibold text-white">To\'lov va hisob</h3>
                                        <?php $financial = $job['financial'] ?? ['amount' => 0, 'due_date' => null, 'status' => 'awaiting']; ?>
                                        <form method="post" class="mt-3 space-y-2 text-xs">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="update_payment">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                            <input type="hidden" name="return_client_id" value="<?= htmlspecialchars((string)$filterClientId) ?>">
                                            <label class="block text-[11px] uppercase tracking-wide text-slate-400">Summasi (UZS)
                                                <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($financial['amount']) ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                            </label>
                                            <label class="block text-[11px] uppercase tracking-wide text-slate-400">To\'lov muddati
                                                <input type="date" name="due_date" value="<?= $financial['due_date'] ?? '' ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                            </label>
                                            <label class="block text-[11px] uppercase tracking-wide text-slate-400">Holat
                                                <select name="status" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                                    <option value="awaiting" <?= ($financial['status'] ?? 'awaiting') === 'awaiting' ? 'selected' : '' ?>>To\'lov kutilmoqda</option>
                                                    <option value="partial" <?= ($financial['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Qisman to\'langan</option>
                                                    <option value="paid" <?= ($financial['status'] ?? '') === 'paid' ? 'selected' : '' ?>>To\'langan</option>
                                                </select>
                                            </label>
                                            <label class="block text-[11px] uppercase tracking-wide text-slate-400">Izoh
                                                <input type="text" name="note" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="Masalan: Naqd to\'lov"/>
                                            </label>
                                            <button class="w-full rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-glow">To\'lovni yangilash</button>
                                        </form>
                                        <?php if (!empty($job['financial_events'])): ?>
                                            <div class="mt-4 space-y-2 text-[11px]">
                                                <p class="text-xs font-semibold text-white">So\'nggi o\'zgarishlar</p>
                                                <?php foreach (array_slice($job['financial_events'], 0, 4) as $event): ?>
                                                    <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-slate-300">
                                                        <p><?= htmlspecialchars($event['status']) ?> · <?= htmlspecialchars(number_format((float)$event['amount'], 2, '.', ' ')) ?> UZS</p>
                                                        <p class="text-[10px] text-slate-500"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($event['created_at']))) ?> — <?= htmlspecialchars($event['note']) ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </section>

                                    <section class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-xs text-slate-300">
                                        <h3 class="text-sm font-semibold text-white">Foto va marshrut</h3>
                                        <p>Geo start: <?= htmlspecialchars($job['geo_start']) ?: '—' ?></p>
                                        <p>Geo finish: <?= htmlspecialchars($job['geo_end']) ?: '—' ?></p>
                                        <?php if ($job['route_url']): ?>
                                            <a class="mt-2 inline-flex items-center gap-2 rounded-full bg-sky-500/20 px-3 py-1 text-[11px] font-semibold text-sky-100" href="<?= htmlspecialchars($job['route_url']) ?>" target="_blank">Marshrutni ochish</a>
                                        <?php endif; ?>
                                        <form method="post" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="upload_photo">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                            <input type="hidden" name="return_client_id" value="<?= htmlspecialchars((string)$filterClientId) ?>">
                                            <select name="photo_type" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                                                <option value="before">Oldin</option>
                                                <option value="after">Keyin</option>
                                            </select>
                                            <input type="file" name="photo" class="text-[11px] text-slate-300">
                                            <button class="rounded-xl bg-white/10 px-3 py-1 text-[11px] text-white hover:bg-white/20">Yuklash</button>
                                        </form>
                                        <?php $photos = fetch_all('SELECT * FROM job_photos WHERE job_id = ?', [$job['id']]); ?>
                                        <?php if ($photos): ?>
                                            <ul class="mt-3 flex flex-wrap gap-2 text-[11px]">
                                                <?php foreach ($photos as $photo): ?>
                                                    <li><a class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-white" href="/<?= htmlspecialchars($photo['path']) ?>" target="_blank"><?= htmlspecialchars($photo['type']) ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </section>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-3xl border border-dashed border-white/10 bg-white/5 px-6 py-12 text-center text-slate-300">
                    Bu sana uchun ish rejalashtirilmagan. O\'ngdagi formadan yangi ish qo\'shing.
                </div>
            <?php endif; ?>
        </section>

        <aside class="space-y-6">
            <section id="new-job" class="glass rounded-3xl border border-white/10 p-6 text-sm text-slate-200">
                <h3 class="text-lg font-semibold text-white">Yangi ish yaratish</h3>
                <form method="post" class="mt-4 space-y-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="create_job">
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                    <input type="hidden" name="return_client_id" value="<?= htmlspecialchars((string)$filterClientId) ?>">
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Mijoz
                        <select name="client_id" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Xizmat
                        <select name="service_id" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                            <?php foreach ($services as $service): ?>
                                <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Brigada
                        <input name="crew" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="Brigada nomi">
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Start vaqti
                        <input type="datetime-local" name="scheduled_at" value="<?= date('Y-m-d\TH:i', strtotime($selectedDate . ' 09:00')) ?>" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Yakun vaqti
                        <input type="datetime-local" name="end_at" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Takrorlanish
                        <input name="recurring_rule" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="Masalan: Har dushanba">
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-xs uppercase tracking-wide text-slate-400">GPS start
                            <input name="geo_start" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="41.2995,69.2401">
                        </label>
                        <label class="block text-xs uppercase tracking-wide text-slate-400">GPS finish
                            <input name="geo_end" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="41.3125,69.2797">
                        </label>
                    </div>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Marshrut havolasi
                        <input name="route_url" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" placeholder="https://maps.google.com/...">
                    </label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Izoh
                        <textarea name="notes" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white" rows="3" placeholder="Maxsus topshiriqlar"></textarea>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="urgent" value="1" class="rounded border-white/10 bg-white/5"> Favqulodda</label>
                    <label class="block text-xs uppercase tracking-wide text-slate-400">Dastlabki holat
                        <select name="status" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                            <option value="new">Yangi</option>
                            <option value="scheduled">Rejalangan</option>
                        </select>
                    </label>
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4">
                        <p class="text-xs font-semibold text-emerald-200 uppercase tracking-wide">To\'lov sozlamalari</p>
                        <label class="mt-3 block text-[11px] uppercase tracking-wide text-emerald-100">Summasi (UZS)
                            <input type="number" step="0.01" name="amount" class="mt-1 w-full rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-white" placeholder="0">
                        </label>
                        <label class="mt-3 block text-[11px] uppercase tracking-wide text-emerald-100">To\'lov muddati
                            <input type="date" name="payment_due" class="mt-1 w-full rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-white">
                        </label>
                        <label class="mt-3 block text-[11px] uppercase tracking-wide text-emerald-100">Holat
                            <select name="payment_status" class="mt-1 w-full rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-white">
                                <option value="awaiting">To\'lov kutilmoqda</option>
                                <option value="paid">To\'langan</option>
                            </select>
                        </label>
                    </div>
                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-glow">Ishni yaratish</button>
                </form>
            </section>

            <section class="glass rounded-3xl border border-white/10 p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">KALENDAR</p>
                        <h3 class="text-lg font-semibold text-white"><?= $monthDate->format('F Y') ?></h3>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $monthStart->modify('-1 month')->format('Y-m-01') . $clientQueryString)) ?>" class="rounded-full bg-white/5 px-3 py-1 text-slate-200">←</a>
                        <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $monthStart->modify('+1 month')->format('Y-m-01') . $clientQueryString)) ?>" class="rounded-full bg-white/5 px-3 py-1 text-slate-200">→</a>
                    </div>
                </header>
                <div class="mt-4 grid grid-cols-7 gap-2 text-center text-xs text-slate-400">
                    <?php foreach (['Du','Se','Ch','Pa','Ju','Sh','Ya'] as $dayLabel): ?>
                        <div class="rounded-xl bg-white/5 py-2 font-semibold text-slate-200"><?= $dayLabel ?></div>
                    <?php endforeach; ?>
                    <?php foreach ($calendarCells as $cell): ?>
                        <?php if ($cell === null): ?>
                            <div class="rounded-xl border border-dashed border-white/5 py-4"></div>
                        <?php else: ?>
                            <?php
                            $isSelected = $cell['date'] === $selectedDate;
                            $count = $cell['count'];
                            ?>
                            <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . $cell['date'] . $clientQueryString)) ?>" class="flex h-full flex-col items-center justify-center rounded-xl border border-white/10 px-2 py-3 text-slate-200 <?= $isSelected ? 'bg-sky-500/20 text-white shadow-glow' : 'bg-white/5 hover:bg-white/10' ?>">
                                <span class="text-sm font-semibold"><?= (int)substr($cell['date'], -2) ?></span>
                                <span class="text-[10px] text-slate-300"><?= $count ?> ta ish</span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
</div>
