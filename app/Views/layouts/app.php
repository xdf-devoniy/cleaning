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
            <div class="flex items-center space-x-4">
                <span class="text-lg font-semibold text-sky-600">CleanTrack CRM</span>
                <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900">Dashboard</a>
            </div>
            <form method="POST" action="/logout">
                <button class="rounded bg-red-500 px-3 py-1 text-sm font-medium text-white hover:bg-red-600">Logout</button>
            </form>
        </div>
    </div>
</nav>
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <?= $content ?? '' ?>
</main>
</body>
</html>
