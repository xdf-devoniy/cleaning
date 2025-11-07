<?php
require_login();
authorize(['owner','admin','dispatcher']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_checklist') {
        execute('INSERT INTO checklists (name, description) VALUES (?, ?)', [trim($_POST['name']), trim($_POST['description'])]);
        flash('success', 'Checklist qo\'shildi');
    } elseif ($action === 'record_qc') {
        execute('INSERT INTO job_qc (job_id, checklist_id, score, result, notes) VALUES (?, ?, ?, ?, ?)', [
            (int)$_POST['job_id'],
            (int)$_POST['checklist_id'],
            (int)$_POST['score'],
            $_POST['result'] ?? 'pass',
            trim($_POST['notes'] ?? '')
        ]);
        if ($_POST['result'] === 'fail') {
            execute('INSERT INTO reworks (job_id, issue, penalty) VALUES (?, ?, ?)', [(int)$_POST['job_id'], trim($_POST['notes'] ?? ''), (float)($_POST['penalty'] ?? 0)]);
        }
        flash('success', 'QC yozuvi saqlandi');
    } elseif ($action === 'nps') {
        execute('INSERT INTO job_qc (job_id, checklist_id, score, result, notes) VALUES (?, NULL, ?, ?, ?)', [
            (int)$_POST['job_id'],
            (int)$_POST['score'],
            'survey',
            trim($_POST['notes'] ?? '')
        ]);
        flash('success', 'Mijoz fikri qabul qilindi');
    }
    redirect('/index.php?page=checklists');
}

$checklists = fetch_all('SELECT * FROM checklists ORDER BY id DESC');
$jobs = fetch_all('SELECT j.id, c.name AS client_name, j.scheduled_at FROM jobs j LEFT JOIN clients c ON c.id = j.client_id ORDER BY j.scheduled_at DESC LIMIT 50');
$qcReports = fetch_all('SELECT q.*, c.name AS checklist_name, j.client_id FROM job_qc q LEFT JOIN checklists c ON c.id = q.checklist_id LEFT JOIN jobs j ON j.id = q.job_id ORDER BY q.created_at DESC LIMIT 50');
$issues = fetch_all('SELECT r.*, j.id AS job_id FROM reworks r LEFT JOIN jobs j ON j.id = r.job_id ORDER BY r.created_at DESC LIMIT 20');
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Checklist yaratish</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_checklist">
            <input name="name" class="border rounded px-3 py-2 w-full" placeholder="Nom">
            <textarea name="description" class="border rounded px-3 py-2 w-full" placeholder="Tavsif"></textarea>
            <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
        <h3 class="font-semibold text-sm mt-4">Mavjud shakllar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($checklists as $item): ?>
                <li><?= htmlspecialchars($item['name']) ?> - <?= htmlspecialchars($item['description']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">QC qaydi</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="record_qc">
            <select name="job_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($jobs as $job): ?>
                    <option value="<?= $job['id'] ?>"><?= htmlspecialchars($job['client_name']) ?> - <?= htmlspecialchars($job['scheduled_at']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="checklist_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($checklists as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="score" class="border rounded px-3 py-2 w-full" placeholder="Ball (0-100)">
            <select name="result" class="border rounded px-3 py-2 w-full">
                <option value="pass">O'tdi</option>
                <option value="fail">Muammo bor</option>
            </select>
            <textarea name="notes" class="border rounded px-3 py-2 w-full" placeholder="Izoh"></textarea>
            <input type="number" step="0.01" name="penalty" class="border rounded px-3 py-2 w-full" placeholder="Jarima (ixtiyoriy)">
            <button class="bg-emerald-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
        <form method="post" class="space-y-2 mt-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="nps">
            <label class="text-sm">Mijoz NPS/CSAT</label>
            <select name="job_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($jobs as $job): ?>
                    <option value="<?= $job['id'] ?>"><?= htmlspecialchars($job['client_name']) ?> - <?= htmlspecialchars($job['scheduled_at']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="score" class="border rounded px-3 py-2 w-full" placeholder="Bahosi (0-10)">
            <textarea name="notes" class="border rounded px-3 py-2 w-full" placeholder="Sharh"></textarea>
            <button class="bg-indigo-600 text-white px-3 py-2 rounded w-full">Yuborish</button>
        </form>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Random QC</h2>
        <?php $randomJob = $jobs[array_rand($jobs)] ?? null; ?>
        <?php if ($randomJob): ?>
            <p class="text-sm">Tasodifiy tekshiruv: <strong><?= htmlspecialchars($randomJob['client_name']) ?></strong> - <?= htmlspecialchars($randomJob['scheduled_at']) ?></p>
        <?php else: ?>
            <p class="text-sm text-slate-500">Ishlar topilmadi.</p>
        <?php endif; ?>
        <h3 class="font-semibold text-sm mt-4">Muammolar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($issues as $issue): ?>
                <li>#<?= $issue['job_id'] ?> - <?= htmlspecialchars($issue['issue']) ?> (<?= format_currency((float)$issue['penalty']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="mt-6 border rounded p-4">
    <h3 class="font-semibold mb-3">QC hisobotlari</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="px-3 py-2 text-left">Sana</th>
                    <th class="px-3 py-2 text-left">Checklist</th>
                    <th class="px-3 py-2 text-left">Ball</th>
                    <th class="px-3 py-2 text-left">Natija</th>
                    <th class="px-3 py-2 text-left">Izoh</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qcReports as $row): ?>
                    <tr class="border-b">
                        <td class="px-3 py-2"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['checklist_name'] ?? 'So\'rovnoma') ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['score']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['result']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
