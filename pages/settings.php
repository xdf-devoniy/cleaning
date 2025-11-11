<?php
require_login();
authorize(['owner','admin']);

if (is_post()) {
    verify_csrf();
    foreach (['company_name','company_phone','tax_rate','payment_payme','payment_click','payment_uzum'] as $key) {
        set_setting($key, $_POST[$key] ?? '');
    }
    flash('success', 'Sozlamalar yangilandi');
    redirect('/index.php?page=settings');
}

$settings = [
    'company_name' => get_setting('company_name', 'CleanPro'),
    'company_phone' => get_setting('company_phone', '+998'),
    'tax_rate' => get_setting('tax_rate', '12'),
    'payment_payme' => get_setting('payment_payme', 'Faol'),
    'payment_click' => get_setting('payment_click', 'Faol'),
    'payment_uzum' => get_setting('payment_uzum', 'Faol')
];
?>
<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?= csrf_input() ?>
    <div>
        <label class="block text-sm">Kompaniya nomi</label>
        <input name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div>
        <label class="block text-sm">Telefon</label>
        <input name="company_phone" value="<?= htmlspecialchars($settings['company_phone']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div>
        <label class="block text-sm">Soliq (%)</label>
        <input name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div>
        <label class="block text-sm">Payme holati</label>
        <input name="payment_payme" value="<?= htmlspecialchars($settings['payment_payme']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div>
        <label class="block text-sm">Click holati</label>
        <input name="payment_click" value="<?= htmlspecialchars($settings['payment_click']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div>
        <label class="block text-sm">Uzum QR holati</label>
        <input name="payment_uzum" value="<?= htmlspecialchars($settings['payment_uzum']) ?>" class="border rounded px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
        <button class="bg-blue-600 text-white px-3 py-2 rounded">Saqlash</button>
    </div>
</form>
