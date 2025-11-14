<?php
namespace App\Models;

class PilarModel extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, nome FROM pilares ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome FROM pilares WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nome): int
    {
        $stmt = $this->db->prepare('INSERT INTO pilares (nome) VALUES (:nome)');
        $stmt->execute(['nome' => $nome]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $nome): bool
    {
        $stmt = $this->db->prepare('UPDATE pilares SET nome = :nome WHERE id = :id');
        return $stmt->execute(['nome' => $nome, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pilares WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}