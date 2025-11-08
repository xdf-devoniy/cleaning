<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function flash(string $key, ?string $message = null)
{
    ensure_session();
    if ($message === null) {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    $_SESSION['flash'][$key] = $message;
}

function app_url(string $path = ''): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = trim(strtr(dirname($script), '\\', '/'), '/');

    if ($scriptDir === '' || $scriptDir === '.') {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($scriptFile && $docRoot) {
            $relativePath = str_replace($docRoot, '', $scriptFile);
            $relative = strtr($relativePath, '\\', '/');
            $relativeDir = trim(dirname($relative), '/');
            if ($relativeDir !== '.' && $relativeDir !== '') {
                $scriptDir = $relativeDir;
            }
        }
    }

    $path = ltrim($path, '/');
    $base = $scriptDir !== '' ? '/' . $scriptDir : '';

    if ($path === '') {
        return ($base ?: '/') . 'index.php';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $prefix = $base !== '' ? $base . '/' : '/';
    return $prefix . $path;
}

function redirect(string $path): void {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . app_url($path));
    }
    exit;
}

function audit_log(int $userId, string $action, string $entityType, ?int $entityId, string $details): void {
    execute('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)', [
        $userId,
        $action,
        $entityType,
        $entityId,
        $details
    ]);
}

function notify(int $userId, string $message): void {
    execute('INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)', [$userId, $message]);
}

function roles(): array {
    return ['owner' => 'Egalik', 'admin' => 'Administrator', 'accountant' => 'Buxgalter', 'dispatcher' => 'Dispetcher', 'cleaner' => 'Tozalovchi'];
}

function format_currency(float $amount, string $currency = 'UZS'): string {
    return number_format($amount, 2, '.', ' ') . ' ' . $currency;
}

function ensure_dir(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function store_upload(array $file, string $directory = 'uploads'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    ensure_dir(__DIR__ . '/../' . $directory);
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file['name']);
    $target = __DIR__ . '/../' . $directory . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $directory . '/' . $filename;
    }
    return null;
}

function parse_date(?string $date): ?string {
    if (!$date) {
        return null;
    }
    $ts = strtotime($date);
    return $ts ? date('Y-m-d', $ts) : null;
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function validate_required(array $data, array $fields): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = $label . ' talab qilinadi';
        }
    }
    return $errors;
}

function set_setting(string $key, string $value): void {
    execute('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$key, $value]);
}

function get_setting(string $key, ?string $default = null): ?string {
    $row = fetch_one('SELECT value FROM settings WHERE key = ?', [$key]);
    return $row['value'] ?? $default;
}

