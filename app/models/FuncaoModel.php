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
        } catch (\PDOException $e) {}
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $sql = 'SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
                FROM funcoes f JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE d.cliente_id = :cid ORDER BY d.nome, s.nome, f.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, setor_id FROM funcoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor_id)');
        $stmt->execute(['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id']]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE funcoes SET nome = :nome, setor_id = :setor_id WHERE id = :id');
        return $stmt->execute(['nome' => $data['nome'], 'setor_id' => (int)$data['setor_id'], 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM funcoes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
