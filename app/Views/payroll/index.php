<?php ob_start(); ?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-900">Payroll hisobi</h2>
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>" class="rounded border border-gray-300 px-3 py-2">
            <button class="rounded bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700">Ko'rish</button>
        </form>
    </div>
    <form method="POST" action="/payroll/compute" class="rounded bg-white p-4 shadow flex flex-wrap items-center gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600">Oy</label>
            <input type="month" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>" class="rounded border border-gray-300 px-3 py-2">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="persist" value="1" class="rounded border-gray-300">
            Payout jadvaliga yozish
        </label>
        <button class="rounded bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Hisoblash</button>
    </form>
    <div class="overflow-hidden rounded bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Xodim</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Buyurtma soni</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Buyurtmalar summasi</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">O'rtacha baho</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Asosiy ulush</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Bonus</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-500">Jami to'lov</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td class="px-4 py-2 text-gray-900 font-medium"><?= htmlspecialchars($row['name'], ENT_QUOTES) ?></td>
                        <td class="px-4 py-2 text-right"><?= (int)$row['jobs'] ?></td>
                        <td class="px-4 py-2 text-right"><?= number_format($row['order_total'] / 100, 2) ?> UZS</td>
                        <td class="px-4 py-2 text-right"><?= number_format($row['avg_rating'], 2) ?></td>
                        <td class="px-4 py-2 text-right"><?= number_format($row['base'] / 100, 2) ?> UZS</td>
                        <td class="px-4 py-2 text-right"><?= number_format($row['bonus'] / 100, 2) ?> UZS</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-900"><?= number_format($row['total'] / 100, 2) ?> UZS</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">Tanlangan oy uchun ma'lumot topilmadi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/app.php'; ?>
