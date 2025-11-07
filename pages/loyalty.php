<?php
require_login();
authorize(['owner','admin','accountant']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_settings') {
        set_setting('loyalty_earn_percent', $_POST['earn_percent'] ?? '5');
        set_setting('loyalty_redeem_percent', $_POST['redeem_percent'] ?? '5');
        set_setting('loyalty_expiry_days', $_POST['expiry_days'] ?? '365');
        flash('success', 'Loyallik sozlamalari saqlandi');
    } elseif ($action === 'adjust_points') {
        $clientId = (int)$_POST['client_id'];
        $points = (int)$_POST['points'];
        $type = $points >= 0 ? 'manual_credit' : 'manual_debit';
        execute('INSERT INTO loyalty_transactions (client_id, points, type, description, expires_at) VALUES (?, ?, ?, ?, DATE("now", ?))', [
            $clientId,
            $points,
            $type,
            trim($_POST['description'] ?? ''),
            '+' . (int)get_setting('loyalty_expiry_days', '365') . ' days'
        ]);
        flash('success', 'Ballar yangilandi');
    } elseif ($action === 'expire_points') {
        execute('UPDATE loyalty_transactions SET points = 0, type = "expired" WHERE expires_at IS NOT NULL AND expires_at < DATE("now") AND type != "expired"');
        flash('success', 'Eski ballar o\'chirildi');
    } elseif ($action === 'save_tier') {
        execute('INSERT INTO loyalty_tiers (name, min_points, benefits) VALUES (?, ?, ?)', [trim($_POST['name']), (int)$_POST['min_points'], trim($_POST['benefits'])]);
        flash('success', 'Yangi daraja qo\'shildi');
    }
    redirect('/index.php?page=loyalty');
}

$earn = get_setting('loyalty_earn_percent', '5');
$redeem = get_setting('loyalty_redeem_percent', '5');
$expiry = get_setting('loyalty_expiry_days', '365');
$tiers = fetch_all('SELECT * FROM loyalty_tiers ORDER BY min_points ASC');
$transactions = fetch_all('SELECT lt.*, c.name AS client_name FROM loyalty_transactions lt LEFT JOIN clients c ON c.id = lt.client_id ORDER BY lt.created_at DESC LIMIT 50');
$clients = fetch_all('SELECT id, name FROM clients ORDER BY name ASC');
$report = fetch_one('SELECT SUM(CASE WHEN points > 0 THEN points ELSE 0 END) AS earned, SUM(CASE WHEN points < 0 THEN points ELSE 0 END) AS redeemed FROM loyalty_transactions');
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold text-lg mb-3">Umumiy sozlamalar</h2>
        <form method="post" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save_settings">
            <div>
                <label class="block text-sm">Hisob to'langanda ball (%)</label>
                <input name="earn_percent" value="<?= htmlspecialchars($earn) ?>" class="border rounded px-3 py-2 w-full">
            </div>
            <div>
                <label class="block text-sm">Ballni chegirma sifatida ishlatish (%)</label>
                <input name="redeem_percent" value="<?= htmlspecialchars($redeem) ?>" class="border rounded px-3 py-2 w-full">
            </div>
            <div>
                <label class="block text-sm">Muddati (kun)</label>
                <input name="expiry_days" value="<?= htmlspecialchars($expiry) ?>" class="border rounded px-3 py-2 w-full">
            </div>
            <button class="bg-blue-600 text-white px-3 py-2 rounded">Saqlash</button>
        </form>
        <form method="post" class="mt-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="expire_points">
            <button class="bg-rose-500 text-white px-3 py-2 rounded">Eskirgan ballarni tugatish</button>
        </form>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold text-lg mb-3">Qo'shimcha boshqaruv</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="adjust_points">
            <label class="block text-sm">Mijoz</label>
            <select name="client_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">Ball (manfiy ham bo'lishi mumkin)</label>
            <input name="points" type="number" class="border rounded px-3 py-2 w-full">
            <input name="description" class="border rounded px-3 py-2 w-full" placeholder="Izoh">
            <button class="bg-emerald-600 text-white px-3 py-2 rounded">Tasdiqlash</button>
        </form>
        <div class="mt-4">
            <h3 class="font-semibold">Darajalar</h3>
            <ul class="text-sm space-y-1">
                <?php foreach ($tiers as $tier): ?>
                    <li><?= htmlspecialchars($tier['name']) ?> - <?= $tier['min_points'] ?> ball (<?= htmlspecialchars($tier['benefits']) ?>)</li>
                <?php endforeach; ?>
            </ul>
            <form method="post" class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save_tier">
                <input name="name" placeholder="Nomi" class="border rounded px-2 py-1">
                <input name="min_points" type="number" placeholder="Minimal" class="border rounded px-2 py-1">
                <input name="benefits" placeholder="Imtiyoz" class="border rounded px-2 py-1 md:col-span-1">
                <button class="bg-indigo-500 text-white px-3 py-1 rounded md:col-span-3">Qo'shish</button>
            </form>
        </div>
    </section>
</div>

<section class="mt-6">
    <h2 class="font-semibold text-lg mb-2">Transaktsiyalar</h2>
    <p class="text-sm text-slate-600">Jami to'plangan: <?= (int)($report['earned'] ?? 0) ?> | Jami ishlatilgan: <?= abs((int)($report['redeemed'] ?? 0)) ?></p>
    <div class="overflow-x-auto mt-3">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="px-3 py-2 text-left">Sana</th>
                    <th class="px-3 py-2 text-left">Mijoz</th>
                    <th class="px-3 py-2 text-left">Ball</th>
                    <th class="px-3 py-2 text-left">Turi</th>
                    <th class="px-3 py-2 text-left">Tavsif</th>
                    <th class="px-3 py-2 text-left">Amal qilish</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                    <tr class="border-b">
                        <td class="px-3 py-2"><?= htmlspecialchars($txn['created_at']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($txn['client_name'] ?? '-') ?></td>
                        <td class="px-3 py-2"><?= $txn['points'] ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($txn['type']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($txn['description']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($txn['expires_at'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
