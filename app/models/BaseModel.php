<?php
namespace App\Models;

use App\Database\Database;
use App\Core\Auth;
use PDO;

abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected function hasTenantRestriction(): bool
    {
        return !Auth::isInstituto() && count($this->tenantClientIds()) > 0;
    }

    protected function tenantClientIds(): array
    {
        return Auth::allowedClientIds();
    }

    protected function canAccessClienteId(?int $clienteId): bool
    {
        if ($clienteId === null || $clienteId <= 0) {
            return false;
        }
        if (!Auth::isLoggedIn() || Auth::isInstituto()) {
            return true;
        }
        return in_array($clienteId, $this->tenantClientIds(), true);
    }

    protected function normalizeScopedClienteId(?int $clienteId): ?int
    {
        if (Auth::isInstituto()) {
            return $clienteId;
        }
        $ids = $this->tenantClientIds();
        if (empty($ids)) {
            return $clienteId;
        }
        if ($clienteId !== null && in_array($clienteId, $ids, true)) {
            return $clienteId;
        }
        return (int)$ids[0];
    }

    protected function tenantInCondition(string $column, array &$params, string $prefix = 'scope'): string
    {
        if (!$this->hasTenantRestriction()) {
            return '1=1';
        }
        $ids = $this->tenantClientIds();
        $holders = [];
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $holders[] = ':' . $key;
            $params[$key] = (int)$id;
        }
        return $column . ' IN (' . implode(',', $holders) . ')';
    }
}
