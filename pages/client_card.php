<?php
require_login();
authorize(['owner','admin','accountant','dispatcher']);

$clientId = (int)($_GET['client_id'] ?? 0);
$clients = fetch_all('SELECT id, name FROM clients ORDER BY name ASC');
$client = $clientId ? fetch_one('SELECT * FROM clients WHERE id = ?', [$clientId]) : null;

if (is_post() && $client) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_preferences') {
        $prefs = trim($_POST['preferences'] ?? '');
        execute('UPDATE clients SET notes = ? WHERE id = ?', [$prefs, $clientId]);
        audit_log(current_user()['id'], 'update', 'client_prefs', $clientId, 'Mijoz afzalliklari yangilandi');
        flash('success', 'Afzalliklar yangilandi');
    } elseif ($action === 'add_address') {
        execute('INSERT INTO client_addresses (client_id, label, address, preferences) VALUES (?, ?, ?, ?)', [$clientId, trim($_POST['label'] ?? ''), trim($_POST['address'] ?? ''), trim($_POST['preferences'] ?? '')]);
        flash('success', 'Manzil qo\'shildi');
    } elseif ($action === 'update_address') {
        execute('UPDATE client_addresses SET label=?, address=?, preferences=? WHERE id=? AND client_id=?', [trim($_POST['label'] ?? ''), trim($_POST['address'] ?? ''), trim($_POST['preferences'] ?? ''), (int)$_POST['address_id'], $clientId]);
        flash('success', 'Manzil yangilandi');
    }
    redirect('/index.php?page=client_card&client_id=' . $clientId);
}

$addresses = $client ? fetch_all('SELECT * FROM client_addresses WHERE client_id = ?', [$clientId]) : [];
$invoices = $client ? fetch_all('SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC', [$clientId]) : [];
$payments = $client ? fetch_all('SELECT * FROM invoice_payments WHERE invoice_id IN (SELECT id FROM invoices WHERE client_id = ?)', [$clientId]) : [];
$loyalty = $client ? fetch_all('SELECT * FROM loyalty_transactions WHERE client_id = ? ORDER BY created_at DESC', [$clientId]) : [];
$jobs = $client ? fetch_all('SELECT j.*, s.name AS service_name FROM jobs j LEFT JOIN services s ON s.id = j.service_id WHERE j.client_id = ? ORDER BY scheduled_at DESC LIMIT 10', [$clientId]) : [];
$notes = $client ? fetch_all('SELECT * FROM client_notes WHERE client_id = ? ORDER BY created_at DESC LIMIT 10', [$clientId]) : [];
$files = $client ? fetch_all('SELECT * FROM client_documents WHERE client_id = ? ORDER BY uploaded_at DESC', [$clientId]) : [];
$referrals = $client ? fetch_all('SELECT * FROM loyalty_transactions WHERE client_id = ? AND type = "referral"', [$clientId]) : [];

$totalRevenue = array_sum(array_column($invoices, 'total'));
$totalPaid = array_sum(array_map(fn($p) => $p['amount'], $payments));
$balance = $totalRevenue - $totalPaid;
$lastActivity = $notes[0]['created_at'] ?? ($jobs[0]['scheduled_at'] ?? $client['last_activity'] ?? '');
$points = array_sum(array_map(fn($t) => $t['points'], $loyalty));
$tier = fetch_one('SELECT name FROM loyalty_tiers WHERE min_points <= ? ORDER BY min_points DESC LIMIT 1', [$points]);
?>
<form method="get" class="mb-4 flex gap-3 items-end">
    <input type="hidden" name="page" value="client_card">
    <div>
        <label class="block text-sm">Mijozni tanlang</label>
        <select name="client_id" class="border rounded px-3 py-2">
            <option value="">-- tanlash --</option>
            <?php foreach ($clients as $item): ?>
                <option value="<?= $item['id'] ?>" <?= $clientId === (int)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="bg-blue-600 text-white px-3 py-2 rounded">Yuklash</button>
</form>

<?php if ($client): ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-100 p-4 rounded">
        <p class="text-xs uppercase">LTV</p>
        <p class="text-2xl font-bold"><?= format_currency((float)$totalRevenue, $invoices[0]['currency'] ?? 'UZS') ?></p>
    </div>
    <div class="bg-emerald-100 p-4 rounded">
        <p class="text-xs uppercase">Qoldiq</p>
        <p class="text-2xl font-bold"><?= format_currency((float)$balance, $invoices[0]['currency'] ?? 'UZS') ?></p>
    </div>
    <div class="bg-purple-100 p-4 rounded">
        <p class="text-xs uppercase">So'nggi faoliyat</p>
        <p class="text-xl font-semibold"><?= htmlspecialchars($lastActivity ?: 'Ma\'lumot yo\'q') ?></p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        <section>
            <h2 class="font-semibold text-lg mb-2">To'liq profil</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded p-4">
                    <h3 class="font-semibold">Kontaktlar</h3>
                    <p class="text-sm">Telefon: <?= htmlspecialchars($client['phone'] ?? '-') ?></p>
                    <p class="text-sm">Email: <?= htmlspecialchars($client['email'] ?? '-') ?></p>
                    <p class="text-sm">Teg: <?= htmlspecialchars($client['tag'] ?? '-') ?></p>
                    <p class="text-sm">Status: <?= htmlspecialchars($client['lead_status']) ?></p>
                </div>
                <div class="border rounded p-4">
                    <h3 class="font-semibold">Afzalliklar</h3>
                    <form method="post" class="space-y-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="save_preferences">
                        <textarea name="preferences" class="w-full border rounded px-2 py-1" rows="4"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
                        <button class="bg-blue-600 text-white px-3 py-1 rounded">Saqlash</button>
                    </form>
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-semibold text-lg mb-2">Manzillar</h2>
            <div class="space-y-3">
                <?php foreach ($addresses as $address): ?>
                    <form method="post" class="border rounded p-3 grid grid-cols-1 md:grid-cols-3 gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="update_address">
                        <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                        <input name="label" value="<?= htmlspecialchars($address['label']) ?>" class="border rounded px-2 py-1" placeholder="Nomi">
                        <input name="address" value="<?= htmlspecialchars($address['address']) ?>" class="border rounded px-2 py-1" placeholder="Manzil">
                        <input name="preferences" value="<?= htmlspecialchars($address['preferences']) ?>" class="border rounded px-2 py-1" placeholder="Afzallik">
                        <button class="bg-emerald-500 text-white px-3 py-1 rounded">Yangilash</button>
                    </form>
                <?php endforeach; ?>
                <form method="post" class="border rounded p-3 grid grid-cols-1 md:grid-cols-3 gap-2">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="add_address">
                    <input name="label" class="border rounded px-2 py-1" placeholder="Nomi">
                    <input name="address" class="border rounded px-2 py-1" placeholder="Manzil">
                    <input name="preferences" class="border rounded px-2 py-1" placeholder="Afzallik">
                    <button class="bg-blue-600 text-white px-3 py-1 rounded">Qo'shish</button>
                </form>
            </div>
        </section>

        <section>
            <h2 class="font-semibold text-lg mb-2">Taymlayn</h2>
            <ul class="space-y-2 text-sm">
                <?php foreach ($jobs as $job): ?>
                    <li class="border rounded px-3 py-2">
                        <div class="font-semibold"><?= htmlspecialchars($job['scheduled_at']) ?> - <?= htmlspecialchars($job['service_name'] ?? 'Xizmat') ?></div>
                        <p>Status: <?= htmlspecialchars($job['status']) ?></p>
                        <div class="flex gap-2 mt-2 text-xs">
                            <a class="bg-indigo-500 text-white px-2 py-1 rounded" href="/index.php?page=jobs&focus=<?= $job['id'] ?>">Ishni ko'rish</a>
                            <a class="bg-emerald-500 text-white px-2 py-1 rounded" href="/index.php?page=invoices&client_id=<?= $clientId ?>">Hisob yuborish</a>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($notes as $note): ?>
                    <li class="border rounded px-3 py-2 bg-slate-50">
                        <div class="font-semibold"><?= htmlspecialchars($note['created_at']) ?> - Eslatma</div>
                        <p><?= htmlspecialchars($note['note']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section>
            <h2 class="font-semibold text-lg mb-2">Fayllar</h2>
            <ul class="space-y-1 text-sm">
                <?php foreach ($files as $file): ?>
                    <li>
                        <a class="text-blue-600 underline" href="/<?= htmlspecialchars($file['path']) ?>" target="_blank"><?= htmlspecialchars($file['description'] ?: $file['path']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <div class="space-y-6">
        <section class="border rounded p-4">
            <h3 class="font-semibold mb-2">Moliyaviy ko'rinish</h3>
            <p class="text-sm">Jami hisob: <?= format_currency((float)$totalRevenue) ?></p>
            <p class="text-sm">To'langan: <?= format_currency((float)$totalPaid) ?></p>
            <p class="text-sm">Qoldiq: <?= format_currency((float)$balance) ?></p>
            <h4 class="font-semibold mt-3">Hisoblar</h4>
            <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
                <?php foreach ($invoices as $invoice): ?>
                    <li>#<?= htmlspecialchars($invoice['number']) ?> - <?= htmlspecialchars($invoice['status']) ?> - <?= format_currency((float)$invoice['total'], $invoice['currency']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="border rounded p-4">
            <h3 class="font-semibold mb-2">Loyallik</h3>
            <p class="text-sm">Ballar: <?= $points ?></p>
            <p class="text-sm">Daraja: <?= htmlspecialchars($tier['name'] ?? 'Bronza') ?></p>
            <ul class="text-sm space-y-1 max-h-40 overflow-y-auto mt-2">
                <?php foreach ($loyalty as $txn): ?>
                    <li><?= htmlspecialchars($txn['created_at']) ?> - <?= $txn['points'] ?> (<?= htmlspecialchars($txn['type']) ?>)</li>
                <?php endforeach; ?>
            </ul>
            <h4 class="font-semibold mt-3">Referal tarixi</h4>
            <ul class="text-sm space-y-1">
                <?php foreach ($referrals as $ref): ?>
                    <li><?= htmlspecialchars($ref['description'] ?? 'Referal') ?> - <?= $ref['points'] ?> ball</li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="border rounded p-4">
            <h3 class="font-semibold mb-2">Rejalashtirilgan rejalar</h3>
            <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
                <?php foreach ($jobs as $job): ?>
                    <?php if ($job['recurring_rule']): ?>
                        <li><?= htmlspecialchars($job['service_name'] ?? '-') ?> - <?= htmlspecialchars($job['recurring_rule']) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="border rounded p-4">
            <h3 class="font-semibold mb-2">QC & Fikrlar</h3>
            <?php $qc = fetch_all('SELECT q.*, c.name AS checklist FROM job_qc q LEFT JOIN checklists c ON c.id = q.checklist_id WHERE q.job_id IN (SELECT id FROM jobs WHERE client_id = ?) ORDER BY q.created_at DESC LIMIT 5', [$clientId]); ?>
            <ul class="text-sm space-y-1">
                <?php foreach ($qc as $item): ?>
                    <li><?= htmlspecialchars($item['created_at']) ?> - <?= htmlspecialchars($item['checklist'] ?? 'QC') ?> - <?= htmlspecialchars($item['result']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>
<?php else: ?>
<p class="text-sm text-slate-500">Mijoz tanlanmagan.</p>
<?php endif; ?>
