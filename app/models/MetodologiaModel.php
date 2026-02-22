<?php
namespace App\Models;

class MetodologiaModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            if (!\App\Database\Database::tableExists('metodologias')) {
                $this->db->exec('CREATE TABLE IF NOT EXISTS metodologias (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_pilar INT NOT NULL,
                    item_pilar VARCHAR(255) NOT NULL,
                    CONSTRAINT fk_met_pilar FOREIGN KEY (id_pilar) REFERENCES pilares(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            }
        } catch (\PDOException $e) {}
    }
    private function ensureColumns(): void
    {
        try {
            $this->ensureTable();
            if (!\App\Database\Database::columnExists('metodologias', 'tipo')) {
                $this->db->exec("ALTER TABLE metodologias ADD COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'tarefa'");
            }
            if (!\App\Database\Database::columnExists('metodologias', 'arquivo_path')) {
                $this->db->exec('ALTER TABLE metodologias ADD COLUMN arquivo_path VARCHAR(255) NULL');
            }
            if (!\App\Database\Database::columnExists('metodologias', 'observacoes')) {
                $this->db->exec('ALTER TABLE metodologias ADD COLUMN observacoes TEXT NULL');
            }
            if (!\App\Database\Database::columnExists('metodologias', 'cliente_id')) {
                $this->db->exec('ALTER TABLE metodologias ADD COLUMN cliente_id INT NULL');
            }
        } catch (\PDOException $e) {
        }
    }

    public function all(): array
    {
        $this->ensureColumns();
        $stmt = $this->db->query('SELECT m.id, m.id_pilar, m.item_pilar, m.tipo, m.arquivo_path, m.observacoes, m.cliente_id, p.nome AS pilar_nome FROM metodologias m JOIN pilares p ON p.id = m.id_pilar ORDER BY p.nome, m.item_pilar');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('SELECT id, id_pilar, item_pilar, tipo, arquivo_path, observacoes, cliente_id FROM metodologias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('INSERT INTO metodologias (id_pilar, item_pilar, tipo, arquivo_path, observacoes, cliente_id) VALUES (:id_pilar, :item_pilar, :tipo, :arquivo_path, :observacoes, :cliente_id)');
        $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
            'tipo' => $data['tipo'] ?? 'tarefa',
            'arquivo_path' => $data['arquivo_path'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'cliente_id' => $data['cliente_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('UPDATE metodologias SET id_pilar = :id_pilar, item_pilar = :item_pilar, tipo = :tipo, arquivo_path = :arquivo_path, observacoes = :observacoes, cliente_id = :cliente_id WHERE id = :id');
        return $stmt->execute([
            'id_pilar' => $data['id_pilar'],
            'item_pilar' => $data['item_pilar'],
            'tipo' => $data['tipo'] ?? 'tarefa',
            'arquivo_path' => $data['arquivo_path'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'cliente_id' => $data['cliente_id'] ?? null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM metodologias WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function byCliente(int $clienteId): array
    {
        $this->ensureColumns();
        $stmt = $this->db->prepare('SELECT m.id, m.id_pilar, m.item_pilar, m.tipo, m.arquivo_path, m.observacoes, m.cliente_id, p.nome AS pilar_nome FROM metodologias m JOIN pilares p ON p.id = m.id_pilar WHERE m.cliente_id = :cid ORDER BY p.nome, m.item_pilar');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }
}
