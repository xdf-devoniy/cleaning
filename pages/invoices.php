<?php
require_login();
authorize(['owner','admin','accountant']);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="invoices.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Number','Client','Status','Total','Currency','Due date']);
    $rows = fetch_all('SELECT i.*, c.name AS client_name FROM invoices i LEFT JOIN clients c ON c.id = i.client_id ORDER BY i.created_at DESC');
    foreach ($rows as $row) {
        fputcsv($output, [$row['number'], $row['client_name'], $row['status'], $row['total'], $row['currency'], $row['due_date']]);
    }
    exit;
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $user = current_user();

    if ($action === 'create_quote') {
        $clientId = (int)$_POST['client_id'];
        $subtotal = (float)$_POST['subtotal'];
        $taxRate = (float)$_POST['tax'];
        $total = $subtotal + ($subtotal * $taxRate / 100);
        execute('INSERT INTO quotes (client_id, number, status, subtotal, tax, total, currency) VALUES (?, ?, ?, ?, ?, ?, ?)', [
            $clientId,
            trim($_POST['number'] ?? ''),
            'draft',
            $subtotal,
            $taxRate,
            $total,
            $_POST['currency'] ?? 'UZS'
        ]);
        audit_log($user['id'], 'create', 'quote', get_db()->lastInsertId(), 'Taklif yaratildi');
        flash('success', 'Taklif saqlandi');
    } elseif ($action === 'convert_quote') {
        $quoteId = (int)$_POST['quote_id'];
        $quote = fetch_one('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if ($quote) {
            execute('INSERT INTO invoices (client_id, quote_id, number, status, subtotal, tax, total, currency, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $quote['client_id'],
                $quoteId,
                'INV-' . $quote['number'],
                'unpaid',
                $quote['subtotal'],
                $quote['tax'],
                $quote['total'],
                $quote['currency'],
                $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'))
            ]);
            execute('UPDATE quotes SET status = "accepted" WHERE id = ?', [$quoteId]);
            audit_log($user['id'], 'convert', 'invoice', get_db()->lastInsertId(), 'Taklif hisob-fakturaga o\'zgartirildi');
            flash('success', 'Invoice yaratildi');
        }
    } elseif ($action === 'record_payment') {
        $invoiceId = (int)$_POST['invoice_id'];
        $amount = (float)$_POST['amount'];
        $method = $_POST['method'] ?? 'naqd';
        $reference = trim($_POST['reference'] ?? '');
        $receiptPath = null;
        if (!empty($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $receiptPath = store_upload($_FILES['receipt']);
        }
        execute('INSERT INTO invoice_payments (invoice_id, amount, method, paid_at, reference) VALUES (?, ?, ?, ?, ?)', [
            $invoiceId,
            $amount,
            $method,
            $_POST['paid_at'] ?? date('Y-m-d'),
            $receiptPath ? $receiptPath : $reference
        ]);
        $invoice = fetch_one('SELECT * FROM invoices WHERE id = ?', [$invoiceId]);
        $paid = fetch_one('SELECT SUM(amount) AS total FROM invoice_payments WHERE invoice_id = ?', [$invoiceId]);
        $paidTotal = $paid['total'] ?? 0;
        $status = $paidTotal >= $invoice['total'] ? 'paid' : 'partial';
        execute('UPDATE invoices SET status = ?, paid_at = CASE WHEN ? = "paid" THEN CURRENT_TIMESTAMP ELSE paid_at END WHERE id = ?', [$status, $status, $invoiceId]);
        if ($status === 'paid') {
            $percent = (float)get_setting('loyalty_earn_percent', '5');
            $points = (int)round(($invoice['total'] * $percent) / 100);
            if ($points > 0) {
                execute('INSERT INTO loyalty_transactions (client_id, points, type, description, expires_at) VALUES (?, ?, "auto_invoice", ?, DATE("now", ?))', [
                    $invoice['client_id'],
                    $points,
                    'Hisob #' . $invoice['number'],
                    '+' . (int)get_setting('loyalty_expiry_days', '365') . ' days'
                ]);
            }
        }
        flash('success', 'To\'lov qayd etildi');
    } elseif ($action === 'update_status') {
        $invoiceId = (int)$_POST['invoice_id'];
        execute('UPDATE invoices SET status = ? WHERE id = ?', [$_POST['status'], $invoiceId]);
        flash('success', 'Status yangilandi');
    } elseif ($action === 'create_invoice') {
        $clientId = (int)$_POST['client_id'];
        $subtotal = (float)$_POST['subtotal'];
        $tax = (float)$_POST['tax'];
        $total = $subtotal + ($subtotal * $tax / 100) - (float)($_POST['discount'] ?? 0);
        execute('INSERT INTO invoices (client_id, number, status, subtotal, tax, total, currency, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $clientId,
            trim($_POST['number']),
            $_POST['status'] ?? 'unpaid',
            $subtotal,
            $tax,
            $total,
            $_POST['currency'] ?? 'UZS',
            $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'))
        ]);
        flash('success', 'Invoice saqlandi');
    } elseif ($action === 'auto_invoice') {
        $jobs = fetch_all('SELECT j.*, c.name AS client_name FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.status = "completed" AND j.id NOT IN (SELECT job_id FROM inventory_logs WHERE reason = "auto_invoice")');
        foreach ($jobs as $job) {
            execute('INSERT INTO invoices (client_id, number, status, subtotal, tax, total, currency, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $job['client_id'],
                'JOB-' . $job['id'],
                'unpaid',
                100000,
                12,
                112000,
                'UZS',
                date('Y-m-d', strtotime('+5 days'))
            ]);
        }
        flash('success', 'Auto invoice ishga tushdi');
    }
    redirect('/index.php?page=invoices');
}

$clients = fetch_all('SELECT id, name FROM clients ORDER BY name');
$quotes = fetch_all('SELECT q.*, c.name AS client_name FROM quotes q LEFT JOIN clients c ON c.id = q.client_id ORDER BY q.created_at DESC LIMIT 20');
$invoices = fetch_all('SELECT i.*, c.name AS client_name FROM invoices i LEFT JOIN clients c ON c.id = i.client_id ORDER BY i.created_at DESC LIMIT 50');
$payments = fetch_all('SELECT p.*, i.number AS invoice_number FROM invoice_payments p LEFT JOIN invoices i ON i.id = p.invoice_id ORDER BY p.paid_at DESC LIMIT 20');

$gateways = [
    'Payme' => get_setting('gateway_payme', 'Faol'),
    'Click' => get_setting('gateway_click', 'Faol'),
    'Uzum QR' => get_setting('gateway_uzum', 'Faol')
];
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <section class="border rounded p-4 md:col-span-2">
        <h2 class="font-semibold mb-3">Taklif va hisob yaratish</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <form method="post" class="space-y-2">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_quote">
                <h3 class="font-semibold">Taklif</h3>
                <select name="client_id" class="border rounded px-3 py-2">
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="number" placeholder="Quote #" class="border rounded px-3 py-2">
                <input name="subtotal" type="number" step="0.01" placeholder="Summa" class="border rounded px-3 py-2">
                <input name="tax" type="number" placeholder="Soliq %" class="border rounded px-3 py-2" value="12">
                <select name="currency" class="border rounded px-3 py-2">
                    <option>UZS</option>
                    <option>USD</option>
                </select>
                <button class="bg-blue-600 text-white px-3 py-2 rounded">Saqlash</button>
            </form>
            <form method="post" class="space-y-2">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_invoice">
                <h3 class="font-semibold">Hisob</h3>
                <select name="client_id" class="border rounded px-3 py-2">
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="number" placeholder="INV-001" class="border rounded px-3 py-2">
                <input name="subtotal" type="number" step="0.01" placeholder="Summa" class="border rounded px-3 py-2">
                <input name="tax" type="number" placeholder="Soliq %" class="border rounded px-3 py-2" value="12">
                <input name="discount" type="number" placeholder="Chegirma" class="border rounded px-3 py-2" value="0">
                <select name="currency" class="border rounded px-3 py-2">
                    <option>UZS</option>
                    <option>USD</option>
                </select>
                <input type="date" name="due_date" class="border rounded px-3 py-2">
                <select name="status" class="border rounded px-3 py-2">
                    <option value="unpaid">To'lanmagan</option>
                    <option value="draft">Qoralama</option>
                </select>
                <button class="bg-emerald-600 text-white px-3 py-2 rounded">Saqlash</button>
            </form>
        </div>
        <form method="post" class="mt-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="auto_invoice">
            <button class="bg-indigo-500 text-white px-3 py-2 rounded">Takroriy ishlar uchun hisob yaratish</button>
        </form>
    </section>
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">To'lov tizimlari</h2>
        <ul class="text-sm space-y-2">
            <?php foreach ($gateways as $name => $status): ?>
                <li><strong><?= $name ?>:</strong> <?= htmlspecialchars($status) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="text-xs text-slate-500 mt-4">Payme/Click/Uzum integratsiyasi uchun invoiceda QR havolasi avtomatik hosil qilinadi.</p>
        <a class="mt-3 inline-block bg-slate-800 text-white px-3 py-2 rounded" href="?page=invoices&export=csv">CSV eksport</a>
    </section>
</div>

<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h3 class="font-semibold mb-2">Takliflar</h3>
        <ul class="text-sm space-y-2 max-h-60 overflow-y-auto">
            <?php foreach ($quotes as $quote): ?>
                <li class="border rounded px-3 py-2">
                    <div class="font-semibold"><?= htmlspecialchars($quote['number']) ?> - <?= htmlspecialchars($quote['client_name']) ?></div>
                    <p>Status: <?= htmlspecialchars($quote['status']) ?> | Jami: <?= format_currency((float)$quote['total'], $quote['currency']) ?></p>
                    <form method="post" class="mt-2 flex items-center gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="convert_quote">
                        <input type="hidden" name="quote_id" value="<?= $quote['id'] ?>">
                        <input type="date" name="due_date" class="border rounded px-2 py-1 text-xs">
                        <button class="bg-emerald-600 text-white px-2 py-1 rounded text-xs">Invoicega aylantirish</button>
                        <button type="button" class="bg-slate-600 text-white px-2 py-1 rounded text-xs" onclick="window.print()">Chop etish</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="border rounded p-4">
        <h3 class="font-semibold mb-2">To'lovlar</h3>
        <ul class="text-sm space-y-2 max-h-60 overflow-y-auto">
            <?php foreach ($payments as $pay): ?>
                <li class="border rounded px-3 py-2">
                    <div class="font-semibold"><?= htmlspecialchars($pay['invoice_number']) ?> - <?= format_currency((float)$pay['amount']) ?></div>
                    <p><?= htmlspecialchars($pay['method']) ?> | <?= htmlspecialchars($pay['paid_at']) ?></p>
                    <?php if ($pay['reference']): ?>
                        <a class="text-blue-600 underline" href="/<?= htmlspecialchars($pay['reference']) ?>" target="_blank">Chek</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="mt-6 border rounded p-4">
    <h3 class="font-semibold mb-3">Hisoblar</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Mijoz</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Jami</th>
                    <th class="px-3 py-2 text-left">To'lov</th>
                    <th class="px-3 py-2 text-left">Harakatlar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr class="border-b">
                        <td class="px-3 py-2"><?= htmlspecialchars($invoice['number']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($invoice['client_name']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($invoice['status']) ?></td>
                        <td class="px-3 py-2"><?= format_currency((float)$invoice['total'], $invoice['currency']) ?></td>
                        <td class="px-3 py-2">
                            <form method="post" enctype="multipart/form-data" class="flex flex-col gap-1 text-xs">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="record_payment">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                                <div class="flex gap-1">
                                    <input name="amount" type="number" step="0.01" placeholder="Summ" class="border rounded px-2 py-1 w-24">
                                    <input name="paid_at" type="date" class="border rounded px-2 py-1 w-32">
                                </div>
                                <select name="method" class="border rounded px-2 py-1">
                                    <option>Payme</option>
                                    <option>Click</option>
                                    <option>Uzum QR</option>
                                    <option>Naqd</option>
                                </select>
                                <input name="reference" placeholder="Chek" class="border rounded px-2 py-1">
                                <input type="file" name="receipt" class="text-xs">
                                <button class="bg-emerald-500 text-white px-2 py-1 rounded">To'lov qo'shish</button>
                            </form>
                        </td>
                        <td class="px-3 py-2">
                            <form method="post" class="flex gap-2 text-xs">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                                <select name="status" class="border rounded px-2 py-1">
                                    <option value="paid" <?= $invoice['status'] === 'paid' ? 'selected' : '' ?>>To'langan</option>
                                    <option value="unpaid" <?= $invoice['status'] === 'unpaid' ? 'selected' : '' ?>>To'lanmagan</option>
                                    <option value="overdue" <?= $invoice['status'] === 'overdue' ? 'selected' : '' ?>>Kechikkan</option>
                                    <option value="partial" <?= $invoice['status'] === 'partial' ? 'selected' : '' ?>>Qisman</option>
                                </select>
                                <button class="bg-slate-700 text-white px-2 py-1 rounded">Saqlash</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
