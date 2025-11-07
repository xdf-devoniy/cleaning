<?php ob_start(); ?>
<h1 class="text-2xl font-semibold text-gray-900">Umumiy ko'rsatkichlar</h1>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Bugungi buyurtmalar</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= (int)$metrics['jobs_today'] ?></p>
    </div>
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Bugungi tushum</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($metrics['revenue_day'] / 100, 2) ?> UZS</p>
    </div>
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Haftalik tushum</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($metrics['revenue_week'] / 100, 2) ?> UZS</p>
    </div>
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Oylik tushum</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($metrics['revenue_month'] / 100, 2) ?> UZS</p>
    </div>
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Bajarilish darajasi (7 kun)</p>
        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $metrics['completion_rate'] ?>%</p>
    </div>
    <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Eng yaxshi tozalovchi (30 kun)</p>
        <p class="mt-2 text-lg font-semibold text-gray-900"><?= htmlspecialchars($metrics['top_cleaner'] ?? '—', ENT_QUOTES) ?></p>
        <p class="text-sm text-gray-500">Buyurtmalar: <?= (int)$metrics['top_cleaner_jobs'] ?></p>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/app.php'; ?>
