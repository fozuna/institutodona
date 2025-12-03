<?php
namespace App\Models;

class ConsultorModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS consultores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                email VARCHAR(180) NOT NULL UNIQUE,
                telefone VARCHAR(30) NULL,
                especialidade VARCHAR(255) NULL,
                senioridade ENUM(\'Junior\',\'Pleno\',\'Senior\') NULL,
                cidade VARCHAR(120) NULL,
                estado CHAR(2) NULL,
                observacoes TEXT NULL,
                usuario_id INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } catch (\PDOException $e) {
            // silently ignore in case of restricted permissions
        }
    }
    public function all(): array
    {
        $this->ensureTable();
        try {
            $stmt = $this->db->query('SELECT id, nome, email FROM consultores ORDER BY nome');
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        try {
            $stmt = $this->db->prepare('SELECT id, nome, email, telefone, especialidade, senioridade, cidade, estado, observacoes FROM consultores WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        try {
            $stmt = $this->db->prepare('INSERT INTO consultores (nome, email, telefone, especialidade, senioridade, cidade, estado, observacoes) VALUES (:nome, :email, :telefone, :especialidade, :senioridade, :cidade, :estado, :observacoes)');
            $stmt->execute([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'] ?? null,
                'especialidade' => $data['especialidade'] ?? null,
                'senioridade' => $data['senioridade'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
            ]);
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare('INSERT INTO consultores (nome, email) VALUES (:nome, :email)');
            $stmt->execute(['nome' => $data['nome'], 'email' => $data['email']]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        try {
            $stmt = $this->db->prepare('UPDATE consultores SET nome = :nome, email = :email, telefone = :telefone, especialidade = :especialidade, senioridade = :senioridade, cidade = :cidade, estado = :estado, observacoes = :observacoes WHERE id = :id');
            return $stmt->execute([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'] ?? null,
                'especialidade' => $data['especialidade'] ?? null,
                'senioridade' => $data['senioridade'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
                'id' => $id,
            ]);
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare('UPDATE consultores SET nome = :nome, email = :email WHERE id = :id');
            return $stmt->execute(['nome' => $data['nome'], 'email' => $data['email'], 'id' => $id]);
        }
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        try {
            $stmt = $this->db->prepare('DELETE FROM consultores WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
