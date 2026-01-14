<?php
namespace App\Models;

class ColaboradorModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS colaboradores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                email VARCHAR(180) NULL,
                funcao_id INT NOT NULL,
                cliente_id INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('colaboradores', 'cliente_id')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN cliente_id INT NULL');
            }
        } catch (\PDOException $e) {}
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $sql = 'SELECT col.id, col.nome, col.email, col.funcao_id, col.cliente_id,
                       f.nome AS funcao, s.nome AS setor, d.nome AS departamento
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE col.cliente_id = :cid
                ORDER BY d.nome, s.nome, f.nome, col.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, email, funcao_id FROM colaboradores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:nome, :email, :funcao_id, :cliente_id)');
        $stmt->execute(['nome' => $data['nome'], 'email' => $data['email'] ?? null, 'funcao_id' => (int)$data['funcao_id'], 'cliente_id' => $data['cliente_id'] ?? null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE colaboradores SET nome = :nome, email = :email, funcao_id = :funcao_id, cliente_id = :cliente_id WHERE id = :id');
        return $stmt->execute(['nome' => $data['nome'], 'email' => $data['email'] ?? null, 'funcao_id' => (int)$data['funcao_id'], 'cliente_id' => $data['cliente_id'] ?? null, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM colaboradores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
