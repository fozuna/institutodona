<?php
namespace App\Models;

class MetodologiaModel extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT m.id, m.id_pilar, m.item_pilar, p.nome AS pilar_nome FROM metodologias m JOIN pilares p ON p.id = m.id_pilar ORDER BY p.nome, m.item_pilar');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, id_pilar, item_pilar FROM metodologias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO metodologias (id_pilar, item_pilar) VALUES (:id_pilar, :item_pilar)');
        $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE metodologias SET id_pilar = :id_pilar, item_pilar = :item_pilar WHERE id = :id');
        return $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM metodologias WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}