<?php
require_login();
authorize(['owner','admin','dispatcher']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $user = current_user();

    if ($action === 'create_job') {
        execute('INSERT INTO jobs (client_id, service_id, crew, status, scheduled_at, end_at, recurring_rule, geo_start, geo_end, route_url, urgent, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            (int)$_POST['client_id'],
            (int)$_POST['service_id'],
            trim($_POST['crew'] ?? ''),
            $_POST['status'] ?? 'new',
            $_POST['scheduled_at'] ?? date('Y-m-d H:i:s'),
            $_POST['end_at'] ?? null,
            trim($_POST['recurring_rule'] ?? ''),
            trim($_POST['geo_start'] ?? ''),
            trim($_POST['geo_end'] ?? ''),
            trim($_POST['route_url'] ?? ''),
            !empty($_POST['urgent']) ? 1 : 0,
            trim($_POST['notes'] ?? '')
        ]);
        flash('success', 'Yangi ish saqlandi');
    } elseif ($action === 'update_job') {
        $jobId = (int)$_POST['job_id'];
        execute('UPDATE jobs SET crew=?, status=?, scheduled_at=?, end_at=?, service_id=?, notes=? WHERE id=?', [
            trim($_POST['crew'] ?? ''),
            $_POST['status'] ?? 'new',
            $_POST['scheduled_at'] ?? date('Y-m-d H:i:s'),
            $_POST['end_at'] ?? null,
            (int)$_POST['service_id'],
            trim($_POST['notes'] ?? ''),
            $jobId
        ]);
        if (!empty($_POST['notify_cleaner'])) {
            $crewUsers = fetch_all('SELECT id FROM users WHERE role = "cleaner"');
            foreach ($crewUsers as $crewUser) {
                notify((int)$crewUser['id'], 'Yangi ish #' . $jobId . ' sizga biriktirildi');
            }
        }
        flash('success', 'Ish yangilandi');
    } elseif ($action === 'upload_photo' && !empty($_FILES['photo'])) {
        $path = store_upload($_FILES['photo']);
        if ($path) {
            execute('INSERT INTO job_photos (job_id, path, type) VALUES (?, ?, ?)', [(int)$_POST['job_id'], $path, $_POST['photo_type'] ?? 'before']);
            flash('success', 'Rasm yuklandi');
        }
    }
    redirect('/index.php?page=jobs');
}

$date = $_GET['date'] ?? date('Y-m-d');
$jobs = fetch_all('SELECT j.*, c.name AS client_name, s.name AS service_name FROM jobs j LEFT JOIN clients c ON c.id = j.client_id LEFT JOIN services s ON s.id = j.service_id WHERE date(j.scheduled_at) = ? ORDER BY j.scheduled_at', [$date]);
$clients = fetch_all('SELECT id, name FROM clients ORDER BY name');
$services = fetch_all('SELECT id, name FROM services ORDER BY name');
?>
<form method="get" class="flex gap-3 items-end mb-4">
    <input type="hidden" name="page" value="jobs">
    <div>
        <label class="block text-sm">Sana</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="border rounded px-3 py-2">
    </div>
    <button class="bg-blue-600 text-white px-3 py-2 rounded">Ko'rsatish</button>
</form>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <section class="border rounded p-4 md:col-span-2">
        <h2 class="font-semibold mb-3">Kunlik ishlar jadvali</h2>
        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
            <?php foreach ($jobs as $job): ?>
                <details class="border rounded" <?= isset($_GET['focus']) && (int)$_GET['focus'] === (int)$job['id'] ? 'open' : '' ?>>
                    <summary class="px-3 py-2 flex justify-between items-center">
                        <div>
                            <span class="font-semibold"><?= htmlspecialchars($job['client_name']) ?></span>
                            <span class="text-xs text-slate-500 ml-2"><?= htmlspecialchars($job['service_name']) ?></span>
                        </div>
                        <span class="text-xs text-slate-500"><?= htmlspecialchars($job['scheduled_at']) ?></span>
                    </summary>
                    <div class="px-3 py-3 space-y-3">
                        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="update_job">
                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                            <select name="service_id" class="border rounded px-2 py-1">
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= $service['id'] ?>" <?= $service['id'] == $job['service_id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="crew" value="<?= htmlspecialchars($job['crew']) ?>" class="border rounded px-2 py-1" placeholder="Brigada">
                            <select name="status" class="border rounded px-2 py-1">
                                <option value="new" <?= $job['status'] === 'new' ? 'selected' : '' ?>>Yangi</option>
                                <option value="scheduled" <?= $job['status'] === 'scheduled' ? 'selected' : '' ?>>Rejalashtirilgan</option>
                                <option value="completed" <?= $job['status'] === 'completed' ? 'selected' : '' ?>>Bajarildi</option>
                                <option value="canceled" <?= $job['status'] === 'canceled' ? 'selected' : '' ?>>Bekor qilindi</option>
                                <option value="no-show" <?= $job['status'] === 'no-show' ? 'selected' : '' ?>>Kelmagan</option>
                            </select>
                            <input type="datetime-local" name="scheduled_at" value="<?= date('Y-m-d\TH:i', strtotime($job['scheduled_at'])) ?>" class="border rounded px-2 py-1">
                            <input type="datetime-local" name="end_at" value="<?= $job['end_at'] ? date('Y-m-d\TH:i', strtotime($job['end_at'])) : '' ?>" class="border rounded px-2 py-1">
                            <textarea name="notes" class="border rounded px-2 py-1 md:col-span-3" placeholder="Izoh"><?= htmlspecialchars($job['notes']) ?></textarea>
                            <label class="flex items-center gap-2 text-xs">
                                <input type="checkbox" name="notify_cleaner" value="1">
                                Tozalovchilarga yuborish
                            </label>
                            <button class="bg-emerald-600 text-white px-3 py-1 rounded">Saqlash</button>
                        </form>
                        <div>
                            <p class="text-sm">Geo start: <?= htmlspecialchars($job['geo_start']) ?> | Geo finish: <?= htmlspecialchars($job['geo_end']) ?></p>
                            <?php if ($job['route_url']): ?>
                                <a class="text-blue-600 underline text-sm" href="<?= htmlspecialchars($job['route_url']) ?>" target="_blank">Marshrutni ochish</a>
                            <?php endif; ?>
                            <?php if ($job['urgent']): ?>
                                <span class="ml-2 text-xs bg-rose-500 text-white px-2 py-1 rounded">Shoshilinch</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">Rasmlar</h4>
                            <ul class="flex gap-2 text-xs">
                                <?php $photos = fetch_all('SELECT * FROM job_photos WHERE job_id = ?', [$job['id']]); ?>
                                <?php foreach ($photos as $photo): ?>
                                    <li><a class="text-blue-600 underline" href="/<?= htmlspecialchars($photo['path']) ?>" target="_blank"><?= htmlspecialchars($photo['type']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="post" enctype="multipart/form-data" class="flex gap-2 items-center mt-2 text-xs">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="upload_photo">
                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                <select name="photo_type" class="border rounded px-2 py-1">
                                    <option value="before">Oldin</option>
                                    <option value="after">Keyin</option>
                                </select>
                                <input type="file" name="photo">
                                <button class="bg-slate-700 text-white px-2 py-1 rounded">Yuklash</button>
                            </form>
                        </div>
                        <div class="text-xs text-slate-500">ETA havolasi: <a class="text-blue-600 underline" href="https://maps.google.com/?q=<?= urlencode($job['geo_start']) ?>" target="_blank">Google Maps</a></div>
                    </div>
                </details>
            <?php endforeach; ?>
            <?php if (!$jobs): ?>
                <p class="text-sm text-slate-500">Bu kun uchun ish mavjud emas.</p>
            <?php endif; ?>
        </div>
    </section>
    <section class="border rounded p-4">
        <h3 class="font-semibold mb-3">Yangi ish yaratish</h3>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_job">
            <label class="block text-sm">Mijoz</label>
            <select name="client_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">Xizmat</label>
            <select name="service_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($services as $service): ?>
                    <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="crew" class="border rounded px-3 py-2 w-full" placeholder="Brigada">
            <input type="datetime-local" name="scheduled_at" class="border rounded px-3 py-2 w-full" value="<?= date('Y-m-d\TH:i') ?>">
            <input type="datetime-local" name="end_at" class="border rounded px-3 py-2 w-full">
            <input name="recurring_rule" class="border rounded px-3 py-2 w-full" placeholder="Masalan: haftasiga 1 marta">
            <input name="geo_start" class="border rounded px-3 py-2 w-full" placeholder="Boshlanish GPS">
            <input name="geo_end" class="border rounded px-3 py-2 w-full" placeholder="Yakun GPS">
            <input name="route_url" class="border rounded px-3 py-2 w-full" placeholder="Google/Yandex marshrut havolasi">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="urgent" value="1"> Favqulodda</label>
            <textarea name="notes" class="border rounded px-3 py-2 w-full" placeholder="Izoh"></textarea>
            <select name="status" class="border rounded px-3 py-2 w-full">
                <option value="new">Yangi</option>
                <option value="scheduled">Rejalashtirilgan</option>
            </select>
            <button class="bg-emerald-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
    </section>
</div>
