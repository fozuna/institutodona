<?php
namespace App\Core;

class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return $token && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}