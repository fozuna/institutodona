<?php
namespace App\Models;

use RuntimeException;

class CronogramaEventoTipoModel extends BaseModel
{
    public function all(): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, ativo, created_at FROM cronograma_evento_tipos ORDER BY nome');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function allActive(): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT nome FROM cronograma_evento_tipos WHERE ativo = 1 ORDER BY nome');
        $stmt->execute();
        return array_values(array_filter(array_map(static fn($r): string => (string)($r['nome'] ?? ''), $stmt->fetchAll())));
    }

    public function create(string $nome): int
    {
        $this->ensureTable();
        $nome = $this->normalizeNome($nome);
        $stmt = $this->db->prepare('INSERT INTO cronograma_evento_tipos (nome, ativo) VALUES (:nome, 1)');
        $stmt->execute(['nome' => $nome]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $nome): bool
    {
        $this->ensureTable();
        if ($id <= 0) {
            return false;
        }
        $nome = $this->normalizeNome($nome);
        $stmt = $this->db->prepare('UPDATE cronograma_evento_tipos SET nome = :nome WHERE id = :id');
        return $stmt->execute(['nome' => $nome, 'id' => $id]);
    }

    public function setAtivo(int $id, bool $ativo): bool
    {
        $this->ensureTable();
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE cronograma_evento_tipos SET ativo = :ativo WHERE id = :id');
        return $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM cronograma_evento_tipos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function normalizeNome(string $nome): string
    {
        $nome = trim($nome);
        if ($nome === '') {
            throw new RuntimeException('Informe um nome para o tipo de evento.');
        }
        if (mb_strlen($nome) > 50) {
            throw new RuntimeException('Nome do tipo de evento excede o limite de 50 caracteres.');
        }
        return $nome;
    }

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS cronograma_evento_tipos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(50) NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_cronograma_evento_tipos_nome (nome),
                INDEX idx_cronograma_evento_tipos_ativo (ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
        }
    }
}

