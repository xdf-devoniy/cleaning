<?php
require_login();
authorize(['owner','admin','accountant','dispatcher']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $userId = current_user()['id'];

    if ($action === 'add_client') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tag' => trim($_POST['tag'] ?? ''),
            'lead_status' => $_POST['lead_status'] ?? 'yangi',
            'notes' => trim($_POST['notes'] ?? ''),
            'last_activity' => parse_date($_POST['last_activity'] ?? null)
        ];
        $errors = validate_required($data, ['name' => 'Ism']);
        if (!$errors) {
            execute('INSERT INTO clients (name, phone, email, tag, lead_status, notes, last_activity) VALUES (:name,:phone,:email,:tag,:lead_status,:notes,:last_activity)', $data);
            $clientId = get_db()->lastInsertId();
            audit_log($userId, 'create', 'client', $clientId, 'Mijoz qo\'shildi');
            flash('success', 'Mijoz muvaffaqiyatli qo\'shildi');
        } else {
            flash('error', implode(', ', $errors));
        }
    } elseif ($action === 'update_client') {
        $clientId = (int)$_POST['client_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tag' => trim($_POST['tag'] ?? ''),
            'lead_status' => $_POST['lead_status'] ?? 'yangi',
            'notes' => trim($_POST['notes'] ?? '')
        ];
        execute('UPDATE clients SET name=:name, phone=:phone, email=:email, tag=:tag, lead_status=:lead_status, notes=:notes WHERE id=:id', $data + ['id' => $clientId]);
        audit_log($userId, 'update', 'client', $clientId, 'Mijoz ma\'lumotlari yangilandi');
        flash('success', 'Ma\'lumotlar saqlandi');
    } elseif ($action === 'delete_client') {
        $clientId = (int)$_POST['client_id'];
        execute('DELETE FROM clients WHERE id=?', [$clientId]);
        audit_log($userId, 'delete', 'client', $clientId, 'Mijoz o\'chirildi');
        flash('success', 'Mijoz o\'chirildi');
    } elseif ($action === 'merge_duplicates') {
        $phone = trim($_POST['phone'] ?? '');
        if ($phone) {
            $clients = fetch_all('SELECT * FROM clients WHERE phone = ? ORDER BY id ASC', [$phone]);
            if (count($clients) > 1) {
                $primary = array_shift($clients);
                foreach ($clients as $duplicate) {
                    execute('UPDATE client_addresses SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('UPDATE client_documents SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('UPDATE client_notes SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('UPDATE communications SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('UPDATE invoices SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('UPDATE loyalty_transactions SET client_id=? WHERE client_id=?', [$primary['id'], $duplicate['id']]);
                    execute('DELETE FROM clients WHERE id=?', [$duplicate['id']]);
                }
                audit_log($userId, 'merge', 'client', $primary['id'], 'Telefon raqam bo\'yicha dublikatlar birlashtirildi');
                flash('success', 'Dublikatlar birlashtirildi');
            } else {
                flash('error', 'Ushbu raqam bo\'yicha dublikat topilmadi');
            }
        }
    } elseif ($action === 'add_note') {
        $clientId = (int)$_POST['client_id'];
        execute('INSERT INTO client_notes (client_id, note, reminder_at) VALUES (?, ?, ?)', [$clientId, trim($_POST['note'] ?? ''), parse_date($_POST['reminder_at'] ?? null)]);
        audit_log($userId, 'create', 'client_note', $clientId, 'Mijoz eslatmasi qo\'shildi');
        flash('success', 'Eslatma saqlandi');
    } elseif ($action === 'add_comm') {
        $clientId = (int)$_POST['client_id'];
        execute('INSERT INTO communications (client_id, channel, summary, occurred_at) VALUES (?, ?, ?, ?)', [$clientId, $_POST['channel'] ?? 'Telegram', trim($_POST['summary'] ?? ''), $_POST['occurred_at'] ?? date('Y-m-d H:i:s')]);
        flash('success', 'Aloqa yozuvi qo\'shildi');
    } elseif ($action === 'upload_doc' && !empty($_FILES['document'])) {
        $clientId = (int)$_POST['client_id'];
        $path = store_upload($_FILES['document']);
        if ($path) {
            execute('INSERT INTO client_documents (client_id, path, description) VALUES (?, ?, ?)', [$clientId, $path, trim($_POST['description'] ?? '')]);
            flash('success', 'Fayl saqlandi');
        } else {
            flash('error', 'Fayl yuklashda xatolik');
        }
    }

    redirect('/index.php?page=clients');
}

$search = trim($_GET['search'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$where = [];
$params = [];
if ($search) {
    $where[] = '(name LIKE :search OR email LIKE :search)';
    $params['search'] = "%$search%";
}
if ($tag) {
    $where[] = 'tag = :tag';
    $params['tag'] = $tag;
}
if ($phone) {
    $where[] = 'phone LIKE :phone';
    $params['phone'] = "%$phone%";
}
$sql = 'SELECT * FROM clients';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC LIMIT 120';
$clients = fetch_all($sql, $params);
$leadStatuses = ['yangi','malakali','taklif','yutildi','yo\'qotildi'];
$grouped = [];
foreach ($clients as $client) {
    $grouped[$client['lead_status']][] = $client;
}
$loyaltyBalances = fetch_all('SELECT client_id, SUM(points) AS points FROM loyalty_transactions GROUP BY client_id');
$loyaltyMap = [];
foreach ($loyaltyBalances as $row) {
    $loyaltyMap[$row['client_id']] = (int)$row['points'];
}
?>
<div class="space-y-8 text-slate-800">
    <form method="get" class="rounded-3xl border border-slate-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 p-6 shadow-lg">
        <input type="hidden" name="page" value="clients">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="text-xs uppercase text-slate-500">Ism yoki email</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-sky-500 focus:outline-none" placeholder="Mijozni toping">
            </div>
            <div>
                <label class="text-xs uppercase text-slate-500">Teg</label>
                <input type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-sky-500 focus:outline-none" placeholder="VIP, corporate...">
            </div>
            <div>
                <label class="text-xs uppercase text-slate-500">Telefon</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-sky-500 focus:outline-none" placeholder="998...">
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-2xl bg-sky-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20">Intellektual qidiruv</button>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <?php foreach ($leadStatuses as $status): ?>
                <span class="rounded-full border border-slate-200 px-3 py-1 text-slate-500"><?= ucfirst($status) ?></span>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="grid gap-6 xl:grid-cols-[360px,1fr]">
        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                <h2 class="text-lg font-semibold text-slate-900">Yangi mijoz qo'shish</h2>
                <p class="mt-2 text-sm text-slate-500">CRM ichida tafsilotlar, eslatmalar va loyallikni boshqarish.</p>
                <form method="post" class="mt-4 space-y-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="add_client">
                    <input name="name" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Ism" required>
                    <div class="grid grid-cols-2 gap-3">
                        <input name="phone" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Telefon">
                        <input name="email" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Email">
                    </div>
                    <input name="tag" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Teg">
                    <select name="lead_status" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm">
                        <?php foreach ($leadStatuses as $status): ?>
                            <option value="<?= $status ?>"><?= ucfirst($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="notes" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Afzalliklar, talablar" rows="3"></textarea>
                    <div>
                        <label class="text-xs uppercase text-slate-500">So'nggi faoliyat</label>
                        <input type="date" name="last_activity" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm">
                    </div>
                    <button class="w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20">CRMga qo'shish</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                <h3 class="text-base font-semibold text-slate-900">Dublikatlarni boshqarish</h3>
                <p class="mt-2 text-sm text-slate-500">Telefon raqam orqali avtomatik birlashtirish.</p>
                <form method="post" class="mt-3 flex gap-2">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="merge_duplicates">
                    <input name="phone" placeholder="Telefon" class="flex-1 rounded-2xl border border-slate-200 px-4 py-2 text-sm" required>
                    <button class="rounded-2xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow shadow-amber-500/30">Birlashtirish</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                <h3 class="text-base font-semibold text-slate-900">Analitik tezkor ko'rinish</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><span class="font-semibold text-slate-900"><?= count($clients) ?></span> ta filtrlangan mijoz</li>
                    <li><span class="font-semibold text-emerald-600"><?= number_format(array_sum($loyaltyMap)) ?></span> ball yig'ildi</li>
                    <li><span class="font-semibold text-sky-600"><?= fetch_one('SELECT COUNT(*) AS c FROM clients WHERE lead_status = "taklif"')['c'] ?? 0 ?></span> ta taklif bosqichida</li>
                </ul>
            </div>
        </aside>

        <section class="space-y-6">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                <h2 class="text-lg font-semibold text-slate-900">Pipeline ko'rinishi</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <?php foreach ($leadStatuses as $status):
                        $column = $grouped[$status] ?? [];
                        ?>
                        <div class="flex flex-col rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-700"><?= ucfirst($status) ?></h3>
                                <span class="rounded-full bg-white px-3 py-1 text-xs text-slate-500"><?= count($column) ?></span>
                            </div>
                            <div class="mt-3 space-y-3">
                                <?php foreach (array_slice($column, 0, 4) as $item): ?>
                                    <a href="#client-<?= $item['id'] ?>" class="block rounded-2xl bg-white px-3 py-2 text-sm text-slate-600 shadow-sm hover:shadow">
                                        <span class="block font-semibold text-slate-800"><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="text-xs text-slate-500"><?= htmlspecialchars($item['phone'] ?? '-') ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (!$column): ?>
                                    <p class="text-xs text-slate-400">Bo'sh</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-6">
                <?php foreach ($clients as $client):
                    $clientNotes = fetch_all('SELECT * FROM client_notes WHERE client_id = ? ORDER BY created_at DESC', [$client['id']]);
                    $clientComms = fetch_all('SELECT * FROM communications WHERE client_id = ? ORDER BY occurred_at DESC', [$client['id']]);
                    $clientDocs = fetch_all('SELECT * FROM client_documents WHERE client_id = ? ORDER BY uploaded_at DESC', [$client['id']]);
                    $balance = fetch_one('SELECT SUM(t.total) - SUM(t.paid_amount) AS bal FROM (
                        SELECT i.total AS total, COALESCE((SELECT SUM(amount) FROM invoice_payments WHERE invoice_id = i.id),0) AS paid_amount
                        FROM invoices i WHERE i.client_id = ?
                    ) AS t', [$client['id']]);
                    $loyalty = $loyaltyMap[$client['id']] ?? 0;
                    ?>
                    <article id="client-<?= $client['id'] ?>" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-lg">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-xl font-semibold text-slate-900"><?= htmlspecialchars($client['name']) ?></h3>
                                    <span class="rounded-full bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-600"><?= htmlspecialchars($client['lead_status']) ?></span>
                                    <?php if ($client['tag']): ?>
                                        <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600"><?= htmlspecialchars($client['tag']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">So'nggi faoliyat: <?= htmlspecialchars($client['last_activity'] ?? 'noma\'lum') ?></p>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-3 py-1">Balans: <?= format_currency((float)($balance['bal'] ?? 0)) ?></span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1">Loyallik: <?= $loyalty ?> ball</span>
                                    <a href="<?= htmlspecialchars(app_url('index.php?page=client_card&client_id=' . $client['id'])) ?>" class="rounded-full bg-indigo-500/10 px-3 py-1 font-medium text-indigo-600">360° karta</a>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <form method="post" class="hidden" id="delete-client-<?= $client['id'] ?>">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_client">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                </form>
                                <button form="delete-client-<?= $client['id'] ?>" onclick="return confirm('O\'chirishni tasdiqlaysizmi?')" class="rounded-2xl bg-rose-500/10 px-4 py-2 text-xs font-semibold text-rose-600">O'chirish</button>
                            </div>
                        </div>

                        <form method="post" class="mt-6 grid gap-4 md:grid-cols-2">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="update_client">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                            <div>
                                <label class="text-xs uppercase text-slate-500">Telefon</label>
                                <input name="phone" value="<?= htmlspecialchars($client['phone']) ?>" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Telefon">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-slate-500">Email</label>
                                <input name="email" value="<?= htmlspecialchars($client['email']) ?>" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Email">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-slate-500">Ism</label>
                                <input name="name" value="<?= htmlspecialchars($client['name']) ?>" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Ism">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-slate-500">Teg</label>
                                <input name="tag" value="<?= htmlspecialchars($client['tag']) ?>" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" placeholder="Teg">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-slate-500">Lead status</label>
                                <select name="lead_status" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm">
                                    <?php foreach ($leadStatuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $client['lead_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs uppercase text-slate-500">Afzalliklar</label>
                                <textarea name="notes" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm" rows="3" placeholder="Afzalliklar, eslatmalar"><?= htmlspecialchars($client['notes']) ?></textarea>
                            </div>
                            <div class="md:col-span-2 flex flex-wrap gap-3">
                                <button class="rounded-2xl bg-sky-500 px-4 py-2 text-xs font-semibold text-white shadow shadow-sky-500/30">Ma'lumotni yangilash</button>
                                <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&client_id=' . $client['id'])) ?>" class="rounded-2xl bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-600">Ish rejalashtirish</a>
                                <a href="<?= htmlspecialchars(app_url('index.php?page=jobs&client_id=' . $client['id'] . '&date=' . date('Y-m-d'))) ?>" class="rounded-2xl bg-indigo-500/10 px-4 py-2 text-xs font-semibold text-indigo-600">To'lov holati</a>
                            </div>
                        </form>

                        <div class="mt-6 grid gap-6 lg:grid-cols-3">
                            <section class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-800">Eslatmalar</h4>
                                    <span class="text-xs text-slate-400"><?= count($clientNotes) ?> ta</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-600 max-h-48 overflow-y-auto">
                                    <?php foreach ($clientNotes as $note): ?>
                                        <li class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p><?= htmlspecialchars($note['note']) ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($note['reminder_at']) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (!$clientNotes): ?>
                                        <li class="text-xs text-slate-400">Eslatma yo'q</li>
                                    <?php endif; ?>
                                </ul>
                                <form method="post" class="space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="add_note">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <textarea name="note" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Yangi eslatma"></textarea>
                                    <input type="date" name="reminder_at" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                    <button class="rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-white">Qo'shish</button>
                                </form>
                            </section>

                            <section class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-800">Aloqa loglari</h4>
                                    <span class="text-xs text-slate-400"><?= count($clientComms) ?> ta</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-600 max-h-48 overflow-y-auto">
                                    <?php foreach ($clientComms as $comm): ?>
                                        <li class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p class="font-semibold text-slate-700"><?= htmlspecialchars($comm['channel']) ?></p>
                                            <p><?= htmlspecialchars($comm['summary']) ?></p>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($comm['occurred_at']) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (!$clientComms): ?>
                                        <li class="text-xs text-slate-400">Aloqa yozuvi yo'q</li>
                                    <?php endif; ?>
                                </ul>
                                <form method="post" class="space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="add_comm">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <select name="channel" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                        <option>Qo'ng'iroq</option>
                                        <option>Telegram</option>
                                        <option>WhatsApp</option>
                                        <option>SMS</option>
                                    </select>
                                    <textarea name="summary" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Qisqa mazmun"></textarea>
                                    <input type="datetime-local" name="occurred_at" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                    <button class="rounded-2xl bg-indigo-500 px-4 py-2 text-xs font-semibold text-white">Yozib qo'yish</button>
                                </form>
                            </section>

                            <section class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-800">Fayl xotirasi</h4>
                                    <span class="text-xs text-slate-400"><?= count($clientDocs) ?> ta</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-600 max-h-48 overflow-y-auto">
                                    <?php foreach ($clientDocs as $doc): ?>
                                        <li class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
                                            <a class="truncate text-sky-600 hover:underline" href="/<?= htmlspecialchars($doc['path']) ?>" target="_blank"><?= htmlspecialchars($doc['description'] ?: $doc['path']) ?></a>
                                            <span class="text-xs text-slate-400"><?= htmlspecialchars($doc['uploaded_at']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (!$clientDocs): ?>
                                        <li class="text-xs text-slate-400">Fayl yuklanmagan</li>
                                    <?php endif; ?>
                                </ul>
                                <form method="post" enctype="multipart/form-data" class="space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="upload_doc">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <input type="file" name="document" class="w-full text-sm text-slate-600">
                                    <input type="text" name="description" placeholder="Izoh" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                                    <button class="rounded-2xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white">Yuklash</button>
                                </form>
                            </section>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$clients): ?>
                    <p class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Mijoz topilmadi</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
