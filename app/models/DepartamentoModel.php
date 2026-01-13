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
                UNIQUE KEY dep_unique (cliente_id, nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } catch (\PDOException $e) {}
    }

    public function all(): array
    {
        $this->ensureTable();
        $stmt = $this->db->query('SELECT d.id, d.nome, d.cliente_id, c.nome_empresa AS cliente FROM departamentos d JOIN clientes c ON c.id = d.cliente_id ORDER BY c.nome_empresa, d.nome');
        return $stmt->fetchAll();
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, cliente_id FROM departamentos WHERE cliente_id = :cid ORDER BY nome');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, cliente_id FROM departamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cliente_id)');
        $stmt->execute(['nome' => $data['nome'], 'cliente_id' => (int)$data['cliente_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE departamentos SET nome = :nome, cliente_id = :cliente_id WHERE id = :id');
        return $stmt->execute(['nome' => $data['nome'], 'cliente_id' => (int)$data['cliente_id'], 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM departamentos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
