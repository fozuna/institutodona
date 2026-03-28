<?php
namespace App\Models;

class AuditoriaArquivoModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_arquivos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                questao_id INT NOT NULL,
                path VARCHAR(255) NOT NULL,
                compressed_path VARCHAR(255) NULL,
                original_name VARCHAR(255) NOT NULL,
                mime VARCHAR(100) NULL,
                size INT NOT NULL,
                sha256 CHAR(64) NOT NULL,
                thumb_path VARCHAR(255) NULL,
                created_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_aud_arquivos_auditoria (auditoria_id),
                INDEX idx_aud_arquivos_questao (questao_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function listByQuestao(int $auditoriaId, int $questaoId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM auditoria_arquivos WHERE auditoria_id = :a AND questao_id = :q ORDER BY id DESC');
        $stmt->execute(['a' => $auditoriaId, 'q' => $questaoId]);
        return $stmt->fetchAll();
    }

    public function totalBytesByQuestao(int $auditoriaId, int $questaoId): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(size),0) FROM auditoria_arquivos WHERE auditoria_id = :a AND questao_id = :q');
        $stmt->execute(['a' => $auditoriaId, 'q' => $questaoId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO auditoria_arquivos (auditoria_id, questao_id, path, compressed_path, original_name, mime, size, sha256, thumb_path, created_by)
            VALUES (:auditoria_id, :questao_id, :path, :compressed_path, :original_name, :mime, :size, :sha256, :thumb_path, :created_by)');
        $stmt->execute([
            'auditoria_id' => (int)$data['auditoria_id'],
            'questao_id' => (int)$data['questao_id'],
            'path' => (string)$data['path'],
            'compressed_path' => $data['compressed_path'] ?? null,
            'original_name' => (string)$data['original_name'],
            'mime' => $data['mime'] ?? null,
            'size' => (int)$data['size'],
            'sha256' => (string)$data['sha256'],
            'thumb_path' => $data['thumb_path'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM auditoria_arquivos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
