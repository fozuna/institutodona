<?php
namespace App\Core;

class Auth
{
    private const ADMIN_CLIENT_ROLES = ['cliente', 'cliente_admin'];

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        $idCliente = isset($user['id_cliente']) && $user['id_cliente'] !== null ? (int)$user['id_cliente'] : null;
        $scopeIds = TenantScopeResolver::resolveForUser($user);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo_acesso' => $user['tipo_acesso'],
            'id_cliente' => $idCliente,
            'allowed_client_ids' => $scopeIds,
            'selected_client_ids' => $scopeIds,
            'unrestricted_access' => ($user['tipo_acesso'] ?? null) === 'instituto',
        ];
    }

    public static function refreshScope(): void
    {
        $user = self::user();
        if (!$user) {
            return;
        }
        if (($user['tipo_acesso'] ?? null) === 'instituto') {
            $_SESSION['user']['allowed_client_ids'] = [];
            $_SESSION['user']['selected_client_ids'] = [];
            return;
        }
        $_SESSION['user']['allowed_client_ids'] = TenantScopeResolver::resolveForUser($user);
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function isInstituto(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'instituto';
    }

    public static function isCliente(): bool
    {
        return in_array((self::user()['tipo_acesso'] ?? null), self::ADMIN_CLIENT_ROLES, true);
    }

    public static function isConsultor(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'consultor';
    }

    public static function isClienteAdmin(): bool
    {
        return in_array((self::user()['tipo_acesso'] ?? null), self::ADMIN_CLIENT_ROLES, true);
    }

    public static function isReader(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'reader';
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function allowedClientIds(): array
    {
        $user = self::user();
        if (!$user) {
            return [];
        }
        if (($user['tipo_acesso'] ?? null) === 'instituto') {
            return [];
        }
        $ids = $user['allowed_client_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        if (empty($ids) && !empty($user['id_cliente'])) {
            $ids = [(int)$user['id_cliente']];
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        return $ids;
    }

    public static function canAccessCliente(int $clienteId): bool
    {
        if ($clienteId <= 0) {
            return false;
        }
        if (self::isInstituto()) {
            return true;
        }
        return in_array($clienteId, self::allowedClientIds(), true);
    }
}
