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
$expirySoon = fetch_one('SELECT COUNT(*) AS expiring FROM loyalty_transactions WHERE expires_at BETWEEN DATE("now") AND DATE("now", "+30 days") AND points > 0')['expiring'] ?? 0;
$redeemedLast30 = fetch_one('SELECT SUM(points) AS sum FROM loyalty_transactions WHERE points < 0 AND created_at >= DATE("now", "-30 days")')['sum'] ?? 0;
?>
<div class="space-y-8 text-slate-800">
    <section class="grid gap-4 lg:grid-cols-4">
        <div class="rounded-3xl bg-gradient-to-br from-sky-500 to-indigo-500 p-6 text-white shadow-xl">
            <p class="text-xs uppercase tracking-wide text-white/80">Umumiy yig'ilgan</p>
            <p class="mt-3 text-3xl font-semibold"><?= number_format((int)($report['earned'] ?? 0)) ?> ball</p>
            <p class="text-xs text-white/70">Oxirgi 30 kunda <?= number_format(abs((int)$redeemedLast30)) ?> ball ishlatilgan</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg">
            <p class="text-xs uppercase text-slate-500">Ishlatilgan ball</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900"><?= number_format(abs((int)($report['redeemed'] ?? 0))) ?></p>
            <p class="text-xs text-slate-500">Kutilayotgan eskirish: <?= $expirySoon ?> ta tranzaksiya</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg">
            <p class="text-xs uppercase text-slate-500">Bonus qoidalari</p>
            <p class="mt-3 text-lg font-semibold text-slate-900"><?= htmlspecialchars($earn) ?>% earn / <?= htmlspecialchars($redeem) ?>% redeem</p>
            <p class="text-xs text-slate-500">Amal qilish muddati: <?= htmlspecialchars($expiry) ?> kun</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg">
            <p class="text-xs uppercase text-slate-500">Tierlar</p>
            <ul class="mt-3 space-y-1 text-sm text-slate-600">
                <?php foreach ($tiers as $tier): ?>
                    <li class="flex justify-between">
                        <span><?= htmlspecialchars($tier['name']) ?></span>
                        <span><?= $tier['min_points'] ?>+</span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$tiers): ?><li class="text-xs text-slate-400">Darajalar belgilanmagan</li><?php endif; ?>
            </ul>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <h2 class="text-lg font-semibold text-slate-900">Sozlamalar va avtomatika</h2>
            <form method="post" class="mt-4 grid gap-4 md:grid-cols-3">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save_settings">
                <div>
                    <label class="text-xs uppercase text-slate-500">Hisob to'langanda (%)</label>
                    <input name="earn_percent" value="<?= htmlspecialchars($earn) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="5">
                </div>
                <div>
                    <label class="text-xs uppercase text-slate-500">Chegirma sifatida (%)</label>
                    <input name="redeem_percent" value="<?= htmlspecialchars($redeem) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="5">
                </div>
                <div>
                    <label class="text-xs uppercase text-slate-500">Muddati (kun)</label>
                    <input name="expiry_days" value="<?= htmlspecialchars($expiry) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="365">
                </div>
                <div class="md:col-span-3 flex gap-3">
                    <button class="rounded-2xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow shadow-sky-500/30">Sozlash</button>
                    <button name="action" value="expire_points" class="rounded-2xl bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow shadow-rose-500/30">Eskirgan ballarni tugatish</button>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
            <h2 class="text-lg font-semibold text-slate-900">Qo'shimcha boshqaruv</h2>
            <form method="post" class="mt-4 space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="adjust_points">
                <div>
                    <label class="text-xs uppercase text-slate-500">Mijoz</label>
                    <select name="client_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm">
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase text-slate-500">Ball</label>
                        <input name="points" type="number" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="100">
                    </div>
                    <div>
                        <label class="text-xs uppercase text-slate-500">Izoh</label>
                        <input name="description" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Bonus / jarima">
                    </div>
                </div>
                <button class="w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow shadow-emerald-500/30">Tasdiqlash</button>
            </form>

            <div class="mt-6 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-800">Darajalar</h3>
                <form method="post" class="mt-3 grid gap-3 md:grid-cols-4">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="save_tier">
                    <input name="name" placeholder="Nomi" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                    <input name="min_points" type="number" placeholder="Minimal" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                    <input name="benefits" placeholder="Imtiyoz" class="md:col-span-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                    <button class="md:col-span-4 rounded-2xl bg-indigo-500 px-4 py-2 text-sm font-semibold text-white">Darajani qo'shish</button>
                </form>
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Transaktsiyalar oqimi</h2>
                <p class="text-sm text-slate-500">Jami to'plangan: <?= number_format((int)($report['earned'] ?? 0)) ?> • Ishlatilgan: <?= number_format(abs((int)($report['redeemed'] ?? 0))) ?></p>
            </div>
            <span class="rounded-full bg-slate-900/5 px-3 py-1 text-xs text-slate-500">Oxirgi 50 yozuv</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Sana</th>
                        <th class="px-3 py-2">Mijoz</th>
                        <th class="px-3 py-2">Ball</th>
                        <th class="px-3 py-2">Turi</th>
                        <th class="px-3 py-2">Tavsif</th>
                        <th class="px-3 py-2">Amal qiladi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($txn['created_at']) ?></td>
                            <td class="px-3 py-2 font-medium text-slate-800"><?= htmlspecialchars($txn['client_name'] ?? '-') ?></td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $txn['points'] >= 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' ?>"><?= $txn['points'] ?></span>
                            </td>
                            <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($txn['type']) ?></td>
                            <td class="px-3 py-2 text-slate-500"><?= htmlspecialchars($txn['description']) ?></td>
                            <td class="px-3 py-2 text-slate-500"><?= htmlspecialchars($txn['expires_at'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
