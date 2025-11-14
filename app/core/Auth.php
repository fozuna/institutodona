<?php
namespace App\Core;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo_acesso' => $user['tipo_acesso'],
            'id_cliente' => $user['id_cliente'] ?? null,
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function isInstituto(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'instituto';
    }

    public static function isCliente(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'cliente';
    }
}