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
              meta_valor DECIMAL(12,2) NULL,
              meta_unidade VARCHAR(32) NULL,
              prazo DATE NULL,
              responsavel VARCHAR(120) NULL,
              fase ENUM('PLAN','DO','CHECK','ACT') NOT NULL DEFAULT 'PLAN',
              status ENUM('A Fazer','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'A Fazer',
              progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) { }
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
