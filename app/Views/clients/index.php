<?php ob_start(); ?>
<?php $statusLabels = ['active' => 'Faol', 'paused' => "Pauza", 'lost' => 'Yo\'qotilgan']; ?>
<div class="flex flex-col gap-6 lg:flex-row">
    <div class="lg:w-2/3 space-y-4">
        <h2 class="text-xl font-semibold text-gray-900">Mijozlar</h2>
        <div class="overflow-hidden rounded bg-white shadow">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Nomi</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Holat</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Telefon</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Oxirgi buyurtma</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">LTV</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($client['name'], ENT_QUOTES) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($client['email'] ?? '', ENT_QUOTES) ?></div>
                            </td>
                            <td class="px-4 py-2">
                                <?php $statusKey = $client['status'] ?? 'active'; ?>
                                <span class="rounded-full bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700"><?= htmlspecialchars($statusLabels[$statusKey] ?? $statusKey, ENT_QUOTES) ?></span>
                            </td>
                            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($client['phone'] ?? '', ENT_QUOTES) ?></td>
                            <td class="px-4 py-2 text-gray-500"><?= $client['last_order_at'] ? date('d.m.Y H:i', strtotime($client['last_order_at'])) : '—' ?></td>
                            <td class="px-4 py-2 text-gray-900 font-semibold"><?= number_format(($client['lifetime_value'] ?? 0) / 100, 2) ?> UZS</td>
                        </tr>
                        <?php if (!empty($addresses[$client['id']])): ?>
                            <tr class="bg-gray-50">
                                <td colspan="5" class="px-4 py-2 text-xs text-gray-600">
                                    Manzillar:
                                    <?php foreach ($addresses[$client['id']] as $address): ?>
                                        <span class="mr-2 rounded bg-white px-2 py-1 shadow-sm">
                                            <?= htmlspecialchars($address['label'], ENT_QUOTES) ?> — <?= htmlspecialchars($address['address_line'] ?? '', ENT_QUOTES) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="lg:w-1/3">
        <h2 class="text-xl font-semibold text-gray-900">Yangi mijoz</h2>
        <form method="POST" action="/clients" class="mt-4 space-y-4 rounded bg-white p-4 shadow">
            <div>
                <label class="block text-sm font-medium text-gray-700">Mijoz nomi</label>
                <input type="text" name="name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Telefon</label>
                <input type="text" name="phone" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Holat</label>
                    <select name="status" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                        <option value="active">Faol</option>
                        <option value="paused">Pauza</option>
                        <option value="lost">Yo'qotilgan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Afzal vaqt</label>
                    <input type="time" name="preferred_time" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Afzal kunlar</label>
                <input type="text" name="preferred_days" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" placeholder="Du,Chor,Jum">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Teglar</label>
                <input type="text" name="tags" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" placeholder="VIP, Ofis">
            </div>
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-700">Manzil</h3>
                <div class="mt-2 space-y-2">
                    <input type="text" name="address_label" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Belgi (ixtiyoriy)">
                    <textarea name="address_line" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="To'liq manzil"></textarea>
                </div>
            </div>
            <button class="w-full rounded bg-sky-600 py-2 text-white hover:bg-sky-700">Saqlash</button>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/app.php'; ?>
