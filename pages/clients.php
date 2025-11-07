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
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$clients = fetch_all($sql, $params);
$leadStatuses = ['yangi','malakali','taklif','yutildi','yo\'qotildi'];
?>
<form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <input type="hidden" name="page" value="clients">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ism yoki email" class="border rounded px-3 py-2">
    <input type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" placeholder="Teg" class="border rounded px-3 py-2">
    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="Telefon" class="border rounded px-3 py-2">
    <button class="bg-blue-600 text-white rounded px-3 py-2">Qidirish</button>
</form>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <h2 class="font-semibold mb-3">Yangi mijoz qo'shish</h2>
        <form method="post" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="add_client">
            <input name="name" class="w-full border rounded px-3 py-2" placeholder="Ism" required>
            <input name="phone" class="w-full border rounded px-3 py-2" placeholder="Telefon">
            <input name="email" class="w-full border rounded px-3 py-2" placeholder="Email">
            <input name="tag" class="w-full border rounded px-3 py-2" placeholder="Teg">
            <select name="lead_status" class="w-full border rounded px-3 py-2">
                <?php foreach ($leadStatuses as $status): ?>
                    <option value="<?= $status ?>"><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="notes" class="w-full border rounded px-3 py-2" placeholder="Izoh"></textarea>
            <label class="block text-sm">So'nggi faoliyat</label>
            <input type="date" name="last_activity" class="w-full border rounded px-3 py-2">
            <button class="bg-emerald-600 text-white rounded px-3 py-2">Saqlash</button>
        </form>

        <div class="mt-6">
            <h3 class="font-semibold mb-2">Telefon bo'yicha dublikatlarni birlashtirish</h3>
            <form method="post" class="flex gap-2">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="merge_duplicates">
                <input name="phone" placeholder="Telefon" class="border rounded px-3 py-2 flex-1" required>
                <button class="bg-amber-500 text-white rounded px-3 py-2">Birlashtirish</button>
            </form>
        </div>
    </div>
    <div>
        <h2 class="font-semibold mb-3">Mijozlar ro'yxati</h2>
        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
            <?php foreach ($clients as $client): ?>
                <details class="border rounded">
                    <summary class="px-3 py-2 flex justify-between items-center cursor-pointer">
                        <span>
                            <span class="font-semibold"><?= htmlspecialchars($client['name']) ?></span>
                            <span class="text-xs text-slate-500 ml-2"><?= htmlspecialchars($client['lead_status']) ?></span>
                        </span>
                        <span class="text-xs text-slate-500"><?= htmlspecialchars($client['phone'] ?? '') ?></span>
                    </summary>
                    <div class="px-3 py-4 space-y-4">
                        <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="update_client">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                            <input name="name" value="<?= htmlspecialchars($client['name']) ?>" class="border rounded px-3 py-2" placeholder="Ism">
                            <input name="phone" value="<?= htmlspecialchars($client['phone']) ?>" class="border rounded px-3 py-2" placeholder="Telefon">
                            <input name="email" value="<?= htmlspecialchars($client['email']) ?>" class="border rounded px-3 py-2" placeholder="Email">
                            <input name="tag" value="<?= htmlspecialchars($client['tag']) ?>" class="border rounded px-3 py-2" placeholder="Teg">
                            <select name="lead_status" class="border rounded px-3 py-2">
                                <?php foreach ($leadStatuses as $status): ?>
                                    <option value="<?= $status ?>" <?= $client['lead_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="notes" class="border rounded px-3 py-2 md:col-span-2" placeholder="Izoh"><?= htmlspecialchars($client['notes']) ?></textarea>
                            <div class="md:col-span-2 flex gap-2">
                                <button class="bg-blue-600 text-white px-3 py-2 rounded">Yangilash</button>
                                <button form="delete-client-<?= $client['id'] ?>" class="bg-rose-500 text-white px-3 py-2 rounded" onclick="return confirm('O\'chirishni tasdiqlaysizmi?')">O'chirish</button>
                            </div>
                        </form>
                        <form id="delete-client-<?= $client['id'] ?>" method="post" class="hidden">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="delete_client">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                        </form>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-semibold text-sm">Eslatmalar</h4>
                                <ul class="text-sm space-y-2 max-h-40 overflow-y-auto">
                                    <?php $notes = fetch_all('SELECT * FROM client_notes WHERE client_id = ? ORDER BY created_at DESC', [$client['id']]); ?>
                                    <?php foreach ($notes as $note): ?>
                                        <li class="border rounded px-2 py-1">
                                            <p><?= htmlspecialchars($note['note']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($note['reminder_at']) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <form method="post" class="mt-2 space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="add_note">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <textarea name="note" class="border rounded px-2 py-1 w-full" placeholder="Yangi eslatma"></textarea>
                                    <input type="date" name="reminder_at" class="border rounded px-2 py-1 w-full">
                                    <button class="bg-emerald-500 text-white px-3 py-1 rounded">Qo'shish</button>
                                </form>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm">Aloqa loglari</h4>
                                <ul class="text-sm space-y-2 max-h-40 overflow-y-auto">
                                    <?php $comms = fetch_all('SELECT * FROM communications WHERE client_id = ? ORDER BY occurred_at DESC', [$client['id']]); ?>
                                    <?php foreach ($comms as $comm): ?>
                                        <li class="border rounded px-2 py-1">
                                            <p class="font-semibold"><?= htmlspecialchars($comm['channel']) ?></p>
                                            <p><?= htmlspecialchars($comm['summary']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($comm['occurred_at']) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <form method="post" class="mt-2 space-y-2">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="add_comm">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <select name="channel" class="border rounded px-2 py-1 w-full">
                                        <option>Qo'ng'iroq</option>
                                        <option>Telegram</option>
                                        <option>WhatsApp</option>
                                        <option>SMS</option>
                                    </select>
                                    <textarea name="summary" class="border rounded px-2 py-1 w-full" placeholder="Qisqa mazmun"></textarea>
                                    <input type="datetime-local" name="occurred_at" class="border rounded px-2 py-1 w-full">
                                    <button class="bg-indigo-500 text-white px-3 py-1 rounded">Yozib qo'yish</button>
                                </form>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-sm">Fayllar</h4>
                            <ul class="text-sm space-y-1">
                                <?php $docs = fetch_all('SELECT * FROM client_documents WHERE client_id = ? ORDER BY uploaded_at DESC', [$client['id']]); ?>
                                <?php foreach ($docs as $doc): ?>
                                    <li>
                                        <a class="text-blue-600 underline" href="/<?= htmlspecialchars($doc['path']) ?>" target="_blank"><?= htmlspecialchars($doc['description'] ?: $doc['path']) ?></a>
                                        <span class="text-xs text-slate-500 ml-2"><?= htmlspecialchars($doc['uploaded_at']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="post" enctype="multipart/form-data" class="mt-2 flex gap-2 items-center">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="upload_doc">
                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                <input type="file" name="document" class="text-sm">
                                <input type="text" name="description" placeholder="Izoh" class="border rounded px-2 py-1">
                                <button class="bg-slate-700 text-white px-3 py-1 rounded">Yuklash</button>
                            </form>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
            <?php if (!$clients): ?>
                <p class="text-sm text-slate-500">Mijoz topilmadi</p>
            <?php endif; ?>
        </div>
    </div>
</div>
