<?php
namespace App\Models;

class DepartamentoModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS departamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                cliente_id INT NOT NULL,
                compartilhar_todas_filiais TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE KEY dep_unique (cliente_id, nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('departamentos', 'ativo')) {
                $this->db->exec('ALTER TABLE departamentos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('departamentos', 'compartilhar_todas_filiais')) {
                $this->db->exec('ALTER TABLE departamentos ADD COLUMN compartilhar_todas_filiais TINYINT(1) NOT NULL DEFAULT 0 AFTER cliente_id');
            }
            $this->db->exec('CREATE TABLE IF NOT EXISTS departamento_clientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                departamento_id INT NOT NULL,
                cliente_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_departamento_cliente (departamento_id, cliente_id),
                KEY idx_departamento_clientes_cliente (cliente_id),
                CONSTRAINT fk_departamento_clientes_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
                CONSTRAINT fk_departamento_clientes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $this->db->exec('INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id)
                             SELECT id, cliente_id
                             FROM departamentos');
        } catch (\PDOException $e) {}
    }

    /**
     * @param array<int> $clienteIds
     * @return array<int>
     */
    private function normalizeLinkedClienteIds(int $ownerClienteId, array $clienteIds): array
    {
        $ownerClienteId = (int)$this->normalizeScopedClienteId($ownerClienteId);
        if ($ownerClienteId <= 0 || !$this->canAccessClienteId($ownerClienteId)) {
            return [];
        }

        $ownerGroupId = (int)($this->resolveCatalogClienteId($ownerClienteId) ?? 0);
        $valid = [$ownerClienteId];
        foreach (array_values(array_unique(array_filter(array_map('intval', $clienteIds)))) as $clienteId) {
            $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
            if ($clienteId <= 0) {
                continue;
            }
            if (!$this->canAccessClienteId($clienteId)) {
                continue;
            }
            $candidateGroupId = (int)($this->resolveCatalogClienteId($clienteId) ?? 0);
            if ($candidateGroupId <= 0 || $candidateGroupId !== $ownerGroupId) {
                continue;
            }
            $valid[] = $clienteId;
        }

        $valid = array_values(array_unique($valid));
        sort($valid);
        return $valid;
    }

    /**
     * @param array<int> $clienteIds
     */
    private function syncClienteLinks(int $departamentoId, int $ownerClienteId, array $clienteIds): void
    {
        $clienteIds = $this->normalizeLinkedClienteIds($ownerClienteId, $clienteIds);
        if (empty($clienteIds)) {
            throw new \RuntimeException('Departamento sem empresas válidas para compartilhamento.');
        }

        $delete = $this->db->prepare('DELETE FROM departamento_clientes WHERE departamento_id = :departamento_id');
        $delete->execute(['departamento_id' => $departamentoId]);

        $insert = $this->db->prepare(
            'INSERT INTO departamento_clientes (departamento_id, cliente_id)
             VALUES (:departamento_id, :cliente_id)'
        );
        foreach ($clienteIds as $clienteId) {
            $insert->execute([
                'departamento_id' => $departamentoId,
                'cliente_id' => $clienteId,
            ]);
        }
    }

    private function baseSelect(): string
    {
        return "SELECT d.id,
                       d.nome,
                       d.cliente_id,
                       d.compartilhar_todas_filiais,
                       d.ativo,
                       owner.nome_empresa AS cliente,
                       GROUP_CONCAT(DISTINCT linked.nome_empresa ORDER BY linked.nome_empresa SEPARATOR ', ') AS clientes_vinculados,
                       COUNT(DISTINCT dc.cliente_id) AS total_clientes_vinculados
                FROM departamentos d
                JOIN clientes owner ON owner.id = d.cliente_id
                LEFT JOIN departamento_clientes dc ON dc.departamento_id = d.id
                LEFT JOIN clientes linked ON linked.id = dc.cliente_id";
    }

    private function hydrateVisibilitySummary(array $row): array
    {
        $row['clientes_vinculados'] = trim((string)($row['clientes_vinculados'] ?? ''));
        $row['total_clientes_vinculados'] = (int)($row['total_clientes_vinculados'] ?? 0);
        if ((int)($row['compartilhar_todas_filiais'] ?? 0) === 1) {
            $row['visibilidade_resumo'] = 'Todas as empresas do grupo';
            return $row;
        }
        if ($row['total_clientes_vinculados'] <= 1) {
            $row['visibilidade_resumo'] = 'Exclusivo';
            return $row;
        }
        $row['visibilidade_resumo'] = $row['clientes_vinculados'] !== ''
            ? $row['clientes_vinculados']
            : 'Compartilhado com empresas selecionadas';
        return $row;
    }

    public function all(): array
    {
        $this->ensureTable();
        $params = [];
        $where = [$this->tenantDepartamentoVisibilityCondition('d', $params, 'da')];
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE ' . implode(' AND ', $where) . '
            GROUP BY d.id, d.nome, d.cliente_id, d.compartilhar_todas_filiais, d.ativo, owner.nome_empresa
            ORDER BY owner.nome_empresa, d.nome');
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->hydrateVisibilitySummary($row), $stmt->fetchAll() ?: []);
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $where = [$this->departamentoVisibilitySql('d', ':cid')];
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE ' . implode(' AND ', $where) . '
            GROUP BY d.id, d.nome, d.cliente_id, d.compartilhar_todas_filiais, d.ativo, owner.nome_empresa
            ORDER BY d.nome');
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->hydrateVisibilitySummary($row), $stmt->fetchAll() ?: []);
    }

    public function allByClientes(array $clienteIds): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        $params = [];
        $where = [$this->departamentoVisibilityForClientesCondition('d', $clienteIds, $params, 'dabc_scope')];
        $tenantScope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'dabc_tenant');
        if ($tenantScope !== '1=1') {
            $where[] = $tenantScope;
        }
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE ' . implode(' AND ', $where) . '
            GROUP BY d.id, d.nome, d.cliente_id, d.compartilhar_todas_filiais, d.ativo, owner.nome_empresa
            ORDER BY owner.nome_empresa, d.nome');
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->hydrateVisibilitySummary($row), $stmt->fetchAll() ?: []);
    }

    public function activeByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $stmt = $this->db->prepare(
            "SELECT d.id, d.nome, d.cliente_id, d.ativo, d.compartilhar_todas_filiais
             FROM departamentos d
             WHERE d.ativo = 1
               AND " . $this->departamentoVisibilitySql('d', ':cid') . "
             ORDER BY nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE d.id = :id
              AND ' . $this->tenantDepartamentoVisibilityCondition('d', $params, 'df') . '
            GROUP BY d.id, d.nome, d.cliente_id, d.compartilhar_todas_filiais, d.ativo, owner.nome_empresa');
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['cliente_ids'] = $this->linkedClienteIds((int)$row['id']);
        return $this->hydrateVisibilitySummary($row);
    }

    public function findActive(int $id, ?int $clienteId = null): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $where = ['d.id = :id', 'd.ativo = 1'];
        if ($clienteId !== null && $clienteId > 0) {
            $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
            if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
                return null;
            }
            $params['cid'] = $clienteId;
            $where[] = $this->departamentoVisibilitySql('d', ':cid');
        } else {
            $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'dfa');
        }
        $stmt = $this->db->prepare(
            'SELECT d.id, d.nome, d.cliente_id, d.ativo, d.compartilhar_todas_filiais
             FROM departamentos d
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $ownerClienteId = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if ($ownerClienteId <= 0 || !$this->canAccessClienteId($ownerClienteId)) {
            return 0;
        }
        $shareAll = !empty($data['compartilhar_todas_filiais']) ? 1 : 0;
        $clienteIds = isset($data['cliente_ids']) && is_array($data['cliente_ids']) ? $data['cliente_ids'] : [];

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'INSERT INTO departamentos (nome, cliente_id, compartilhar_todas_filiais)
                 VALUES (:nome, :cliente_id, :compartilhar_todas_filiais)'
            );
            $stmt->execute([
                'nome' => trim((string)$data['nome']),
                'cliente_id' => $ownerClienteId,
                'compartilhar_todas_filiais' => $shareAll,
            ]);
            $id = (int)$this->db->lastInsertId();
            $this->syncClienteLinks($id, $ownerClienteId, $clienteIds);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $current = $this->find($id);
        if (!$current) {
            return false;
        }
        $ownerClienteId = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : (int)$current['cliente_id']);
        if ($ownerClienteId <= 0 || !$this->canAccessClienteId($ownerClienteId)) {
            return false;
        }

        $clienteIds = isset($data['cliente_ids']) && is_array($data['cliente_ids'])
            ? $data['cliente_ids']
            : $this->linkedClienteIds($id);

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'UPDATE departamentos
                 SET nome = :nome,
                     cliente_id = :cliente_id,
                     compartilhar_todas_filiais = :compartilhar_todas_filiais
                 WHERE id = :id'
            );
            $stmt->execute([
                'nome' => trim((string)$data['nome']),
                'cliente_id' => $ownerClienteId,
                'compartilhar_todas_filiais' => !empty($data['compartilhar_todas_filiais']) ? 1 : 0,
                'id' => $id,
            ]);
            $this->syncClienteLinks($id, $ownerClienteId, $clienteIds);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'dd');
        $stmt = $this->db->prepare("DELETE d FROM departamentos d WHERE d.id = :id AND $scope");
        return $stmt->execute($params);
    }

    /**
     * @return array<int>
     */
    public function linkedClienteIds(int $departamentoId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            'SELECT cliente_id
             FROM departamento_clientes
             WHERE departamento_id = :departamento_id
             ORDER BY cliente_id'
        );
        $stmt->execute(['departamento_id' => $departamentoId]);
        return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll() ?: [], 'cliente_id'))));
    }
}
