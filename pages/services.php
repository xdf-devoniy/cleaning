<?php
require_login();
authorize(['owner','admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_service') {
        execute('INSERT INTO services (name, base_price, price_per_sqm, description, location, client_type, bundle, seasonal_notes, promo_code, recurrence_discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            trim($_POST['name']),
            (float)$_POST['base_price'],
            (float)$_POST['price_per_sqm'],
            trim($_POST['description']),
            trim($_POST['location']),
            trim($_POST['client_type']),
            trim($_POST['bundle']),
            trim($_POST['seasonal_notes']),
            trim($_POST['promo_code']),
            (float)$_POST['recurrence_discount']
        ]);
        flash('success', 'Xizmat qo\'shildi');
    }
    redirect('/index.php?page=services');
}

$services = fetch_all('SELECT * FROM services ORDER BY name');
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Yangi xizmat</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="add_service">
            <input name="name" class="border rounded px-3 py-2 w-full" placeholder="Nom">
            <input name="base_price" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Bazaviy narx">
            <input name="price_per_sqm" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="m² narxi">
            <textarea name="description" class="border rounded px-3 py-2 w-full" placeholder="Tavsif"></textarea>
            <input name="location" class="border rounded px-3 py-2 w-full" placeholder="Hudud">
            <input name="client_type" class="border rounded px-3 py-2 w-full" placeholder="Mijoz turi">
            <input name="bundle" class="border rounded px-3 py-2 w-full" placeholder="Paketlar">
            <textarea name="seasonal_notes" class="border rounded px-3 py-2 w-full" placeholder="Mavsumiy narx"></textarea>
            <input name="promo_code" class="border rounded px-3 py-2 w-full" placeholder="Promo kod">
            <input name="recurrence_discount" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Takroriy chegirma %">
            <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
    </section>
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Xizmatlar ro'yxati</h2>
        <ul class="text-sm space-y-2 max-h-[500px] overflow-y-auto">
            <?php foreach ($services as $service): ?>
                <li class="border rounded px-3 py-2">
                    <div class="font-semibold"><?= htmlspecialchars($service['name']) ?> - <?= format_currency((float)$service['base_price']) ?></div>
                    <p class="text-xs text-slate-500">m² narxi: <?= htmlspecialchars($service['price_per_sqm']) ?></p>
                    <p class="text-xs"><?= htmlspecialchars($service['description']) ?></p>
                    <p class="text-xs">Promo: <?= htmlspecialchars($service['promo_code']) ?> | Paket: <?= htmlspecialchars($service['bundle']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
