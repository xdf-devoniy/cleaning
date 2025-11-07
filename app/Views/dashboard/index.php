<?php ob_start(); ?>
<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-lg bg-white p-5 shadow">
        <dt class="text-sm font-medium text-gray-500">Bugungi buyurtmalar</dt>
        <dd class="mt-2 text-3xl font-semibold text-gray-900"><?= $ordersToday ?></dd>
    </div>
    <div class="rounded-lg bg-white p-5 shadow">
        <dt class="text-sm font-medium text-gray-500">Bugungi tushum (tiyin)</dt>
        <dd class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($todayRevenue) ?></dd>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/app.php'; ?>
