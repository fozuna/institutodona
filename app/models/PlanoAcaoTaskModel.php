<?php
namespace App\Models;

class PlanoAcaoTaskModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS pdca_tasks (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_cliente INT NOT NULL,
              titulo VARCHAR(255) NOT NULL,
              descricao TEXT NULL,
              meta_valor TEXT NULL,
              meta_unidade VARCHAR(255) NULL,
              prazo DATE NULL,
              responsavel VARCHAR(120) NULL,
              fase ENUM('PLAN','DO','CHECK','ACT') NOT NULL DEFAULT 'PLAN',
              status ENUM('A Fazer','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'A Fazer',
              progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Migrations for existing tables
            // Check and modify prazo back to DATE if it was changed to VARCHAR
            try {
                // First try to clean up any invalid date strings if possible or just force conversion (invalid dates become NULL or 0000-00-00)
                $this->db->exec("UPDATE pdca_tasks SET prazo = NULL WHERE STR_TO_DATE(prazo, '%Y-%m-%d') IS NULL");
                $this->db->exec("ALTER TABLE pdca_tasks MODIFY COLUMN prazo DATE NULL");
                // Keep other text fields as TEXT/VARCHAR
                $this->db->exec("ALTER TABLE pdca_tasks MODIFY COLUMN meta_valor TEXT NULL");
                $this->db->exec("ALTER TABLE pdca_tasks MODIFY COLUMN meta_unidade VARCHAR(255) NULL");
            } catch (\Exception $e) {
                // Ignore if already altered
            }

        } catch (\PDOException $e) { }
    }

    /**
     * Calculates the traffic light status for a deadline.
     * 
     * @param string|null $prazo YYYY-MM-DD
     * @param string $status Current task status
     * @return string 'red'|'yellow'|'green'|'gray'
     */
    public function getPrazoStatus(?string $prazo, string $status = ''): string
    {
        if ($status === 'Concluído') {
            return 'gray'; // Completed tasks don't need traffic light or can be gray/green
        }
        
        if (!$prazo || $prazo === '0000-00-00') {
            return 'gray'; // No deadline
        }

        try {
            $deadline = new \DateTime($prazo);
            $today = new \DateTime('today'); // midnight
            
            if ($deadline < $today) {
                return 'red'; // Overdue
            }
            
            $diff = $today->diff($deadline);
            $days = (int)$diff->format('%a'); // Total days
            
            if ($days <= 2) {
                return 'yellow'; // 2 days or less remaining (including today as 0 days diff if same day, but logic above covers past)
            }
            
            return 'green'; // More than 2 days
        } catch (\Exception $e) {
            return 'gray';
        }
    }

    public function create(array $data): int
    {
        $this->ensure();
        $stmt = $this->db->prepare('INSERT INTO pdca_tasks (id_cliente, titulo, descricao, meta_valor, meta_unidade, prazo, responsavel, fase, status, progresso) VALUES (:id_cliente, :titulo, :descricao, :meta_valor, :meta_unidade, :prazo, :responsavel, :fase, :status, :progresso)');
        $stmt->execute([
            'id_cliente' => $data['id_cliente'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'meta_valor' => $data['meta_valor'] ?? null,
            'meta_unidade' => $data['meta_unidade'] ?? null,
            'prazo' => $data['prazo'] ?? null,
            'responsavel' => $data['responsavel'] ?? null,
            'fase' => 'DO', // Force execution phase
            'status' => $data['status'] ?? 'A Fazer',
            'progresso' => (int)($data['progresso'] ?? 0),
        ]);
        $id = (int)$this->db->lastInsertId();
        
        // Log creation
        (new PlanoAcaoHistoryModel())->log('task', $id, 'create', $data);
        
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensure();
        $old = $this->find($id);
        if (!$old) return false;

        $stmt = $this->db->prepare('UPDATE pdca_tasks SET titulo=:titulo, descricao=:descricao, meta_valor=:meta_valor, meta_unidade=:meta_unidade, prazo=:prazo, responsavel=:responsavel, status=:status, progresso=:progresso WHERE id=:id');
        $res = $stmt->execute([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'meta_valor' => $data['meta_valor'] ?? null,
            'meta_unidade' => $data['meta_unidade'] ?? null,
            'prazo' => $data['prazo'] ?? null,
            'responsavel' => $data['responsavel'] ?? null,
            'status' => $data['status'] ?? 'A Fazer',
            'progresso' => (int)($data['progresso'] ?? 0),
            'id' => $id,
        ]);

        if ($res) {
            $changes = [];
            foreach (['titulo', 'descricao', 'meta_valor', 'meta_unidade', 'prazo', 'responsavel', 'status', 'progresso'] as $f) {
                $vOld = $old[$f] ?? null;
                $vNew = $data[$f] ?? null;
                if ($vOld != $vNew) {
                    $changes[$f] = ['old' => $vOld, 'new' => $vNew];
                }
            }
            if (!empty($changes)) {
                (new PlanoAcaoHistoryModel())->log('task', $id, 'update', $changes);
            }
        }
        return $res;
    }

    public function byCliente(int $idCliente): array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT * FROM pdca_tasks WHERE id_cliente = :id ORDER BY prazo IS NULL, prazo, created_at DESC');
        $stmt->execute(['id' => $idCliente]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT * FROM pdca_tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        $this->ensure();
        $stmt = $this->db->prepare('DELETE FROM pdca_tasks WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
