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
$openJobs = array_filter($jobs, fn($job) => in_array($job['status'], ['new','scheduled']));
?>
<div class="space-y-8 text-slate-800">
    <form method="get" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
        <input type="hidden" name="page" value="client_card">
        <div class="grid gap-4 md:grid-cols-[minmax(0,320px),auto] md:items-end">
            <div>
                <label class="text-xs uppercase text-slate-500">Mijozni tanlang</label>
                <select name="client_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-sky-500 focus:outline-none">
                    <option value="">-- tanlash --</option>
                    <?php foreach ($clients as $item): ?>
                        <option value="<?= $item['id'] ?>" <?= $clientId === (int)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-3">
                <button class="rounded-2xl bg-sky-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20">Profilni yuklash</button>
                <?php if ($client): ?>
                    <a href="<?= htmlspecialchars(app_url('index.php?page=clients&search=' . urlencode($client['name']))) ?>" class="rounded-2xl bg-slate-900/5 px-4 py-3 text-sm font-semibold text-slate-600">CRMga qaytish</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if ($client): ?>
        <section class="rounded-3xl border border-slate-100 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-8 text-white shadow-xl">
            <div class="flex flex-col gap-6 lg:flex-row lg:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-white/50">Mijoz profili</p>
                    <h2 class="mt-2 text-3xl font-semibold"><?= htmlspecialchars($client['name']) ?></h2>
                    <p class="mt-2 text-sm text-white/70">Telefon: <?= htmlspecialchars($client['phone'] ?? '-') ?> • Email: <?= htmlspecialchars($client['email'] ?? '-') ?></p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-white/70">
                        <span class="rounded-full bg-white/10 px-3 py-1">Status: <?= htmlspecialchars($client['lead_status']) ?></span>
                        <?php if ($client['tag']): ?><span class="rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-100">Teg: <?= htmlspecialchars($client['tag']) ?></span><?php endif; ?>
                        <span class="rounded-full bg-white/10 px-3 py-1">So'nggi faoliyat: <?= htmlspecialchars($lastActivity ?: 'Mavjud emas') ?></span>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-xs uppercase text-white/60">LTV</p>
                        <p class="mt-2 text-2xl font-semibold"><?= format_currency((float)$totalRevenue, $invoices[0]['currency'] ?? 'UZS') ?></p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-xs uppercase text-white/60">Balans</p>
                        <p class="mt-2 text-2xl font-semibold"><?= format_currency((float)$balance, $invoices[0]['currency'] ?? 'UZS') ?></p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-xs uppercase text-white/60">Loyallik</p>
                        <p class="mt-2 text-2xl font-semibold"><?= $points ?> ball</p>
                        <p class="text-xs text-white/60"><?= htmlspecialchars($tier['name'] ?? 'Bronza') ?></p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3 text-xs text-white/70">
                <span class="rounded-full bg-sky-500/20 px-3 py-1">Faol rejalar: <?= count($openJobs) ?></span>
                <span class="rounded-full bg-emerald-500/20 px-3 py-1">Referallar: <?= count($referrals) ?></span>
                <span class="rounded-full bg-white/10 px-3 py-1">Fayllar: <?= count($files) ?></span>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-slate-900">Afzalliklar va aloqa ma'lumotlari</h3>
                    <div class="mt-4 grid gap-6 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <h4 class="text-sm font-semibold text-slate-800">Kontaktlar</h4>
                                <p class="text-sm text-slate-600">Telefon: <?= htmlspecialchars($client['phone'] ?? '-') ?></p>
                                <p class="text-sm text-slate-600">Email: <?= htmlspecialchars($client['email'] ?? '-') ?></p>
                                <p class="text-sm text-slate-600">Teg: <?= htmlspecialchars($client['tag'] ?? '-') ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <h4 class="text-sm font-semibold text-slate-800">Eslatmalar</h4>
                                <ul class="mt-2 space-y-2 text-sm text-slate-600 max-h-40 overflow-y-auto">
                                    <?php foreach ($notes as $note): ?>
                                        <li class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p><?= htmlspecialchars($note['note']) ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($note['created_at']) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (!$notes): ?><li class="text-xs text-slate-400">Eslatma kiritilmagan</li><?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <h4 class="text-sm font-semibold text-slate-800">Afzalliklarni yangilash</h4>
                            <form method="post" class="mt-3 space-y-3">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="save_preferences">
                                <textarea name="preferences" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" rows="6" placeholder="Afzalliklar va nozik talablar"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
                                <button class="rounded-2xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow shadow-sky-500/30">Saqlash</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Manzillar va joylar</h3>
                        <span class="rounded-full bg-slate-900/5 px-3 py-1 text-xs text-slate-500"><?= count($addresses) ?> ta manzil</span>
                    </div>
                    <div class="mt-4 space-y-4">
                        <?php foreach ($addresses as $address): ?>
                            <form method="post" class="rounded-2xl border border-slate-200 p-4 grid gap-3 md:grid-cols-4">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="update_address">
                                <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                <input name="label" value="<?= htmlspecialchars($address['label']) ?>" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nomi">
                                <input name="address" value="<?= htmlspecialchars($address['address']) ?>" class="md:col-span-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Manzil">
                                <input name="preferences" value="<?= htmlspecialchars($address['preferences']) ?>" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Afzallik">
                                <button class="rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-white">Yangilash</button>
                            </form>
                        <?php endforeach; ?>
                        <form method="post" class="rounded-2xl border border-dashed border-slate-200 p-4 grid gap-3 md:grid-cols-4">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="add_address">
                            <input name="label" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nomi">
                            <input name="address" class="md:col-span-2 rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Manzil">
                            <input name="preferences" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Afzallik">
                            <button class="rounded-2xl bg-sky-500 px-4 py-2 text-xs font-semibold text-white">Qo'shish</button>
                        </form>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Faoliyat taymlayni</h3>
                        <span class="rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-600"><?= count($jobs) ?> ta ish</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($jobs as $job): ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($job['scheduled_at']) ?> • <?= htmlspecialchars($job['service_name'] ?? 'Xizmat') ?></p>
                                        <p class="text-xs text-slate-500">Status: <?= htmlspecialchars($job['status']) ?> • Brigada: <?= htmlspecialchars($job['crew'] ?? '-') ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a class="rounded-2xl bg-sky-500/10 px-3 py-1 text-xs font-semibold text-sky-600" href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . date('Y-m-d', strtotime($job['scheduled_at'] ?? 'now')) . '&focus=' . $job['id'])) ?>#job-<?= $job['id'] ?>">Jadval</a>
                                        <a class="rounded-2xl bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600" href="<?= htmlspecialchars(app_url('index.php?page=jobs&date=' . date('Y-m-d', strtotime($job['scheduled_at'] ?? 'now')) . '&focus=' . $job['id'])) ?>#job-<?= $job['id'] ?>">To'lov</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$jobs): ?><p class="text-sm text-slate-500">Ishlar hali yaratilmagan.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-slate-900">Moliyaviy ko'rinish</h3>
                    <p class="mt-2 text-sm text-slate-600">Jami hisob: <?= format_currency((float)$totalRevenue) ?></p>
                    <p class="text-sm text-slate-600">To'langan: <?= format_currency((float)$totalPaid) ?></p>
                    <p class="text-sm text-slate-600">Qoldiq: <?= format_currency((float)$balance) ?></p>
                    <h4 class="mt-4 text-sm font-semibold text-slate-800">Hisoblar</h4>
                    <ul class="mt-2 space-y-2 text-sm text-slate-600 max-h-40 overflow-y-auto">
                        <?php foreach ($invoices as $invoice): ?>
                            <li class="flex items-center justify-between gap-3">
                                <span>#<?= htmlspecialchars($invoice['number']) ?> • <?= htmlspecialchars($invoice['status']) ?></span>
                                <span><?= format_currency((float)$invoice['total'], $invoice['currency']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (!$invoices): ?><li class="text-xs text-slate-400">Invoys topilmadi</li><?php endif; ?>
                    </ul>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-slate-900">Loyallik va referallar</h3>
                    <p class="mt-2 text-sm text-slate-600">Ballar: <?= $points ?></p>
                    <p class="text-sm text-slate-600">Daraja: <?= htmlspecialchars($tier['name'] ?? 'Bronza') ?></p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600 max-h-32 overflow-y-auto">
                        <?php foreach ($loyalty as $txn): ?>
                            <li><?= htmlspecialchars($txn['created_at']) ?> • <?= $txn['points'] ?> (<?= htmlspecialchars($txn['type']) ?>)</li>
                        <?php endforeach; ?>
                        <?php if (!$loyalty): ?><li class="text-xs text-slate-400">Loyallik tranzaksiyasi yo'q</li><?php endif; ?>
                    </ul>
                    <h4 class="mt-4 text-sm font-semibold text-slate-800">Referal tarixi</h4>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($referrals as $ref): ?>
                            <li><?= htmlspecialchars($ref['description'] ?? 'Referal') ?> • <?= $ref['points'] ?> ball</li>
                        <?php endforeach; ?>
                        <?php if (!$referrals): ?><li class="text-xs text-slate-400">Referal yo'q</li><?php endif; ?>
                    </ul>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-slate-900">Fayllar va QC natijalari</h3>
                    <ul class="space-y-2 text-sm text-slate-600 max-h-32 overflow-y-auto">
                        <?php foreach ($files as $file): ?>
                            <li class="flex items-center justify-between gap-3">
                                <a class="truncate text-sky-600 hover:underline" href="/<?= htmlspecialchars($file['path']) ?>" target="_blank"><?= htmlspecialchars($file['description'] ?: $file['path']) ?></a>
                                <span class="text-xs text-slate-400"><?= htmlspecialchars($file['uploaded_at']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (!$files): ?><li class="text-xs text-slate-400">Fayl yuklanmagan</li><?php endif; ?>
                    </ul>
                    <h4 class="mt-4 text-sm font-semibold text-slate-800">QC natijalari</h4>
                    <?php $qc = fetch_all('SELECT q.*, c.name AS checklist FROM job_qc q LEFT JOIN checklists c ON c.id = q.checklist_id WHERE q.job_id IN (SELECT id FROM jobs WHERE client_id = ?) ORDER BY q.created_at DESC LIMIT 5', [$clientId]); ?>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($qc as $item): ?>
                            <li><?= htmlspecialchars($item['created_at']) ?> • <?= htmlspecialchars($item['checklist'] ?? 'QC') ?> • <?= htmlspecialchars($item['result']) ?></li>
                        <?php endforeach; ?>
                        <?php if (!$qc): ?><li class="text-xs text-slate-400">QC qaydlari yo'q</li><?php endif; ?>
                    </ul>
                </div>
            </aside>
        </section>
    <?php else: ?>
        <p class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Mijoz tanlanmagan.</p>
    <?php endif; ?>
</div>
