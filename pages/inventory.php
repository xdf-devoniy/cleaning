<?php
require_login();
authorize(['owner','admin','accountant']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_item') {
        execute('INSERT INTO inventory_items (name, category, stock, min_stock, cost, supplier, maintenance_schedule, serial_number, warranty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            trim($_POST['name']),
            trim($_POST['category']),
            (int)$_POST['stock'],
            (int)$_POST['min_stock'],
            (float)$_POST['cost'],
            trim($_POST['supplier']),
            trim($_POST['maintenance_schedule']),
            trim($_POST['serial_number']),
            trim($_POST['warranty'])
        ]);
        flash('success', 'Inventar qo\'shildi');
    } elseif ($action === 'log_usage') {
        execute('INSERT INTO inventory_logs (item_id, change_qty, reason, job_id) VALUES (?, ?, ?, ?)', [
            (int)$_POST['item_id'],
            (int)$_POST['qty'],
            trim($_POST['reason']),
            $_POST['job_id'] ? (int)$_POST['job_id'] : null
        ]);
        execute('UPDATE inventory_items SET stock = stock + ? WHERE id = ?', [(int)$_POST['qty'], (int)$_POST['item_id']]);
        flash('success', 'Harakat qayd etildi');
    } elseif ($action === 'add_po') {
        execute('INSERT INTO purchase_orders (supplier, total, status, ordered_at, received_at) VALUES (?, ?, ?, ?, ?)', [
            trim($_POST['supplier']),
            (float)$_POST['total'],
            trim($_POST['status']),
            $_POST['ordered_at'],
            $_POST['received_at']
        ]);
        flash('success', 'Buyurtma saqlandi');
    }
    redirect('/index.php?page=inventory');
}

$items = fetch_all('SELECT * FROM inventory_items ORDER BY name');
$logs = fetch_all('SELECT l.*, i.name AS item_name FROM inventory_logs l LEFT JOIN inventory_items i ON i.id = l.item_id ORDER BY l.created_at DESC LIMIT 50');
$pos = fetch_all('SELECT * FROM purchase_orders ORDER BY ordered_at DESC LIMIT 20');
$jobs = fetch_all('SELECT id, scheduled_at FROM jobs ORDER BY scheduled_at DESC LIMIT 50');
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Inventar qo'shish</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="add_item">
            <input name="name" class="border rounded px-3 py-2 w-full" placeholder="Nomi">
            <input name="category" class="border rounded px-3 py-2 w-full" placeholder="Kategoriya">
            <input name="stock" type="number" class="border rounded px-3 py-2 w-full" placeholder="Miqdor">
            <input name="min_stock" type="number" class="border rounded px-3 py-2 w-full" placeholder="Minimal zaxira">
            <input name="cost" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Narx">
            <input name="supplier" class="border rounded px-3 py-2 w-full" placeholder="Yetkazib beruvchi">
            <input name="maintenance_schedule" class="border rounded px-3 py-2 w-full" placeholder="Tex xizmat">
            <input name="serial_number" class="border rounded px-3 py-2 w-full" placeholder="Seriya">
            <input name="warranty" class="border rounded px-3 py-2 w-full" placeholder="Kafolat">
            <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Harakatlar</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="log_usage">
            <select name="item_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?> (<?= $item['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <input name="qty" type="number" class="border rounded px-3 py-2 w-full" placeholder="+/- miqdor">
            <input name="reason" class="border rounded px-3 py-2 w-full" placeholder="Sabab (job/chiqim)">
            <select name="job_id" class="border rounded px-3 py-2 w-full">
                <option value="">Ish bog'lash (ixtiyoriy)</option>
                <?php foreach ($jobs as $job): ?>
                    <option value="<?= $job['id'] ?>">Job #<?= $job['id'] ?> - <?= htmlspecialchars($job['scheduled_at']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="bg-emerald-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
        <h3 class="font-semibold text-sm mt-4">So'nggi loglar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($logs as $log): ?>
                <li><?= htmlspecialchars($log['item_name']) ?>: <?= $log['change_qty'] ?> (<?= htmlspecialchars($log['reason']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Purchase order</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="add_po">
            <input name="supplier" class="border rounded px-3 py-2 w-full" placeholder="Yetkazib beruvchi">
            <input name="total" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Jami">
            <input name="status" class="border rounded px-3 py-2 w-full" placeholder="Holat">
            <input type="date" name="ordered_at" class="border rounded px-3 py-2 w-full">
            <input type="date" name="received_at" class="border rounded px-3 py-2 w-full">
            <button class="bg-indigo-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
        <h3 class="font-semibold text-sm mt-4">Oxirgi buyurtmalar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($pos as $po): ?>
                <li><?= htmlspecialchars($po['supplier']) ?> - <?= format_currency((float)$po['total']) ?> (<?= htmlspecialchars($po['status']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
