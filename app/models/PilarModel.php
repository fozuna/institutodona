<?php
namespace App\Models;

class PilarModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS pilares (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(80) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $stmt = $this->db->query('SELECT COUNT(*) FROM pilares');
            if ((int)$stmt->fetchColumn() === 0) {
                $ins = $this->db->prepare('INSERT INTO pilares (nome) VALUES (?), (?), (?), (?)');
                $ins->execute(['Processos','Gestão','Pessoas','Trilha Capacitação']);
            }
        } catch (\PDOException $e) {}
    }
    public function all(): array
    {
        $this->ensureTable();
        $stmt = $this->db->query('SELECT id, nome FROM pilares ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome FROM pilares WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nome): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO pilares (nome) VALUES (:nome)');
        $stmt->execute(['nome' => $nome]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $nome): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('UPDATE pilares SET nome = :nome WHERE id = :id');
        return $stmt->execute(['nome' => $nome, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM pilares WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
