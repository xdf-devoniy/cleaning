<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$token = $_GET['token'] ?? '';
$record = $token ? fetch_one('SELECT t.*, c.name, c.id AS client_id FROM client_portal_tokens t JOIN clients c ON c.id = t.client_id WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at >= DATE("now"))', [$token]) : null;
if (!$record) {
    http_response_code(404);
    exit('Havola topilmadi yoki muddati tugagan.');
}
$clientId = $record['client_id'];
$client = fetch_one('SELECT * FROM clients WHERE id = ?', [$clientId]);
$jobs = fetch_all('SELECT * FROM jobs WHERE client_id = ? ORDER BY scheduled_at DESC LIMIT 20', [$clientId]);
$invoices = fetch_all('SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC LIMIT 20', [$clientId]);
$loyalty = fetch_all('SELECT * FROM loyalty_transactions WHERE client_id = ? ORDER BY created_at DESC', [$clientId]);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijoz portali</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="max-w-4xl mx-auto py-6 px-4">
    <h1 class="text-2xl font-bold mb-4">Salom, <?= htmlspecialchars($client['name']) ?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <section class="border rounded p-4 bg-white">
            <h2 class="font-semibold mb-2">Bronlar</h2>
            <ul class="text-sm space-y-1 max-h-48 overflow-y-auto">
                <?php foreach ($jobs as $job): ?>
                    <li><?= htmlspecialchars($job['scheduled_at']) ?> - <?= htmlspecialchars($job['status']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section class="border rounded p-4 bg-white">
            <h2 class="font-semibold mb-2">Hisob-fakturalar</h2>
            <ul class="text-sm space-y-1 max-h-48 overflow-y-auto">
                <?php foreach ($invoices as $invoice): ?>
                    <li><?= htmlspecialchars($invoice['number']) ?> - <?= format_currency((float)$invoice['total'], $invoice['currency']) ?> (<?= htmlspecialchars($invoice['status']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section class="border rounded p-4 bg-white">
            <h2 class="font-semibold mb-2">Loyallik ballari</h2>
            <p class="text-lg font-bold"><?= array_sum(array_map(fn($t) => $t['points'], $loyalty)) ?></p>
            <ul class="text-sm space-y-1 max-h-48 overflow-y-auto">
                <?php foreach ($loyalty as $txn): ?>
                    <li><?= htmlspecialchars($txn['created_at']) ?> - <?= $txn['points'] ?> (<?= htmlspecialchars($txn['type']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section class="border rounded p-4 bg-white">
            <h2 class="font-semibold mb-2">Fikr yuborish</h2>
            <p class="text-sm">Qo'llab-quvvatlash uchun <a class="text-blue-600 underline" href="mailto:<?= htmlspecialchars(get_setting('company_email', 'support@example.com')) ?>">email</a> yoki <a class="text-blue-600 underline" href="https://t.me/<?= htmlspecialchars(get_setting('support_telegram', 'cleaning_bot')) ?>" target="_blank">Telegram</a>.</p>
        </section>
    </div>
</div>
</body>
</html>
