<?php ob_start(); ?>
<?php
$errorMessages = [
    'required' => "Ushbu maydon majburiy",
    'email' => "Email formati noto'g'ri",
    'min' => "Kamida 6 ta belgi kiriting",
];
$formatErrors = static function (array $items) use ($errorMessages): string {
    $texts = array_map(fn($code) => $errorMessages[$code] ?? $code, $items);
    return implode(', ', $texts);
};
?>
<h1 class="text-2xl font-semibold text-gray-900">Tizimga kirish</h1>
<form method="POST" action="/login" class="mt-6 space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
        <?php if (!empty($errors['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formatErrors($errors['email']), ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Parol</label>
        <input type="password" name="password" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" required>
        <?php if (!empty($errors['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formatErrors($errors['password']), ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($errors['auth'])): ?>
        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">Login yoki parol noto'g'ri</div>
    <?php endif; ?>
    <button class="w-full rounded bg-sky-600 py-2 text-white hover:bg-sky-700">Kirish</button>
</form>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/auth.php'; ?>
