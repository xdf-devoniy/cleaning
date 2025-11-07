<!DOCTYPE html>
<html lang="uz" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <title>CleanTrack CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body class="h-full">
<nav class="bg-white shadow">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center space-x-6">
                <span class="text-lg font-semibold text-sky-600">CleanTrack CRM</span>
                <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900">Boshqaruv paneli</a>
                <a href="/clients" class="text-sm font-medium text-gray-600 hover:text-gray-900">Mijozlar</a>
                <a href="/orders" class="text-sm font-medium text-gray-600 hover:text-gray-900">Buyurtmalar</a>
                <a href="/payroll" class="text-sm font-medium text-gray-600 hover:text-gray-900">Ish haqi</a>
            </div>
            <div class="flex items-center space-x-3 text-sm text-gray-600">
                <?php if (!empty($authUser)): ?>
                    <span><?= htmlspecialchars($authUser['name'] ?? $authUser['email'] ?? 'Foydalanuvchi', ENT_QUOTES) ?></span>
                <?php endif; ?>
                <form method="POST" action="/logout">
                    <button class="rounded bg-red-500 px-3 py-1 text-sm font-medium text-white hover:bg-red-600">Chiqish</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-4">
    <?php if (!empty($flash['success'])): ?>
        <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 text-sm">
            <?= htmlspecialchars($flash['success'], ENT_QUOTES) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
            <?= htmlspecialchars($flash['error'], ENT_QUOTES) ?>
        </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
</main>
</body>
</html>
