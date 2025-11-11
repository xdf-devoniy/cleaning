<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

function current_user(): ?array {
    ensure_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return fetch_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /index.php');
        exit;
    }
}

function login(string $username, string $password): bool {
    $user = fetch_one('SELECT * FROM users WHERE username = ?', [$username]);
    if ($user && password_verify($password, $user['password_hash'])) {
        ensure_session();
        $_SESSION['user_id'] = $user['id'];
        audit_log($user['id'], 'login', 'user', $user['id'], 'Foydalanuvchi tizimga kirdi');
        return true;
    }
    return false;
}

function logout(): void {
    $user = current_user();
    ensure_session();
    session_destroy();
    if ($user) {
        audit_log($user['id'], 'logout', 'user', $user['id'], 'Foydalanuvchi tizimdan chiqdi');
    }
}

function authorize(array $roles): void {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Sizda ushbu amal uchun ruxsat yo\'q.');
    }
}

