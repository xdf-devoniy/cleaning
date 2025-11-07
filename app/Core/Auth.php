<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?self $instance = null;
    private ?array $user = null;
    private array $policies;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->policies = require __DIR__ . '/../Config/policies.php';
        if (isset($_SESSION['user_id'])) {
            $this->user = User::find((int)$_SESSION['user_id']);
        }
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $this->user = $user;
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->user = null;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function authorize(string $ability): bool
    {
        if (!$this->check()) {
            return false;
        }

        $role = $this->user['role'] ?? null;
        $allowedRoles = $this->policies[$ability] ?? [];
        return in_array($role, $allowedRoles, true);
    }
}
