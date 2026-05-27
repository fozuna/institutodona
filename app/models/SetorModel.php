<?php
namespace App\Models;

class SetorModel extends BaseModel
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
            $this->db->exec('CREATE TABLE IF NOT EXISTS setores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                departamento_id INT NOT NULL,
                UNIQUE KEY setor_unique (departamento_id, nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('setores', 'ativo')) {
                $this->db->exec('ALTER TABLE setores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
        } catch (\PDOException $e) {}
    }

    public function all(): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sa');
        $stmt = $this->db->prepare("SELECT s.id, s.nome, s.departamento_id FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE $scope ORDER BY s.nome");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $sql = 'SELECT s.id, s.nome, s.departamento_id, d.nome AS departamento
                FROM setores s JOIN departamentos d ON d.id = s.departamento_id
                WHERE d.cliente_id = :cid ORDER BY d.nome, s.nome';
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sbc');
        $sql = str_replace('WHERE d.cliente_id = :cid', 'WHERE d.cliente_id = :cid AND ' . $scope, $sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByClientes(array $clienteIds): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        $params = [];
        $where = [
            $this->buildClienteScopeClause('d.cliente_id', $clienteIds, $params, 'sabc_scope'),
            $this->tenantInCondition('d.cliente_id', $params, 'sabc_tenant'),
        ];
        $stmt = $this->db->prepare(
            "SELECT s.id, s.nome, s.departamento_id, d.nome AS departamento
             FROM setores s
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY d.nome, s.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sf');
        $cols = ['s.id', 's.nome', 's.departamento_id'];
        if (\App\Database\Database::columnExists('setores', 'ativo')) {
            $cols[] = 's.ativo';
        }
        $stmt = $this->db->prepare("SELECT " . implode(', ', $cols) . " FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE s.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function activeByDepartamento(int $departamentoId): array
    {
        $this->ensureTable();
        $params = ['dep' => $departamentoId];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sda');
        $stmt = $this->db->prepare(
            "SELECT s.id, s.nome, s.departamento_id, s.ativo
             FROM setores s
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE s.departamento_id = :dep AND s.ativo = 1 AND $scope
             ORDER BY s.nome"
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
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sbcact');
        $stmt = $this->db->prepare(
            "SELECT s.id, s.nome, s.departamento_id, d.nome AS departamento
             FROM setores s
             JOIN departamentos d ON d.id = s.departamento_id
             WHERE d.cliente_id = :cid AND s.ativo = 1 AND d.ativo = 1 AND $scope
             ORDER BY d.nome, s.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findActive(int $id, ?int $departamentoId = null): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $where = ['s.id = :id', 's.ativo = 1'];
        if ($departamentoId !== null && $departamentoId > 0) {
            $params['dep'] = $departamentoId;
            $where[] = 's.departamento_id = :dep';
        }
        $where[] = $this->tenantInCondition('d.cliente_id', $params, 'sfa');
        $stmt = $this->db->prepare(
            'SELECT s.id, s.nome, s.departamento_id, s.ativo
             FROM setores s
             JOIN departamentos d ON d.id = s.departamento_id
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
        $depId = (int)$data['departamento_id'];
        $params = ['dep' => $depId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'sc');
        $check = $this->db->prepare("SELECT id FROM departamentos WHERE id = :dep AND $scope LIMIT 1");
        $check->execute($params);
        if (!$check->fetch()) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :departamento_id)');
        $stmt->execute(['nome' => $data['nome'], 'departamento_id' => (int)$data['departamento_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $params = ['nome' => $data['nome'], 'departamento_id' => (int)$data['departamento_id'], 'id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'su');
        $stmt = $this->db->prepare("UPDATE setores s JOIN departamentos d ON d.id = s.departamento_id SET s.nome = :nome, s.departamento_id = :departamento_id WHERE s.id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sd');
        $stmt = $this->db->prepare("DELETE s FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE s.id = :id AND $scope");
        return $stmt->execute($params);
    }
}
