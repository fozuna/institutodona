<?php
namespace App\Models;

class MetodologiaModel extends BaseModel
{
    private function ensureColumns(): void
    {
        try {
            if (!\App\Database\Database::columnExists('metodologias', 'tipo')) {
                $this->db->exec("ALTER TABLE metodologias ADD COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'tarefa'");
            }
            if (!\App\Database\Database::columnExists('metodologias', 'arquivo_path')) {
                $this->db->exec('ALTER TABLE metodologias ADD COLUMN arquivo_path VARCHAR(255) NULL');
            }
            if (!\App\Database\Database::columnExists('metodologias', 'observacoes')) {
                $this->db->exec('ALTER TABLE metodologias ADD COLUMN observacoes TEXT NULL');
            }
        } catch (\PDOException $e) {
        }
    }

    public function all(): array
    {
        $this->ensureColumns();
        $stmt = $this->db->query('SELECT m.id, m.id_pilar, m.item_pilar, m.tipo, m.arquivo_path, m.observacoes, p.nome AS pilar_nome FROM metodologias m JOIN pilares p ON p.id = m.id_pilar ORDER BY p.nome, m.item_pilar');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('SELECT id, id_pilar, item_pilar, tipo, arquivo_path, observacoes FROM metodologias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('INSERT INTO metodologias (id_pilar, item_pilar, tipo, arquivo_path, observacoes) VALUES (:id_pilar, :item_pilar, :tipo, :arquivo_path, :observacoes)');
        $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
            'tipo' => $data['tipo'] ?? 'tarefa',
            'arquivo_path' => $data['arquivo_path'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('UPDATE metodologias SET id_pilar = :id_pilar, item_pilar = :item_pilar, tipo = :tipo, arquivo_path = :arquivo_path, observacoes = :observacoes WHERE id = :id');
        return $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
            'tipo' => $data['tipo'] ?? 'tarefa',
            'arquivo_path' => $data['arquivo_path'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM metodologias WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
