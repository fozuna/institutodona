<?php
namespace App\Models;

class SetorModel extends BaseModel
{
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

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('d.cliente_id', $params, 'sf');
        $stmt = $this->db->prepare("SELECT s.id, s.nome, s.departamento_id FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE s.id = :id AND $scope");
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
