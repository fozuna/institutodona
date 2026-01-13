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
        } catch (\PDOException $e) {}
    }

    public function all(): array
    {
        $this->ensureTable();
        $stmt = $this->db->query('SELECT s.id, s.nome, s.departamento_id FROM setores s ORDER BY s.nome');
        return $stmt->fetchAll();
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $sql = 'SELECT s.id, s.nome, s.departamento_id, d.nome AS departamento
                FROM setores s JOIN departamentos d ON d.id = s.departamento_id
                WHERE d.cliente_id = :cid ORDER BY d.nome, s.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, departamento_id FROM setores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :departamento_id)');
        $stmt->execute(['nome' => $data['nome'], 'departamento_id' => (int)$data['departamento_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE setores SET nome = :nome, departamento_id = :departamento_id WHERE id = :id');
        return $stmt->execute(['nome' => $data['nome'], 'departamento_id' => (int)$data['departamento_id'], 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM setores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
