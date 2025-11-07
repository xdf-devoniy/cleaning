<?php
// CleanTrack CRM - Simple PHP version (Uzbek tilida)
session_start();

$dbPath = __DIR__ . '/../database/cleantrack.sqlite';
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0777, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Maʼlumotlar bazasiga ulanib bo‘lmadi: ' . htmlspecialchars($e->getMessage()));
}

initializeDatabase($pdo);

// Foydalanuvchini aniqlash
if (!isset($_SESSION['user_id']) && isset($_POST['action']) && $_POST['action'] === 'login') {
    handleLogin($pdo);
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$pages = ['dashboard', 'clients', 'orders'];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $pages, true)) {
    $page = 'dashboard';
}

if (!isset($_SESSION['user_id'])) {
    echo renderHead('CleanTrack CRM - Kirish');
    echo renderLoginForm();
    echo renderFoot();
    exit;
}

$user = getUserById($pdo, $_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// POST amallarini ko‘rib chiqish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action'] ?? '') {
        case 'create_client':
            handleCreateClient($pdo);
            break;
        case 'create_order':
            handleCreateOrder($pdo);
            break;
    }
}

// Kontentni chiqarish
echo renderHead('CleanTrack CRM');
echo renderNavbar($user);

echo '<main class="max-w-6xl mx-auto p-4 space-y-6">';
if ($page === 'dashboard') {
    echo renderDashboard($pdo);
}
if ($page === 'clients') {
    echo renderClients($pdo);
}
if ($page === 'orders') {
    echo renderOrders($pdo);
}
echo '</main>';

echo renderFoot();

// ===== Funksiyalar =====
function initializeDatabase(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT,
        email TEXT UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "owner"
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT,
        email TEXT,
        tags TEXT,
        preferred_days TEXT,
        preferred_time TEXT,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT "rejalashtirilgan",
        scheduled_date TEXT NOT NULL,
        duration_minutes INTEGER DEFAULT 0,
        subtotal INTEGER NOT NULL DEFAULT 0,
        discount INTEGER NOT NULL DEFAULT 0,
        surcharge INTEGER NOT NULL DEFAULT 0,
        tax INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL DEFAULT 0,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        amount INTEGER NOT NULL,
        method TEXT NOT NULL,
        received_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )');

    // Standart foydalanuvchini yaratish
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    if ((int)$stmt->fetchColumn() === 0) {
        $passwordHash = password_hash('parol123', PASSWORD_BCRYPT);
        $insert = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, role) VALUES (?, ?, ?, ?, ?)');
        $insert->execute(['Administrator', '+998900000000', 'admin@example.com', $passwordHash, 'owner']);
    }
}

function handleLogin(PDO $pdo): void
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['flash_error'] = 'Login va parolni kiriting.';
        return;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['flash_error'] = 'Login yoki parol noto‘g‘ri.';
        return;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['flash_success'] = 'Xush kelibsiz, ' . htmlspecialchars($user['name']) . '!';
    header('Location: index.php');
    exit;
}

function handleCreateClient(PDO $pdo): void
{
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $_SESSION['flash_error'] = 'Mijoz nomini kiritish majburiy.';
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO clients (name, phone, email, tags, preferred_days, preferred_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $name,
        trim($_POST['phone'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['tags'] ?? ''),
        trim($_POST['preferred_days'] ?? ''),
        trim($_POST['preferred_time'] ?? ''),
        trim($_POST['notes'] ?? '')
    ]);
    $_SESSION['flash_success'] = 'Mijoz muvaffaqiyatli qo‘shildi.';
    header('Location: index.php?page=clients');
    exit;
}

function handleCreateOrder(PDO $pdo): void
{
    $clientId = (int)($_POST['client_id'] ?? 0);
    $scheduledDate = trim($_POST['scheduled_date'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $subtotal = (int)round(((float)($_POST['subtotal'] ?? 0)) * 100);
    $discount = (int)round(((float)($_POST['discount'] ?? 0)) * 100);
    $surcharge = (int)round(((float)($_POST['surcharge'] ?? 0)) * 100);
    $tax = (int)round(((float)($_POST['tax'] ?? 0)) * 100);

    if ($clientId <= 0 || $scheduledDate === '') {
        $_SESSION['flash_error'] = 'Mijoz va sanani to‘liq kiriting.';
        return;
    }

    $total = max(0, $subtotal - $discount + $surcharge + $tax);

    $stmt = $pdo->prepare('INSERT INTO orders (client_id, scheduled_date, duration_minutes, subtotal, discount, surcharge, tax, total, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $clientId,
        $scheduledDate,
        $duration,
        $subtotal,
        $discount,
        $surcharge,
        $tax,
        $total,
        trim($_POST['notes'] ?? '')
    ]);

    if ($total > 0 && isset($_POST['payment_amount']) && $_POST['payment_amount'] !== '') {
        $paymentAmount = (int)round(((float)$_POST['payment_amount']) * 100);
        $method = trim($_POST['payment_method'] ?? 'naqd');
        $orderId = (int)$pdo->lastInsertId();
        $paymentStmt = $pdo->prepare('INSERT INTO payments (order_id, amount, method) VALUES (?, ?, ?)');
        $paymentStmt->execute([$orderId, $paymentAmount, $method]);
    }

    $_SESSION['flash_success'] = 'Buyurtma saqlandi.';
    header('Location: index.php?page=orders');
    exit;
}

function renderHead(string $title): string
{
    return '<!DOCTYPE html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . htmlspecialchars($title) . '</title>'
        . '<link rel="stylesheet" href="assets/css/tailwind.css">'
        . '</head><body class="bg-slate-100 text-slate-900">';
}

function renderFoot(): string
{
    $html = '';
    if (isset($_SESSION['flash_error'])) {
        $html .= '<div class="fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
        unset($_SESSION['flash_error']);
    }
    if (isset($_SESSION['flash_success'])) {
        $html .= '<div class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-2 rounded shadow">' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
        unset($_SESSION['flash_success']);
    }
    $html .= '</body></html>';
    return $html;
}

function renderLoginForm(): string
{
    return '<div class="min-h-screen flex items-center justify-center">'
        . '<form method="post" class="bg-white shadow rounded-lg p-8 w-full max-w-sm space-y-4">'
        . '<h1 class="text-2xl font-semibold text-center">CleanTrack CRM</h1>'
        . '<input type="hidden" name="action" value="login">'
        . '<div><label class="block text-sm font-medium">Email</label><input required type="email" name="email" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" placeholder="admin@example.com"></div>'
        . '<div><label class="block text-sm font-medium">Parol</label><input required type="password" name="password" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" placeholder="parol123"></div>'
        . '<button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded">Kirish</button>'
        . '<p class="text-xs text-center text-slate-500">Standart login: admin@example.com / parol123</p>'
        . '</form></div>';
}

function renderNavbar(array $user): string
{
    $links = [
        'dashboard' => 'Asosiy',
        'clients' => 'Mijozlar',
        'orders' => 'Buyurtmalar',
    ];

    $current = $_GET['page'] ?? 'dashboard';
    $nav = '<header class="bg-white shadow"><div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">';
    $nav .= '<div class="text-xl font-semibold">CleanTrack CRM</div>';
    $nav .= '<nav class="space-x-4">';
    foreach ($links as $key => $label) {
        $active = $current === $key ? 'text-indigo-600 font-semibold' : 'text-slate-600';
        $nav .= '<a class="' . $active . '" href="?page=' . $key . '">' . htmlspecialchars($label) . '</a>';
    }
    $nav .= '</nav>';
    $nav .= '<div class="flex items-center space-x-3 text-sm text-slate-600">';
    $nav .= '<span>' . htmlspecialchars($user['name']) . '</span>';
    $nav .= '<a class="text-rose-600" href="?logout=1">Chiqish</a>';
    $nav .= '</div></div></header>';
    return $nav;
}

function renderDashboard(PDO $pdo): string
{
    $totalClients = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
    $todayOrders = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE DATE(scheduled_date) = DATE("now")');
    $todayOrders->execute();
    $todayCount = (int)$todayOrders->fetchColumn();

    $revenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn();
    $paid = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments')->fetchColumn();

    $cards = [
        ['label' => 'Bugungi ishlar', 'value' => $todayCount, 'color' => 'bg-indigo-50 text-indigo-700'],
        ['label' => 'Jami mijozlar', 'value' => $totalClients, 'color' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'Buyurtmalar summasi (UZS)', 'value' => formatMoneyTiyin((int)$revenue), 'color' => 'bg-amber-50 text-amber-700'],
        ['label' => 'Qabul qilingan to‘lovlar (UZS)', 'value' => formatMoneyTiyin((int)$paid), 'color' => 'bg-sky-50 text-sky-700'],
    ];

    $html = '<section><h2 class="text-xl font-semibold mb-4">Ko‘rsatkichlar</h2>';
    $html .= '<div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">';
    foreach ($cards as $card) {
        $html .= '<div class="p-4 rounded-lg shadow-sm ' . $card['color'] . '">';
        $html .= '<div class="text-sm uppercase tracking-wide">' . htmlspecialchars($card['label']) . '</div>';
        $html .= '<div class="text-2xl font-semibold mt-2">' . htmlspecialchars((string)$card['value']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div></section>';

    $stmt = $pdo->query('SELECT o.id, c.name AS client_name, o.scheduled_date, o.status, o.total FROM orders o JOIN clients c ON o.client_id = c.id ORDER BY o.scheduled_date DESC LIMIT 5');
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html .= '<section><h2 class="text-xl font-semibold mt-8 mb-4">So‘nggi buyurtmalar</h2>';
    if (!$recent) {
        $html .= '<p class="text-slate-500">Hozircha buyurtma yo‘q.</p>';
    } else {
        $html .= '<div class="overflow-x-auto bg-white shadow rounded-lg">';
        $html .= '<table class="min-w-full divide-y divide-slate-200">';
        $html .= '<thead class="bg-slate-50"><tr>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">ID</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Mijoz</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Sana</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Holat</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Summa</th>'
            . '</tr></thead><tbody class="divide-y divide-slate-200">';
        foreach ($recent as $row) {
            $html .= '<tr>'
                . '<td class="px-4 py-2 text-sm">#' . (int)$row['id'] . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars($row['client_name']) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars(formatDate($row['scheduled_date'])) . '</td>'
                . '<td class="px-4 py-2"><span class="px-2 py-1 bg-slate-100 rounded text-xs">' . htmlspecialchars($row['status']) . '</span></td>'
                . '<td class="px-4 py-2 text-sm">' . formatMoneyTiyin((int)$row['total']) . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</section>';

    return $html;
}

function renderClients(PDO $pdo): string
{
    $stmt = $pdo->query('SELECT * FROM clients ORDER BY created_at DESC');
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '<section class="space-y-6">';
    $html .= '<div class="bg-white shadow rounded-lg p-6">';
    $html .= '<h2 class="text-lg font-semibold mb-4">Yangi mijoz qo‘shish</h2>';
    $html .= '<form method="post" class="grid gap-4 md:grid-cols-2">';
    $html .= '<input type="hidden" name="action" value="create_client">';
    $html .= inputField('name', 'Mijoz nomi*');
    $html .= inputField('phone', 'Telefon');
    $html .= inputField('email', 'Email');
    $html .= inputField('tags', 'Teglar (vergul bilan)');
    $html .= inputField('preferred_days', 'Afzal kunlar');
    $html .= inputField('preferred_time', 'Afzal vaqt');
    $html .= '<div class="md:col-span-2"><label class="block text-sm font-medium text-slate-600">Izoh</label><textarea name="notes" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" rows="3"></textarea></div>';
    $html .= '<div class="md:col-span-2"><button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" type="submit">Saqlash</button></div>';
    $html .= '</form></div>';

    $html .= '<div class="bg-white shadow rounded-lg">';
    $html .= '<div class="px-6 py-4 border-b border-slate-200"><h2 class="text-lg font-semibold">Mijozlar ro‘yxati</h2></div>';
    if (!$clients) {
        $html .= '<p class="p-6 text-slate-500">Mijozlar hali qo‘shilmagan.</p>';
    } else {
        $html .= '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200">';
        $html .= '<thead class="bg-slate-50"><tr>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Mijoz</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Telefon</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Email</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Afzal vaqt</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Teglar</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Qo‘shilgan</th>'
            . '</tr></thead><tbody class="divide-y divide-slate-200">';
        foreach ($clients as $client) {
            $html .= '<tr>'
                . '<td class="px-4 py-2 text-sm font-medium">' . htmlspecialchars($client['name']) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars($client['phone']) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars($client['email']) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars(trim($client['preferred_days'] . ' ' . $client['preferred_time'])) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars($client['tags']) . '</td>'
                . '<td class="px-4 py-2 text-xs text-slate-500">' . htmlspecialchars(formatDate($client['created_at'])) . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div></section>';

    return $html;
}

function renderOrders(PDO $pdo): string
{
    $clients = $pdo->query('SELECT id, name FROM clients ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    $orders = $pdo->query('SELECT o.*, c.name AS client_name FROM orders o JOIN clients c ON o.client_id = c.id ORDER BY o.scheduled_date DESC')->fetchAll(PDO::FETCH_ASSOC);

    $html = '<section class="space-y-6">';
    $html .= '<div class="bg-white shadow rounded-lg p-6">';
    $html .= '<h2 class="text-lg font-semibold mb-4">Yangi buyurtma</h2>';
    if (!$clients) {
        $html .= '<p class="text-slate-500">Avval mijoz qo‘shing.</p>';
    } else {
        $html .= '<form method="post" class="grid gap-4 md:grid-cols-2">';
        $html .= '<input type="hidden" name="action" value="create_order">';
        $html .= '<div><label class="block text-sm font-medium text-slate-600">Mijoz*</label><select name="client_id" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" required><option value="">Tanlang</option>';
        foreach ($clients as $client) {
            $html .= '<option value="' . (int)$client['id'] . '">' . htmlspecialchars($client['name']) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div><label class="block text-sm font-medium text-slate-600">Sana*</label><input type="date" name="scheduled_date" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2"></div>';
        $html .= inputField('duration_minutes', 'Davomiylik (daqiqada)', 'number');
        $html .= inputField('subtotal', 'Xizmat summasi (UZS)', 'number', '0', '0.01');
        $html .= inputField('discount', 'Chegirma (UZS)', 'number', '0', '0.01');
        $html .= inputField('surcharge', 'Qo‘shimcha to‘lov (UZS)', 'number', '0', '0.01');
        $html .= inputField('tax', 'Soliq (UZS)', 'number', '0', '0.01');
        $html .= inputField('payment_amount', 'Darhol qabul qilingan to‘lov (UZS)', 'number', '');
        $html .= '<div><label class="block text-sm font-medium text-slate-600">To‘lov usuli</label><select name="payment_method" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">'
            . '<option value="naqd">Naqd</option><option value="kartadan">Kartadan</option><option value="bank">Bank o‘tkazmasi</option><option value="payme">Payme</option><option value="click">Click</option>'
            . '</select></div>';
        $html .= '<div class="md:col-span-2"><label class="block text-sm font-medium text-slate-600">Izoh</label><textarea name="notes" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" rows="3"></textarea></div>';
        $html .= '<div class="md:col-span-2"><button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" type="submit">Buyurtmani saqlash</button></div>';
        $html .= '</form>';
    }
    $html .= '</div>';

    $html .= '<div class="bg-white shadow rounded-lg">';
    $html .= '<div class="px-6 py-4 border-b border-slate-200"><h2 class="text-lg font-semibold">Buyurtmalar ro‘yxati</h2></div>';
    if (!$orders) {
        $html .= '<p class="p-6 text-slate-500">Buyurtmalar yo‘q.</p>';
    } else {
        $html .= '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200">';
        $html .= '<thead class="bg-slate-50"><tr>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">ID</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Mijoz</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Sana</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Holat</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Umumiy summa</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Izoh</th>'
            . '</tr></thead><tbody class="divide-y divide-slate-200">';
        foreach ($orders as $order) {
            $html .= '<tr>'
                . '<td class="px-4 py-2 text-sm">#' . (int)$order['id'] . '</td>'
                . '<td class="px-4 py-2 text-sm font-medium">' . htmlspecialchars($order['client_name']) . '</td>'
                . '<td class="px-4 py-2 text-sm">' . htmlspecialchars(formatDate($order['scheduled_date'])) . '</td>'
                . '<td class="px-4 py-2"><span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">' . htmlspecialchars($order['status']) . '</span></td>'
                . '<td class="px-4 py-2 text-sm">' . formatMoneyTiyin((int)$order['total']) . '</td>'
                . '<td class="px-4 py-2 text-sm text-slate-500">' . htmlspecialchars($order['notes']) . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div></section>';

    return $html;
}

function inputField(string $name, string $label, string $type = 'text', string $value = '', string $step = ''): string
{
    $attrs = 'class="mt-1 w-full border border-slate-300 rounded px-3 py-2"';
    if ($step !== '') {
        $attrs .= ' step="' . htmlspecialchars($step) . '"';
    }
    return '<div><label class="block text-sm font-medium text-slate-600">' . htmlspecialchars($label) . '</label>'
        . '<input type="' . htmlspecialchars($type) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" ' . $attrs . '></div>';
}

function getUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function formatMoneyTiyin(int $amount): string
{
    $uzs = $amount / 100;
    return number_format($uzs, 2, '.', ' ');
}

function formatDate(?string $value): string
{
    if (!$value) {
        return '';
    }
    $timestamp = strtotime($value);
    if (!$timestamp) {
        return $value;
    }
    return date('d.m.Y', $timestamp);
}
?>
