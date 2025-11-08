<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

ensure_session();

if (isset($_GET['logout'])) {
    logout();
    redirect('/index.php');
}

if (is_post() && isset($_POST['action']) && $_POST['action'] === 'login') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        flash('success', 'Xush kelibsiz!');
        redirect('/index.php');
    } else {
        flash('error', 'Login yoki parol noto\'g\'ri.');
    }
}

$user = current_user();
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard','clients','client_card','loyalty','invoices','jobs','checklists','inventory','hr','services','portal','communications','reports','settings'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

if ($user) {
    $navMetrics = [
        'clients' => (int)(fetch_one('SELECT COUNT(*) AS total FROM clients')['total'] ?? 0),
        'invoices' => (int)(fetch_one('SELECT COUNT(*) AS due FROM invoices WHERE status != "paid"')['due'] ?? 0),
        'jobs' => (int)(fetch_one('SELECT COUNT(*) AS scheduled FROM jobs WHERE DATE(scheduled_at) = DATE("now")')['scheduled'] ?? 0),
        'communications' => (int)(fetch_one('SELECT COUNT(*) AS new_msgs FROM communications WHERE DATE(occurred_at) = DATE("now")')['new_msgs'] ?? 0),
    ];
    $auditTotalRow = fetch_one('SELECT COUNT(*) AS c FROM audit_logs');
    $auditTotal = (int)($auditTotalRow['c'] ?? 0);
    $notificationTotalRow = fetch_one('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ?', [$user['id']]);
    $notificationTotal = (int)($notificationTotalRow['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tozalash Boshqaruv Platformasi</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Inter"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        night: '#0f172a',
                        ocean: '#0ea5e9',
                    },
                    boxShadow: {
                        glow: '0 10px 40px rgba(14, 165, 233, 0.25)',
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgba(14,165,233,0.15), transparent 35%),
                        radial-gradient(circle at 80% 0%, rgba(129,140,248,0.2), transparent 45%),
                        #0f172a;
        }
        .glass {
            backdrop-filter: blur(18px);
            background-color: rgba(15,23,42,0.65);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 font-display">
<div class="min-h-screen">
    <div class="flex items-center justify-between px-8 pt-8">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-sky-300">BEPUL CLEAN</p>
            <h1 class="mt-1 text-3xl font-semibold">Tozalash kompaniyasi boshqaruv tizimi</h1>
            <p class="text-sm text-slate-400">Rolga asoslangan boshqaruv, real vaqt analitika va mukammal tajriba</p>
        </div>
        <?php if ($user): ?>
            <div class="flex items-center gap-4 glass rounded-2xl px-5 py-3 shadow-glow">
                <div class="text-right">
                    <p class="text-sm uppercase tracking-wide text-slate-400"><?= roles()[$user['role']] ?? $user['role'] ?></p>
                    <p class="text-xl font-semibold text-white"><?= htmlspecialchars($user['username']) ?></p>
                </div>
                <a href="?logout=1" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-rose-500/30">
                    <span>Chiqish</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$user): ?>
        <div class="mt-10 flex flex-col items-center justify-center px-6">
            <div class="glass max-w-4xl w-full rounded-3xl border border-white/10 p-10 text-slate-200 shadow-2xl">
                <div class="grid gap-10 md:grid-cols-2">
                    <div class="space-y-6">
                        <h2 class="text-3xl font-semibold leading-tight">Operatsiyalarni yagona panelda boshqaring</h2>
                        <p class="text-sm text-slate-400">Mijozlar bilan munosabat, loyallik, hisob-kitoblar va dispetcherlik jarayonlarini birlashtirgan professional yechim.</p>
                        <ul class="space-y-3 text-sm text-slate-300">
                            <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span><span>Real vaqtli KPI'lar va moslashuvchan hisobotlar</span></li>
                            <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span><span>Rolega asoslangan xavfsizlik va audit izi</span></li>
                            <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span><span>Telegram, SMS va email integratsiyalari</span></li>
                        </ul>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-6 shadow-inner">
                        <h3 class="text-xl font-semibold text-white">Tizimga kirish</h3>
                        <?php if ($msg = flash('error')): ?>
                            <div class="mt-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                                <?= htmlspecialchars($msg) ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" class="mt-6 space-y-5">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="login">
                            <div>
                                <label class="text-xs uppercase tracking-wide text-slate-400">Login</label>
                                <input type="text" name="username" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="text-xs uppercase tracking-wide text-slate-400">Parol</label>
                                <input type="password" name="password" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none" required>
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/30">Kirish</button>
                        </form>
                        <p class="mt-6 text-xs text-slate-500">Standart foydalanuvchi: owner / owner123</p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-10 flex min-h-[70vh] gap-6 px-6 pb-10">
            <aside class="glass hidden w-72 flex-shrink-0 flex-col rounded-3xl border border-white/10 p-6 shadow-glow lg:flex">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase text-slate-400">Navigatsiya</p>
                        <p class="text-lg font-semibold text-white">Modullar</p>
                    </div>
                    <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs text-emerald-200"><?= date('d M') ?></span>
                </div>
                <?php
                $navItems = [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path d="M4 6h16M4 12h8m-8 6h16" stroke-width="1.6"/>'],
                    ['key' => 'clients', 'label' => 'Mijozlar', 'icon' => '<path d="M16 7a4 4 0 1 0-8 0 4 4 0 0 0 8 0Zm-4 6c-4.418 0-8 2.686-8 6v1h16v-1c0-3.314-3.582-6-8-6Z" stroke-width="1.6"/>'],
                    ['key' => 'client_card', 'label' => 'Mijoz kartasi', 'icon' => '<path d="M5 5h14v14H5z M5 11h14" stroke-width="1.6"/>'],
                    ['key' => 'loyalty', 'label' => 'Loyallik', 'icon' => '<path d="M12 21c-4-3.5-7-6.167-7-9.5A4.5 4.5 0 0 1 9.5 7a4.3 4.3 0 0 1 2.5.8A4.3 4.3 0 0 1 14.5 7 4.5 4.5 0 0 1 19 11.5C19 14.833 16 17.5 12 21Z" stroke-width="1.6"/>'],
                    ['key' => 'invoices', 'label' => 'Hisob-fakturalar', 'icon' => '<path d="M7 3h10l2 3v14H5V3h2Zm0 5h10M9 13h6" stroke-width="1.6"/>'],
                    ['key' => 'jobs', 'label' => 'Ish rejalashtirish', 'icon' => '<path d="M5 5h14v14H5z M9 3v4m6-4v4M9 13h6" stroke-width="1.6"/>'],
                    ['key' => 'checklists', 'label' => 'QC', 'icon' => '<path d="M9 11l2 2 4-4m-4-6h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2" stroke-width="1.6"/>'],
                    ['key' => 'inventory', 'label' => 'Inventar', 'icon' => '<path d="M4 7h16v12H4z M4 7l4-4h8l4 4" stroke-width="1.6"/>'],
                    ['key' => 'hr', 'label' => 'HR & Payroll', 'icon' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.418 0-8 2.239-8 5v2h16v-2c0-2.761-3.582-5-8-5Z" stroke-width="1.6"/>'],
                    ['key' => 'services', 'label' => 'Xizmatlar', 'icon' => '<path d="M6 4h12v4H6zm0 6h12v10H6z" stroke-width="1.6"/>'],
                    ['key' => 'portal', 'label' => 'Mijoz portali', 'icon' => '<path d="M4 5h16v10H4z m4 14h8" stroke-width="1.6"/>'],
                    ['key' => 'communications', 'label' => 'Kommunikatsiya', 'icon' => '<path d="M4 6h16v8H5l-1 4z" stroke-width="1.6"/>'],
                    ['key' => 'reports', 'label' => 'Hisobotlar', 'icon' => '<path d="M5 20h14M7 16V8m5 8V4m5 12v-6" stroke-width="1.6"/>'],
                    ['key' => 'settings', 'label' => 'Sozlamalar', 'icon' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.5-3a1.5 1.5 0 0 0 .113-2.993l-.113-.007-.8-.057a5.5 5.5 0 0 0-.856-2.064l.51-.628a1.5 1.5 0 0 0-2.17-2.07l-.623.511a5.5 5.5 0 0 0-2.069-.854l-.057-.8A1.5 1.5 0 0 0 11 2.5l-.007.113-.057.8a5.5 5.5 0 0 0-2.064.856l-.628-.51a1.5 1.5 0 0 0-2.07 2.17l.511.623a5.5 5.5 0 0 0-.854 2.069l-.8.057A1.5 1.5 0 0 0 4.5 12l.113.007.8.057a5.5 5.5 0 0 0 .856 2.064l-.51.628a1.5 1.5 0 1 0 2.17 2.07l.623-.511a5.5 5.5 0 0 0 2.069.854l.057.8A1.5 1.5 0 0 0 13 21.5l.007-.113.057-.8a5.5 5.5 0 0 0 2.064-.856l.628.51a1.5 1.5 0 0 0 2.07-2.17l-.511-.623a5.5 5.5 0 0 0 .854-2.069l.8-.057Z" stroke-width="1.6"/>'],
                ];
                ?>
                <nav class="flex-1 space-y-2 overflow-y-auto pr-2">
                    <?php foreach ($navItems as $item):
                        $isActive = $page === $item['key'];
                        $metric = $navMetrics[$item['key']] ?? null;
                        ?>
                        <a href="?page=<?= $item['key'] ?>" class="group flex items-center justify-between rounded-2xl border border-white/5 px-4 py-3 transition-all <?= $isActive ? 'bg-sky-500/20 text-white shadow-glow' : 'hover:bg-white/5 text-slate-300' ?>">
                            <span class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-5 w-5">
                                        <?= $item['icon'] ?>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium"><?= htmlspecialchars($item['label']) ?></span>
                            </span>
                            <?php if ($metric !== null): ?>
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200">
                                    <?= (int)$metric ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="mt-6 rounded-2xl border border-sky-500/30 bg-sky-500/10 p-4 text-xs text-sky-100">
                    <p class="font-semibold text-sm">Tezkor ko'rsatkichlar</p>
                    <p class="mt-1 text-slate-200">Bugungi jadval: <?= (int)($navMetrics['jobs'] ?? 0) ?> ta ish</p>
                    <p class="text-slate-300">To'lanmagan invoyslar: <?= (int)($navMetrics['invoices'] ?? 0) ?></p>
                </div>
            </aside>
            <main class="flex-1">
                <div class="glass rounded-3xl border border-white/10 px-6 py-6 shadow-2xl">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <form method="get" class="hidden md:block">
                                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                                <div class="relative">
                                    <input name="q" placeholder="Global qidiruv..." class="w-72 rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">⌘K</span>
                                </div>
                            </form>
                            <div class="hidden h-10 w-px bg-white/5 md:block"></div>
                            <div class="flex flex-wrap gap-3">
                                <a href="/index.php?page=jobs" class="rounded-2xl bg-emerald-500/20 px-4 py-2 text-sm font-medium text-emerald-100 shadow-lg shadow-emerald-500/20">Yangi ish</a>
                                <a href="/index.php?page=invoices" class="rounded-2xl bg-indigo-500/20 px-4 py-2 text-sm font-medium text-indigo-100 shadow-lg shadow-indigo-500/20">Yangi invoys</a>
                                <a href="/index.php?page=clients" class="rounded-2xl bg-sky-500/20 px-4 py-2 text-sm font-medium text-sky-100 shadow-lg shadow-sky-500/20">Yangi mijoz</a>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-300">Audit: <?= $auditTotal ?> yozuv</span>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-300">Bildirishnomalar: <?= $notificationTotal ?></span>
                        </div>
                    </div>

                    <?php if ($msg = flash('success')): ?>
                        <div class="mt-6 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($msg = flash('error')): ?>
                        <div class="mt-6 rounded-2xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-6 rounded-3xl bg-white p-6 text-slate-800 shadow-xl">
                        <?php include __DIR__ . '/../pages/' . $page . '.php'; ?>
                    </div>
                </div>
            </main>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
