<?php
namespace App\Models;

class DepartamentoModel extends BaseModel
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
            $this->db->exec('CREATE TABLE IF NOT EXISTS departamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                cliente_id INT NOT NULL,
                UNIQUE KEY dep_unique (cliente_id, nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('departamentos', 'ativo')) {
                $this->db->exec('ALTER TABLE departamentos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
        } catch (\PDOException $e) {}
    }

    public function all(): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'da');
        $stmt = $this->db->prepare("SELECT d.id, d.nome, d.cliente_id, c.nome_empresa AS cliente FROM departamentos d JOIN clientes c ON c.id = d.cliente_id WHERE $scope ORDER BY c.nome_empresa, d.nome");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'dbc');
        $stmt = $this->db->prepare("SELECT id, nome, cliente_id FROM departamentos WHERE cliente_id = :cid AND $scope ORDER BY nome");
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
            $this->buildClienteScopeClause('d.cliente_id', $clienteIds, $params, 'dabc_scope'),
            $this->tenantInCondition('d.cliente_id', $params, 'dabc_tenant'),
        ];
        $stmt = $this->db->prepare("SELECT d.id, d.nome, d.cliente_id, c.nome_empresa AS cliente FROM departamentos d JOIN clientes c ON c.id = d.cliente_id WHERE " . implode(' AND ', $where) . " ORDER BY c.nome_empresa, d.nome");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activeByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'dact');
        $stmt = $this->db->prepare(
            "SELECT id, nome, cliente_id, ativo
             FROM departamentos
             WHERE cliente_id = :cid AND ativo = 1 AND $scope
             ORDER BY nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'df');
        $stmt = $this->db->prepare("SELECT id, nome, cliente_id FROM departamentos WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActive(int $id, ?int $clienteId = null): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $where = ['id = :id', 'ativo = 1'];
        if ($clienteId !== null && $clienteId > 0) {
            $params['cid'] = $clienteId;
            $where[] = 'cliente_id = :cid';
        }
        $where[] = $this->tenantInCondition('cliente_id', $params, 'dfa');
        $stmt = $this->db->prepare(
            'SELECT id, nome, cliente_id, ativo
             FROM departamentos
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
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if (($data['cliente_id'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['cliente_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cliente_id)');
        $stmt->execute(['nome' => $data['nome'], 'cliente_id' => (int)$data['cliente_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        $params = ['nome' => $data['nome'], 'cliente_id' => (int)$data['cliente_id'], 'id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'du');
        $stmt = $this->db->prepare("UPDATE departamentos SET nome = :nome, cliente_id = :cliente_id WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'dd');
        $stmt = $this->db->prepare("DELETE FROM departamentos WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }
}
