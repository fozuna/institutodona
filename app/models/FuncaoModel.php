<?php
namespace App\Models;

class FuncaoModel extends BaseModel
{
    private function buildClienteScopeClause(string $column, array $clienteIds, array &$params, string $prefix): string
    {
        $holders = [];
        foreach (array_values($clienteIds) as $i => $clienteId) {
            $key = $prefix . $i;
            $holders[] = ':' . $key;
            $params[$key] = (int)$clienteId;
        }
        return $column . ' IN (' . implode(',', $holders) . ')';
    }

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
            $sql = 'SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
                    FROM funcoes f JOIN setores s ON s.id = f.setor_id
                    JOIN departamentos d ON d.id = s.departamento_id
                    WHERE d.cliente_id = :cid ORDER BY d.nome, s.nome, f.nome';
            $params = ['cid' => $clienteId];
            $scope = $this->tenantInCondition('d.cliente_id', $params, 'fbc');
            $sql = str_replace('WHERE d.cliente_id = :cid', 'WHERE d.cliente_id = :cid AND ' . $scope, $sql);
            $stmt = $this->db->prepare($sql);
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
            $where = [
                $this->buildClienteScopeClause('d.cliente_id', $clienteIds, $params, 'fabc_scope'),
                $this->tenantInCondition('d.cliente_id', $params, 'fabc_tenant'),
            ];
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
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        $params = ['sid' => $setorId];
        $where = ['s.id = :sid'];
        if (!empty($clienteIds)) {
            $where[] = $this->buildClienteScopeClause('d.cliente_id', $clienteIds, $params, 'fbs_scope');
        }
        $where[] = $this->tenantInCondition('d.cliente_id', $params, 'fbs_tenant');
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
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'fabcact');
        $stmt = $this->db->prepare(
            "SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
             FROM funcoes f
             JOIN setores s ON s.id = f.setor_id
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE d.cliente_id = :cid AND f.ativo = 1 AND s.ativo = 1 AND d.ativo = 1 AND $scope
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
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        $params = ['sid' => $setorId];
        $where = ['s.id = :sid', 'f.ativo = 1', 's.ativo = 1', 'd.ativo = 1'];
        if (!empty($clienteIds)) {
            $where[] = $this->buildClienteScopeClause('d.cliente_id', $clienteIds, $params, 'fbsact_scope');
        }
        $where[] = $this->tenantInCondition('d.cliente_id', $params, 'fbsact_tenant');
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
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'ff');
        $cols = ['f.id', 'f.nome', 'f.setor_id'];
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
        $params = ['sid' => (int)$data['setor_id']];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'fc');
        $check = $this->db->prepare("SELECT s.id FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE s.id = :sid AND $scope LIMIT 1");
        $check->execute($params);
        if (!$check->fetch()) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor_id)');
        $stmt->execute(['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $params = ['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id'], 'id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'fu');
        $stmt = $this->db->prepare("UPDATE funcoes f JOIN setores s ON s.id = f.setor_id JOIN departamentos d ON d.id = s.departamento_id SET f.nome = :nome, f.setor_id = :setor_id WHERE f.id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'fd');
        $stmt = $this->db->prepare("DELETE f FROM funcoes f JOIN setores s ON s.id = f.setor_id JOIN departamentos d ON d.id = s.departamento_id WHERE f.id = :id AND $scope");
        return $stmt->execute($params);
    }
}
