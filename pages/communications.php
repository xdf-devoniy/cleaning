<?php
require_login();
authorize(['owner','admin','dispatcher']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'send_message') {
        $campaignId = null;
        execute('INSERT INTO marketing_campaigns (name, channel, audience, template, status, scheduled_at) VALUES (?, ?, ?, ?, ?, ?)', [
            trim($_POST['name'] ?? 'Tezkor xabar'),
            $_POST['channel'],
            trim($_POST['audience']),
            trim($_POST['message']),
            'sent',
            date('Y-m-d H:i:s')
        ]);
        $campaignId = get_db()->lastInsertId();
        foreach ($_POST['clients'] ?? [] as $clientId) {
            execute('INSERT INTO campaign_logs (campaign_id, client_id, sent_at, response) VALUES (?, ?, CURRENT_TIMESTAMP, ?)', [$campaignId, (int)$clientId, 'Kutilmoqda']);
        }
        flash('success', 'Xabarlar rejalashtirildi');
    } elseif ($action === 'create_trigger') {
        set_setting('trigger_' . $_POST['event'], trim($_POST['template']));
        flash('success', 'Trigger saqlandi');
    }
    redirect('/index.php?page=communications');
}

$clients = fetch_all('SELECT id, name FROM clients ORDER BY name');
$campaigns = fetch_all('SELECT * FROM marketing_campaigns ORDER BY created_at DESC LIMIT 20');
$logs = fetch_all('SELECT l.*, c.name FROM campaign_logs l LEFT JOIN clients c ON c.id = l.client_id ORDER BY l.sent_at DESC LIMIT 30');
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Xabar yuborish</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="send_message">
            <input name="name" class="border rounded px-3 py-2 w-full" placeholder="Kampaniya nomi">
            <select name="channel" class="border rounded px-3 py-2 w-full">
                <option value="telegram">Telegram</option>
                <option value="sms">SMS</option>
                <option value="email">Email</option>
            </select>
            <textarea name="audience" class="border rounded px-3 py-2 w-full" placeholder="Segment (masalan: barcha yangi lead)"></textarea>
            <textarea name="message" class="border rounded px-3 py-2 w-full" placeholder="Xabar shabloni"></textarea>
            <label class="text-sm">Mijozlar</label>
            <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border rounded p-2">
                <?php foreach ($clients as $client): ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="clients[]" value="<?= $client['id'] ?>">
                        <?= htmlspecialchars($client['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="bg-blue-600 text-white px-3 py-2 rounded">Yuborish</button>
        </form>
    </section>
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Trigger sozlamalari</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_trigger">
            <select name="event" class="border rounded px-3 py-2 w-full">
                <option value="after_cleaning">Tozalashdan keyin</option>
                <option value="no_booking">30 kun buyurtmasiz</option>
            </select>
            <textarea name="template" class="border rounded px-3 py-2 w-full" placeholder="Shablon"></textarea>
            <button class="bg-emerald-600 text-white px-3 py-2 rounded">Saqlash</button>
        </form>
        <h3 class="font-semibold text-sm mt-4">Oxirgi kampaniyalar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($campaigns as $camp): ?>
                <li><?= htmlspecialchars($camp['name']) ?> - <?= htmlspecialchars($camp['channel']) ?> (<?= htmlspecialchars($camp['status']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="mt-6 border rounded p-4">
    <h3 class="font-semibold mb-2">Kampaniya loglari</h3>
    <ul class="text-sm space-y-1 max-h-60 overflow-y-auto">
        <?php foreach ($logs as $log): ?>
            <li><?= htmlspecialchars($log['name']) ?> - <?= htmlspecialchars($log['sent_at']) ?> - <?= htmlspecialchars($log['response']) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
