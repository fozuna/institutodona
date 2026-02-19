<?php
namespace App\Models;

class PdcaCheckModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS pdca_checks (
              id INT AUTO_INCREMENT PRIMARY KEY,
              task_id INT NOT NULL,
              gap DECIMAL(12,2) NULL,
              analise TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function byTask(int $taskId): array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT * FROM pdca_checks WHERE task_id = :id ORDER BY created_at DESC');
        $stmt->execute(['id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function add(int $taskId, array $data): int
    {
        $this->ensure();
        $stmt = $this->db->prepare('INSERT INTO pdca_checks (task_id, gap, analise) VALUES (:task_id, :gap, :analise)');
        $stmt->execute([
            'task_id' => $taskId,
            'gap' => $data['gap'] ?? null,
            'analise' => $data['analise'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
