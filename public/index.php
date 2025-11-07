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

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tozalash Boshqaruv Platformasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Tozalash kompaniyasi boshqaruv tizimi</h1>
            <p class="text-sm text-slate-600">Loyiha tili: O\'zbekcha</p>
        </div>
        <div class="flex items-center gap-4">
            <?php if ($user): ?>
                <div class="text-right">
                    <p class="font-semibold"><?= htmlspecialchars($user['username']) ?></p>
                    <p class="text-xs text-slate-500">Roli: <?= roles()[$user['role']] ?? $user['role'] ?></p>
                </div>
                <a href="?logout=1" class="px-4 py-2 rounded bg-rose-500 text-white">Chiqish</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$user): ?>
        <div class="max-w-md mx-auto bg-white shadow rounded p-6">
            <h2 class="text-xl font-semibold mb-4">Tizimga kirish</h2>
            <?php if ($msg = flash('error')): ?>
                <div class="bg-rose-100 text-rose-700 px-3 py-2 rounded mb-3"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-sm font-medium">Login</label>
                    <input type="text" name="username" class="mt-1 w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Parol</label>
                    <input type="password" name="password" class="mt-1 w-full border rounded px-3 py-2" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Kirish</button>
            </form>
            <p class="text-xs text-slate-500 mt-4">Standart foydalanuvchi: owner / owner123</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-6">
            <?php
            $navItems = [
                'dashboard' => 'Dashboard',
                'clients' => 'Mijozlar',
                'client_card' => 'Mijoz kartasi',
                'loyalty' => 'Loyallik',
                'invoices' => 'Hisob-fakturalar',
                'jobs' => 'Ish rejalashtirish',
                'checklists' => 'QC',
                'inventory' => 'Inventar',
                'hr' => 'HR & Payroll',
                'services' => 'Xizmatlar',
                'portal' => 'Mijoz portali',
                'communications' => 'Kommunikatsiya',
                'reports' => 'Hisobotlar',
                'settings' => 'Sozlamalar'
            ];
            foreach ($navItems as $key => $label):
                $active = $page === $key ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-white hover:bg-slate-100';
                echo '<a href="?page=' . $key . '" class="border rounded px-3 py-2 text-sm font-medium ' . $active . '">' . $label . '</a>';
            endforeach;
            ?>
        </div>

        <?php if ($msg = flash('success')): ?>
            <div class="bg-emerald-100 text-emerald-700 px-3 py-2 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="bg-white border rounded shadow-sm p-5">
            <?php include __DIR__ . '/../pages/' . $page . '.php'; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
