<?php
namespace App\Helpers;

function csrf_field(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="_token" value="' . $_SESSION['csrf_token'] . '">';
}
