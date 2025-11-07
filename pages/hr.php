<?php
require_login();
authorize(['owner','admin','accountant']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_staff') {
        execute('INSERT INTO staff (name, role, team, hourly_rate, share_rate, salary_type, bonus, penalty, loan, advance, warnings, certifications, contract_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            trim($_POST['name']),
            trim($_POST['role']),
            trim($_POST['team']),
            (float)$_POST['hourly_rate'],
            (float)$_POST['share_rate'],
            trim($_POST['salary_type']),
            (float)$_POST['bonus'],
            (float)$_POST['penalty'],
            (float)$_POST['loan'],
            (float)$_POST['advance'],
            trim($_POST['warnings']),
            trim($_POST['certifications']),
            ''
        ]);
        flash('success', 'Xodim qo\'shildi');
    } elseif ($action === 'attendance') {
        execute('INSERT INTO attendance (staff_id, check_in, check_out) VALUES (?, ?, ?)', [(int)$_POST['staff_id'], $_POST['check_in'], $_POST['check_out']]);
        flash('success', 'Davomat saqlandi');
    } elseif ($action === 'payroll') {
        execute('INSERT INTO payroll_runs (staff_id, period_start, period_end, gross, net, notes) VALUES (?, ?, ?, ?, ?, ?)', [
            (int)$_POST['staff_id'],
            $_POST['period_start'],
            $_POST['period_end'],
            (float)$_POST['gross'],
            (float)$_POST['net'],
            trim($_POST['notes'])
        ]);
        flash('success', 'Payroll generatsiya qilindi');
    } elseif ($action === 'leave') {
        execute('INSERT INTO leaves (staff_id, type, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)', [
            (int)$_POST['staff_id'],
            trim($_POST['type']),
            $_POST['start_date'],
            $_POST['end_date'],
            trim($_POST['status'])
        ]);
        flash('success', 'Ta\'til qayd etildi');
    }
    redirect('/index.php?page=hr');
}

$staff = fetch_all('SELECT * FROM staff ORDER BY name');
$attendance = fetch_all('SELECT a.*, s.name FROM attendance a LEFT JOIN staff s ON s.id = a.staff_id ORDER BY a.check_in DESC LIMIT 20');
$payroll = fetch_all('SELECT p.*, s.name FROM payroll_runs p LEFT JOIN staff s ON s.id = p.staff_id ORDER BY p.period_end DESC LIMIT 20');
$leaves = fetch_all('SELECT l.*, s.name FROM leaves l LEFT JOIN staff s ON s.id = l.staff_id ORDER BY l.start_date DESC LIMIT 20');
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Xodim qo'shish</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="add_staff">
            <input name="name" class="border rounded px-3 py-2 w-full" placeholder="F.I.Sh">
            <input name="role" class="border rounded px-3 py-2 w-full" placeholder="Roli">
            <input name="team" class="border rounded px-3 py-2 w-full" placeholder="Jamoa">
            <input name="hourly_rate" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Soatlik">
            <input name="share_rate" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Ulush %">
            <input name="salary_type" class="border rounded px-3 py-2 w-full" placeholder="Stavka turi">
            <input name="bonus" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Bonus">
            <input name="penalty" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Jarima">
            <input name="loan" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Qarz">
            <input name="advance" type="number" step="0.01" class="border rounded px-3 py-2 w-full" placeholder="Avans">
            <textarea name="warnings" class="border rounded px-3 py-2 w-full" placeholder="Ogohlantirish"></textarea>
            <textarea name="certifications" class="border rounded px-3 py-2 w-full" placeholder="Treninglar"></textarea>
            <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Davomat & Ta'til</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="attendance">
            <select name="staff_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($staff as $member): ?>
                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="datetime-local" name="check_in" class="border rounded px-3 py-2 w-full">
            <input type="datetime-local" name="check_out" class="border rounded px-3 py-2 w-full">
            <button class="bg-emerald-600 text-white px-3 py-2 rounded w-full">Davomat</button>
        </form>
        <form method="post" class="space-y-2 mt-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="leave">
            <select name="staff_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($staff as $member): ?>
                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="type" class="border rounded px-3 py-2 w-full" placeholder="Ta'til turi">
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="start_date" class="border rounded px-3 py-2 w-full">
                <input type="date" name="end_date" class="border rounded px-3 py-2 w-full">
            </div>
            <input name="status" class="border rounded px-3 py-2 w-full" placeholder="Holat (tasdiq/bekor)">
            <button class="bg-indigo-600 text-white px-3 py-2 rounded w-full">Saqlash</button>
        </form>
    </section>

    <section class="border rounded p-4">
        <h2 class="font-semibold mb-3">Payroll</h2>
        <form method="post" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="payroll">
            <select name="staff_id" class="border rounded px-3 py-2 w-full">
                <?php foreach ($staff as $member): ?>
                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="period_start" class="border rounded px-3 py-2 w-full">
                <input type="date" name="period_end" class="border rounded px-3 py-2 w-full">
            </div>
            <input type="number" step="0.01" name="gross" class="border rounded px-3 py-2 w-full" placeholder="Gross">
            <input type="number" step="0.01" name="net" class="border rounded px-3 py-2 w-full" placeholder="Net">
            <textarea name="notes" class="border rounded px-3 py-2 w-full" placeholder="Izoh"></textarea>
            <button class="bg-slate-800 text-white px-3 py-2 rounded w-full">Hisobot</button>
        </form>
    </section>
</div>

<section class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="border rounded p-4">
        <h3 class="font-semibold mb-2">So'nggi davomat</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($attendance as $row): ?>
                <li><?= htmlspecialchars($row['name']) ?> - <?= htmlspecialchars($row['check_in']) ?> → <?= htmlspecialchars($row['check_out']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="border rounded p-4">
        <h3 class="font-semibold mb-2">Payroll yozuvlari</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($payroll as $row): ?>
                <li><?= htmlspecialchars($row['name']) ?> - <?= format_currency((float)$row['net']) ?> (<?= htmlspecialchars($row['period_start']) ?>~<?= htmlspecialchars($row['period_end']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="border rounded p-4">
        <h3 class="font-semibold mb-2">Ta'tillar</h3>
        <ul class="text-sm space-y-1 max-h-40 overflow-y-auto">
            <?php foreach ($leaves as $row): ?>
                <li><?= htmlspecialchars($row['name']) ?> - <?= htmlspecialchars($row['type']) ?> (<?= htmlspecialchars($row['status']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
