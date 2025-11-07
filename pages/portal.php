<?php
require_login();
authorize(['owner','admin','dispatcher']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'generate_link') {
        $token = bin2hex(random_bytes(8));
        $clientId = (int)$_POST['client_id'];
        execute('INSERT INTO client_portal_tokens (client_id, token, expires_at, channel) VALUES (?, ?, DATE("now", "+7 days"), ?)', [$clientId, $token, $_POST['channel'] ?? 'telegram']);
        flash('success', 'Portal havola yaratildi: ' . $token);
    }
    redirect('/index.php?page=portal');
}

$clients = fetch_all('SELECT id, name, phone FROM clients ORDER BY name');
$tokens = fetch_all('SELECT t.*, c.name FROM client_portal_tokens t LEFT JOIN clients c ON c.id = t.client_id ORDER BY t.created_at DESC LIMIT 20');
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Portal havolasi</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="generate_link">
            <select name="client_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['phone']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <select name="channel" class="border rounded px-3 py-2 w-full">
                <option value="telegram">Telegram</option>
                <option value="sms">SMS</option>
            </select>
            <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Havola yuborish</button>
        </form>
        <h3 class="font-semibold text-sm mt-4">Oxirgi havolalar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($tokens as $token): ?>
                <li><?= htmlspecialchars($token['name']) ?> - <?= htmlspecialchars($token['token']) ?> (<?= htmlspecialchars($token['channel']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Portal imkoniyatlari</h2>
        <ul class="list-disc pl-5 text-sm space-y-1">
            <li>Bronlash, hisoblar va ballarni ko'rish</li>
            <li>Onlayn to'lov va qayta rejalashtirish</li>
            <li>Fikr bildirish va choychaqa yuborish</li>
            <li>Saqlangan manzillar va qo'llab-quvvatlash chati</li>
            <li>Ko'p tilli interfeys</li>
        </ul>
        <p class="text-xs text-slate-500 mt-4">Token asosida "client_portal.php?token=..." manzilida ko'rinadi.</p>
    </section>
</div>
