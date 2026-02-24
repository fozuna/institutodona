<?php
namespace App\Models;

class PlanoAcaoActionModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS pdca_actions (
              id INT AUTO_INCREMENT PRIMARY KEY,
              task_id INT NOT NULL,
              titulo VARCHAR(255) NOT NULL,
              owner VARCHAR(120) NULL,
              due_date DATE NULL,
              status ENUM('Planejado','Em Execução','Concluído') NOT NULL DEFAULT 'Planejado'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function byTask(int $taskId, ?string $status = null): array
    {
        $this->ensure();
        $sql = 'SELECT * FROM pdca_actions WHERE task_id = :id';
        $params = ['id' => $taskId];
        if ($status) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY due_date IS NULL, due_date';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(int $taskId, array $data): int
    {
        $this->ensure();
        $stmt = $this->db->prepare('INSERT INTO pdca_actions (task_id, titulo, owner, due_date, status) VALUES (:task_id, :titulo, :owner, :due_date, :status)');
        $stmt->execute([
            'task_id' => $taskId,
            'titulo' => $data['titulo'],
            'owner' => $data['owner'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'Planejado',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensure();
        $stmt = $this->db->prepare('UPDATE pdca_actions SET titulo=:titulo, owner=:owner, due_date=:due_date, status=:status WHERE id=:id');
        return $stmt->execute([
            'titulo' => $data['titulo'],
            'owner' => $data['owner'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'Planejado',
            'id' => $id,
        ]);
    }
}
