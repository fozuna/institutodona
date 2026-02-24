<?php
namespace App\Models;

class PlanoAcaoHistoryModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS planoacao_history (
              id INT AUTO_INCREMENT PRIMARY KEY,
              item_type ENUM('task','action') NOT NULL,
              item_id INT NOT NULL,
              user_id INT NULL,
              action_type ENUM('create','update','delete') NOT NULL,
              changes_json TEXT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function log(string $type, int $itemId, string $action, array $changes = [], ?int $userId = null): void
    {
        $this->ensure();
        if ($userId === null) {
            $userId = $_SESSION['user']['id'] ?? null;
        }
        
        $stmt = $this->db->prepare('INSERT INTO planoacao_history (item_type, item_id, user_id, action_type, changes_json) VALUES (:type, :id, :uid, :act, :chg)');
        $stmt->execute([
            'type' => $type,
            'id' => $itemId,
            'uid' => $userId,
            'act' => $action,
            'chg' => json_encode($changes, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function getByTask(int $taskId, array $filters = []): array
    {
        $this->ensure();
        $sql = "
            SELECT h.*, u.nome as user_name, u.email as user_email
            FROM planoacao_history h
            LEFT JOIN usuarios u ON h.user_id = u.id
            WHERE ((h.item_type = 'task' AND h.item_id = :tid)
               OR (h.item_type = 'action' AND h.item_id IN (SELECT id FROM pdca_actions WHERE task_id = :tid2)))
        ";
        $params = ['tid' => $taskId, 'tid2' => $taskId];

        if (!empty($filters['user_id'])) {
            $sql .= " AND h.user_id = :uid";
            $params['uid'] = $filters['user_id'];
        }
        if (!empty($filters['action_type'])) {
            $sql .= " AND h.action_type = :act";
            $params['act'] = $filters['action_type'];
        }
        if (!empty($filters['date_start'])) {
            $sql .= " AND DATE(h.created_at) >= :dstart";
            $params['dstart'] = $filters['date_start'];
        }
        if (!empty($filters['date_end'])) {
            $sql .= " AND DATE(h.created_at) <= :dend";
            $params['dend'] = $filters['date_end'];
        }

        $sql .= " ORDER BY h.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
