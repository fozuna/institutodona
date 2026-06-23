<?php
namespace App\Models;

class FuncaoModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS funcoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                setor_id INT NOT NULL,
                UNIQUE KEY func_unique (setor_id, nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('funcoes', 'ativo')) {
                $this->db->exec('ALTER TABLE funcoes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
        } catch (\PDOException $e) {}
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return [];
        }
        try {
            if (!\App\Database\Database::tableExists('departamentos')) {
                (new \App\Models\DepartamentoModel())->all();
            }
            if (!\App\Database\Database::tableExists('setores')) {
                (new \App\Models\SetorModel())->all();
            }
        } catch (\PDOException $e) {
        }
        try {
            $params = ['cid' => $clienteId];
            $stmt = $this->db->prepare(
                'SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
                 FROM funcoes f
                 JOIN setores s ON s.id = f.setor_id
                 JOIN departamentos d ON d.id = s.departamento_id
                 WHERE ' . $this->departamentoVisibilitySql('d', ':cid') . '
                 ORDER BY d.nome, s.nome, f.nome'
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function allByClientes(array $clienteIds): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        try {
            if (!\App\Database\Database::tableExists('departamentos')) {
                (new \App\Models\DepartamentoModel())->all();
            }
            if (!\App\Database\Database::tableExists('setores')) {
                (new \App\Models\SetorModel())->all();
            }
        } catch (\PDOException $e) {
        }
        try {
            $params = [];
            $where = [$this->departamentoVisibilityForClientesCondition('d', $clienteIds, $params, 'fabc_scope')];
            $tenantScope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'fabc_tenant');
            if ($tenantScope !== '1=1') {
                $where[] = $tenantScope;
            }
            $stmt = $this->db->prepare(
                "SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
                 FROM funcoes f
                 JOIN setores s ON s.id = f.setor_id
                 JOIN departamentos d ON d.id = s.departamento_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY d.nome, s.nome, f.nome"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function allBySetor(int $setorId, array $clienteIds = []): array
    {
        $this->ensureTable();
        $setorId = (int)$setorId;
        if ($setorId <= 0) {
            return [];
        }
        $params = ['sid' => $setorId];
        $where = ['s.id = :sid'];
        if (!empty($clienteIds)) {
            $where[] = $this->departamentoVisibilityForClientesCondition('d', $clienteIds, $params, 'fbs_scope');
        }
        $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'fbs_tenant');
        $stmt = $this->db->prepare(
            "SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
             FROM funcoes f
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY f.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
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
            "SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
             FROM funcoes f
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE f.ativo = 1
               AND s.ativo = 1
               AND d.ativo = 1
               AND " . $this->departamentoVisibilitySql('d', ':cid') . "
             ORDER BY d.nome, s.nome, f.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activeBySetor(int $setorId, array $clienteIds = []): array
    {
        $this->ensureTable();
        $setorId = (int)$setorId;
        if ($setorId <= 0) {
            return [];
        }
        $params = ['sid' => $setorId];
        $where = ['s.id = :sid', 'f.ativo = 1', 's.ativo = 1', 'd.ativo = 1'];
        if (!empty($clienteIds)) {
            $where[] = $this->departamentoVisibilityForClientesCondition('d', $clienteIds, $params, 'fbsact_scope');
        }
        $where[] = $this->tenantDepartamentoVisibilityCondition('d', $params, 'fbsact_tenant');
        $stmt = $this->db->prepare(
            "SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
             FROM funcoes f
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY f.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'ff');
        $cols = ['f.id', 'f.nome', 'f.setor_id', 'd.cliente_id AS cliente_id', 's.departamento_id'];
        if (\App\Database\Database::columnExists('funcoes', 'ativo')) {
            $cols[] = 'f.ativo';
        }
        $stmt = $this->db->prepare("SELECT " . implode(', ', $cols) . " FROM funcoes f JOIN setores s ON s.id = f.setor_id JOIN departamentos d ON d.id = s.departamento_id WHERE f.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        if (!$this->setorBelongsToCatalogCliente((int)$data['setor_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor_id)');
        $stmt->execute(['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        if (!$this->setorBelongsToCatalogCliente((int)$data['setor_id'])) {
            return false;
        }
        $params = ['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id'], 'id' => $id];
        $scope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'fu');
        $stmt = $this->db->prepare("UPDATE funcoes f JOIN setores s ON s.id = f.setor_id JOIN departamentos d ON d.id = s.departamento_id SET f.nome = :nome, f.setor_id = :setor_id WHERE f.id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantDepartamentoVisibilityCondition('d', $params, 'fd');
        $stmt = $this->db->prepare("DELETE f FROM funcoes f JOIN setores s ON s.id = f.setor_id JOIN departamentos d ON d.id = s.departamento_id WHERE f.id = :id AND $scope");
        return $stmt->execute($params);
    }
}
