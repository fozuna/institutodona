<?php
namespace App\Models;

class PlanoAcaoMetricModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS pdca_metrics (
              id INT AUTO_INCREMENT PRIMARY KEY,
              task_id INT NOT NULL,
              nome VARCHAR(120) NOT NULL,
              planejado DECIMAL(12,2) NULL,
              realizado DECIMAL(12,2) NULL,
              unidade VARCHAR(32) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function byTask(int $taskId): array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT * FROM pdca_metrics WHERE task_id = :id');
        $stmt->execute(['id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function upsert(int $taskId, array $metric): bool
    {
        $this->ensure();
        if (!empty($metric['id'])) {
            $stmt = $this->db->prepare('UPDATE pdca_metrics SET nome=:nome, planejado=:planejado, realizado=:realizado, unidade=:unidade WHERE id=:id AND task_id=:task_id');
            return $stmt->execute([
                'nome' => $metric['nome'],
                'planejado' => $metric['planejado'] ?? null,
                'realizado' => $metric['realizado'] ?? null,
                'unidade' => $metric['unidade'] ?? null,
                'id' => $metric['id'],
                'task_id' => $taskId,
            ]);
        }
        $stmt = $this->db->prepare('INSERT INTO pdca_metrics (task_id, nome, planejado, realizado, unidade) VALUES (:task_id, :nome, :planejado, :realizado, :unidade)');
        return $stmt->execute([
            'task_id' => $taskId,
            'nome' => $metric['nome'],
            'planejado' => $metric['planejado'] ?? null,
            'realizado' => $metric['realizado'] ?? null,
            'unidade' => $metric['unidade'] ?? null,
        ]);
    }
}
