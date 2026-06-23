<?php
namespace App\Models;

use App\Database\Database;
use App\Core\Auth;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    /** @var array<int,int> */
    private array $catalogRootCache = [];
    /** @var array<int>|null */
    private ?array $catalogTenantClientIdsCache = null;

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

    protected function resolveCatalogClienteId(?int $clienteId): ?int
    {
        $clienteId = $clienteId !== null ? (int)$clienteId : null;
        if ($clienteId === null || $clienteId <= 0) {
            return $clienteId;
        }
        if (isset($this->catalogRootCache[$clienteId])) {
            return $this->catalogRootCache[$clienteId];
        }
        try {
            $stmt = $this->db->prepare('SELECT id, is_matriz, matriz_id FROM clientes WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $clienteId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                return $this->catalogRootCache[$clienteId] = $clienteId;
            }
            $matrizId = (int)($row['matriz_id'] ?? 0);
            $isMatriz = $matrizId <= 0 && (int)($row['is_matriz'] ?? 1) === 1;
            $rootId = $isMatriz ? $clienteId : ($matrizId > 0 ? $matrizId : $clienteId);
            return $this->catalogRootCache[$clienteId] = $rootId;
        } catch (\Throwable $e) {
            return $this->catalogRootCache[$clienteId] = $clienteId;
        }
    }

    protected function normalizeCatalogClienteId(?int $clienteId): ?int
    {
        $scoped = $this->normalizeScopedClienteId($clienteId);
        return $this->resolveCatalogClienteId($scoped);
    }

    /**
     * @param array<int> $clienteIds
     * @return array<int>
     */
    protected function normalizeCatalogClienteIds(array $clienteIds): array
    {
        $normalized = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $clienteIds)))) as $clienteId) {
            $rootId = (int)($this->resolveCatalogClienteId($clienteId) ?? 0);
            if ($rootId > 0) {
                $normalized[] = $rootId;
            }
        }
        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        return $normalized;
    }

    /**
     * @return array<int>
     */
    protected function catalogTenantClientIds(): array
    {
        if (!$this->hasTenantRestriction()) {
            return [];
        }
        if ($this->catalogTenantClientIdsCache !== null) {
            return $this->catalogTenantClientIdsCache;
        }
        $expanded = [];
        foreach ($this->tenantClientIds() as $clienteId) {
            $clienteId = (int)$clienteId;
            if ($clienteId <= 0) {
                continue;
            }
            $expanded[] = $clienteId;
            $rootId = (int)($this->resolveCatalogClienteId($clienteId) ?? 0);
            if ($rootId > 0) {
                $expanded[] = $rootId;
            }
        }
        $expanded = array_values(array_unique(array_filter(array_map('intval', $expanded))));
        sort($expanded);
        return $this->catalogTenantClientIdsCache = $expanded;
    }

    protected function canAccessCatalogClienteId(?int $clienteId): bool
    {
        if ($clienteId === null || $clienteId <= 0) {
            return false;
        }
        if (!Auth::isLoggedIn() || Auth::isInstituto()) {
            return true;
        }
        return in_array((int)$clienteId, $this->catalogTenantClientIds(), true);
    }

    protected function tenantCatalogInCondition(string $column, array &$params, string $prefix = 'catalog_scope'): string
    {
        if (!$this->hasTenantRestriction()) {
            return '1=1';
        }
        $ids = $this->catalogTenantClientIds();
        if (empty($ids)) {
            return '1=1';
        }
        $holders = [];
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $holders[] = ':' . $key;
            $params[$key] = (int)$id;
        }
        return $column . ' IN (' . implode(',', $holders) . ')';
    }

    protected function departamentoVisibilitySql(string $departamentoAlias, string $clienteExpression): string
    {
        $departamentoAlias = trim($departamentoAlias);
        $clienteExpression = trim($clienteExpression);
        if ($departamentoAlias === '' || $clienteExpression === '') {
            return '1=0';
        }

        return 'EXISTS (
                    SELECT 1
                    FROM clientes req_cli
                    JOIN clientes owner_cli ON owner_cli.id = ' . $departamentoAlias . '.cliente_id
                    LEFT JOIN departamento_clientes dcv
                      ON dcv.departamento_id = ' . $departamentoAlias . '.id
                     AND dcv.cliente_id = req_cli.id
                    WHERE req_cli.id = ' . $clienteExpression . '
                      AND (
                        dcv.id IS NOT NULL
                        OR (
                            COALESCE(' . $departamentoAlias . '.compartilhar_todas_filiais, 0) = 1
                            AND (CASE WHEN COALESCE(owner_cli.matriz_id, 0) > 0 THEN owner_cli.matriz_id ELSE owner_cli.id END)
                                = (CASE WHEN COALESCE(req_cli.matriz_id, 0) > 0 THEN req_cli.matriz_id ELSE req_cli.id END)
                        )
                      )
                )';
    }

    protected function tenantDepartamentoVisibilityCondition(
        string $departamentoAlias,
        array &$params,
        string $prefix = 'dep_scope'
    ): string {
        if (!$this->hasTenantRestriction()) {
            return '1=1';
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $this->tenantClientIds()))));
        if (empty($ids)) {
            return '1=1';
        }

        $parts = [];
        foreach ($ids as $i => $clienteId) {
            $key = $prefix . $i;
            $params[$key] = $clienteId;
            $parts[] = $this->departamentoVisibilitySql($departamentoAlias, ':' . $key);
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /**
     * @param array<int> $clienteIds
     */
    protected function departamentoVisibilityForClientesCondition(
        string $departamentoAlias,
        array $clienteIds,
        array &$params,
        string $prefix = 'dep_client'
    ): string {
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return '1=0';
        }

        $parts = [];
        foreach ($clienteIds as $i => $clienteId) {
            $key = $prefix . $i;
            $params[$key] = $clienteId;
            $parts[] = $this->departamentoVisibilitySql($departamentoAlias, ':' . $key);
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    protected function departamentoBelongsToCatalogCliente(int $departamentoId, ?int $clienteId = null): bool
    {
        $departamentoId = (int)$departamentoId;
        if ($departamentoId <= 0) {
            return false;
        }
        $params = ['id' => $departamentoId];
        $where = ['d.id = :id'];
        if ($clienteId !== null && $clienteId > 0) {
            $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
            if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
                return false;
            }
            $params['cliente_id'] = $clienteId;
            $where[] = $this->departamentoVisibilitySql('d', ':cliente_id');
        } else {
            $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'dep_catalog');
        }
        $stmt = $this->db->prepare(
            'SELECT d.id FROM departamentos d WHERE ' . implode(' AND ', $where) . ' LIMIT 1'
        );
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    protected function setorBelongsToCatalogCliente(int $setorId, ?int $clienteId = null, ?int $departamentoId = null): bool
    {
        $setorId = (int)$setorId;
        if ($setorId <= 0) {
            return false;
        }
        $params = ['id' => $setorId];
        $where = ['s.id = :id'];
        if ($departamentoId !== null && $departamentoId > 0) {
            $params['departamento_id'] = (int)$departamentoId;
            $where[] = 's.departamento_id = :departamento_id';
        }
        if ($clienteId !== null && $clienteId > 0) {
            $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
            if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
                return false;
            }
            $params['cliente_id'] = $clienteId;
            $where[] = $this->departamentoVisibilitySql('d', ':cliente_id');
        } else {
            $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'setor_catalog');
        }
        $stmt = $this->db->prepare(
            'SELECT s.id
             FROM setores s
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1'
        );
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    protected function funcaoBelongsToCatalogCliente(
        int $funcaoId,
        ?int $clienteId = null,
        ?int $setorId = null,
        ?int $departamentoId = null
    ): bool {
        $funcaoId = (int)$funcaoId;
        if ($funcaoId <= 0) {
            return false;
        }
        $params = ['id' => $funcaoId];
        $where = ['f.id = :id'];
        if ($setorId !== null && $setorId > 0) {
            $params['setor_id'] = (int)$setorId;
            $where[] = 'f.setor_id = :setor_id';
        }
        if ($departamentoId !== null && $departamentoId > 0) {
            $params['departamento_id'] = (int)$departamentoId;
            $where[] = 's.departamento_id = :departamento_id';
        }
        if ($clienteId !== null && $clienteId > 0) {
            $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
            if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
                return false;
            }
            $params['cliente_id'] = $clienteId;
            $where[] = $this->departamentoVisibilitySql('d', ':cliente_id');
        } else {
            $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'func_catalog');
        }
        $stmt = $this->db->prepare(
            'SELECT f.id
             FROM funcoes f
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1'
        );
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }
}
