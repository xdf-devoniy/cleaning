<?php ob_start(); ?>
<?php
$statusLabels = [
    'draft' => "Qoralama",
    'scheduled' => "Rejalashtirilgan",
    'in_progress' => "Jarayonda",
    'completed' => "Tugallangan",
    'paid' => "To'langan",
    'cancelled' => "Bekor qilingan",
    'issued' => "Yuborilgan",
    'void' => "Bekor qilingan",
];
?>
<div class="flex flex-col gap-6 lg:flex-row">
    <div class="lg:w-2/3 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Buyurtmalar</h2>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES) ?>" class="rounded border border-gray-300 px-3 py-2">
                <button class="rounded bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700">Ko'rish</button>
            </form>
        </div>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <div class="rounded bg-white p-4 shadow">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                #<?= $order['id'] ?> — <?= htmlspecialchars($order['client_name'], ENT_QUOTES) ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($order['address_label'], ENT_QUOTES) ?> · <?= date('d.m.Y H:i', strtotime($order['scheduled_at'])) ?></p>
                        </div>
                        <?php $statusKey = $order['status'] ?? 'draft'; ?>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700 uppercase"><?= htmlspecialchars($statusLabels[$statusKey] ?? $statusKey, ENT_QUOTES) ?></span>
                    </div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">Xizmatlar</p>
                            <ul class="mt-1 space-y-1 text-sm text-gray-700">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li><?= htmlspecialchars($item['service_name'], ENT_QUOTES) ?> × <?= (int)$item['qty'] ?> — <?= number_format($item['line_total'] / 100, 2) ?> UZS</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">Ijrochilar</p>
                            <form method="POST" action="/orders/<?= $order['id'] ?>/assign" class="mt-1 space-y-2">
                                <select name="assigned[]" multiple class="h-24 w-full rounded border border-gray-300 px-2 py-1 text-sm">
                                    <?php foreach ($cleaners as $cleaner): ?>
                                        <option value="<?= $cleaner['id'] ?>" <?= in_array($cleaner['id'], array_column($order['assignments'], 'user_id')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cleaner['name'], ENT_QUOTES) ?> (<?= $cleaner['role'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="w-full rounded bg-amber-500 py-1 text-sm font-medium text-white hover:bg-amber-600">Yangilash</button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
                        <div>
                            <p class="text-xs uppercase text-gray-500">Umumiy summa</p>
                            <p class="font-semibold text-gray-900"><?= number_format($order['total'] / 100, 2) ?> UZS</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">To'langan</p>
                            <p class="font-semibold text-gray-900"><?= number_format(($order['payments_total'] ?? 0) / 100, 2) ?> UZS</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">Hisob-faktura</p>
                            <?php if ($order['invoice']): ?>
                                <?php $invoiceStatus = $order['invoice']['status'] ?? ''; ?>
                                <?php $invoiceStatusLabel = $statusLabels[$invoiceStatus] ?? $invoiceStatus; ?>
                                <span class="inline-flex rounded bg-emerald-50 px-2 py-1 text-emerald-700 text-xs font-semibold"><?= htmlspecialchars($order['invoice']['invoice_no'], ENT_QUOTES) ?> (<?= htmlspecialchars($invoiceStatusLabel, ENT_QUOTES) ?>)</span>
                            <?php else: ?>
                                <form method="POST" action="/invoices/<?= $order['id'] ?>/issue">
                                    <button class="rounded bg-sky-600 px-3 py-1 text-xs font-medium text-white hover:bg-sky-700">Hisob-faktura</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <form method="POST" action="/orders/<?= $order['id'] ?>/status" class="flex items-center gap-2">
                            <select name="status" class="rounded border border-gray-300 px-3 py-1 text-sm">
                                <option value="scheduled">Rejalashtirilgan</option>
                                <option value="in_progress">Boshlash</option>
                                <option value="completed">Tugallangan</option>
                                <option value="paid">To'langan</option>
                                <option value="cancelled">Bekor qilingan</option>
                            </select>
                            <button class="rounded bg-gray-800 px-3 py-1 text-sm font-medium text-white">Holatni yangilash</button>
                        </form>
                        <form method="POST" action="/payments" class="flex flex-wrap items-center gap-2 text-sm">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <?php if ($order['invoice']): ?>
                                <input type="hidden" name="invoice_id" value="<?= $order['invoice']['id'] ?>">
                            <?php endif; ?>
                            <input type="number" name="amount" placeholder="Summasi (tiyin)" class="w-32 rounded border border-gray-300 px-2 py-1">
                            <select name="method" class="rounded border border-gray-300 px-2 py-1">
                                <option value="cash">Naqd</option>
                                <option value="card">Karta</option>
                                <option value="bank">Bank</option>
                                <option value="payme">Payme</option>
                                <option value="click">Click</option>
                            </select>
                            <input type="text" name="txn_ref" placeholder="Tranzaksiya №" class="w-32 rounded border border-gray-300 px-2 py-1">
                            <button class="rounded bg-emerald-600 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-700">To'lov</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <p class="text-sm text-gray-500">Tanlangan kunda buyurtmalar mavjud emas.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="lg:w-1/3">
        <h2 class="text-xl font-semibold text-gray-900">Yangi buyurtma</h2>
        <form method="POST" action="/orders" class="mt-4 space-y-4 rounded bg-white p-4 shadow">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES) ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Mijoz</label>
                <select name="client_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
                    <option value="">Tanlang</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name'], ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Manzil</label>
                <select name="address_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
                    <?php foreach ($addresses as $address): ?>
                        <option value="<?= $address['id'] ?>"><?= htmlspecialchars($address['label'], ENT_QUOTES) ?> — <?= htmlspecialchars($address['address_line'] ?? '', ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Xizmat</label>
                    <select name="service_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name'], ENT_QUOTES) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Soni</label>
                    <input type="number" name="qty" min="1" value="1" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Boshlanish vaqt</label>
                <input type="datetime-local" name="scheduled_at" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Ijrochilar</label>
                <select name="assigned[]" multiple class="h-24 w-full rounded border border-gray-300 px-2 py-1 text-sm">
                    <?php foreach ($cleaners as $cleaner): ?>
                        <option value="<?= $cleaner['id'] ?>"><?= htmlspecialchars($cleaner['name'], ENT_QUOTES) ?> (<?= $cleaner['role'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Izoh</label>
                <textarea name="notes" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" rows="3"></textarea>
            </div>
            <button class="w-full rounded bg-sky-600 py-2 text-white hover:bg-sky-700">Buyurtma yaratish</button>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/app.php'; ?>
