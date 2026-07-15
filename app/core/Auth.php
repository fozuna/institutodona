<?php
namespace App\Core;

use App\Database\Database;

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
        $platformAccess = (string)($user['platform_access'] ?? 'WEB_PWA');
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo_acesso' => $user['tipo_acesso'],
            'id_cliente' => $idCliente,
            'platform_access' => $platformAccess !== '' ? $platformAccess : 'WEB_PWA',
            'allowed_client_ids' => $scopeIds,
            'selected_client_ids' => $scopeIds,
            'unrestricted_access' => ($user['tipo_acesso'] ?? null) === 'instituto',
        ];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function refreshScope(): void
    {
        $user = self::user();
        if (!$user) {
            return;
        }
        $userId = (int)($user['id'] ?? 0);
        if ($userId > 0) {
            try {
                $stmt = Database::getConnection()->prepare('SELECT id, nome, email, tipo_acesso, id_cliente, platform_access FROM usuarios WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $userId]);
                $fresh = $stmt->fetch();
                if ($fresh) {
                    $_SESSION['user']['nome'] = $fresh['nome'];
                    $_SESSION['user']['email'] = $fresh['email'];
                    $_SESSION['user']['tipo_acesso'] = $fresh['tipo_acesso'];
                    $_SESSION['user']['id_cliente'] = $fresh['id_cliente'] !== null ? (int)$fresh['id_cliente'] : null;
                    $_SESSION['user']['platform_access'] = (string)($fresh['platform_access'] ?? 'WEB_PWA');
                    $_SESSION['user']['unrestricted_access'] = ($fresh['tipo_acesso'] ?? null) === 'instituto';
                }
            } catch (\Throwable $e) {
            }
        }
        $refreshed = self::user() ?? $user;
        if (($refreshed['tipo_acesso'] ?? null) === 'instituto') {
            $_SESSION['user']['allowed_client_ids'] = [];
            $_SESSION['user']['selected_client_ids'] = [];
            return;
        }
        $scopeIds = TenantScopeResolver::resolveForUser($refreshed);
        $_SESSION['user']['allowed_client_ids'] = $scopeIds;
        $_SESSION['user']['selected_client_ids'] = $scopeIds;
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

    public static function isClienteEditor(): bool
    {
        return (self::user()['tipo_acesso'] ?? null) === 'cliente';
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

    public static function platformAccess(): string
    {
        $value = (string)(self::user()['platform_access'] ?? 'WEB_PWA');
        return $value !== '' ? $value : 'WEB_PWA';
    }

    public static function canUseWeb(): bool
    {
        if (self::isInstituto()) {
            return true;
        }
        return in_array(self::platformAccess(), ['WEB', 'WEB_PWA'], true);
    }

    public static function canUsePwa(): bool
    {
        if (self::isInstituto()) {
            return true;
        }
        return in_array(self::platformAccess(), ['PWA', 'WEB_PWA'], true);
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

    public static function canExportPlanosAcao(): bool
    {
        // Modelo atual nao usa RBAC granular. A permissao exportar_planos_acao
        // fica liberada para instituto e perfis de cliente.
        return self::isInstituto() || self::isCliente();
    }
}
