<?php
namespace App\Models;

use App\Database\Database;

final class TarefaModel extends BaseModel
{
    private const STATUS_VALUES = ['Planejado', 'Pendente', 'Andamento', 'Adiado', 'Finalizado'];

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS tarefas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                titulo VARCHAR(180) NOT NULL,
                descricao TEXT NULL,
                data_inicio DATETIME NOT NULL,
                data_fim DATETIME NULL,
                prioridade ENUM('baixa','media','alta') NOT NULL DEFAULT 'media',
                status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
                finalizado_em DATETIME NULL,
                finalizado_por_user_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tarefas_cliente_data (cliente_id, data_inicio),
                INDEX idx_tarefas_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('tarefas', 'finalizado_por_user_id')) {
                $this->db->exec('ALTER TABLE tarefas ADD COLUMN finalizado_por_user_id INT NULL AFTER finalizado_em');
            }
        } catch (\PDOException $e) {
        }
    }

    public function all(?int $clienteId = null): array
    {
        $this->ensureTable();
        $params = [];
        $where = [];
        if ($clienteId !== null && $clienteId > 0) {
            $params['cliente_id'] = $this->normalizeScopedClienteId($clienteId);
            $where[] = 't.cliente_id = :cliente_id';
        }
        $scope = $this->tenantInCondition('t.cliente_id', $params, 'tsk');
        $where[] = $scope;
        $sql = "SELECT t.*, c.nome_empresa AS cliente
                FROM tarefas t
                JOIN clientes c ON c.id = t.cliente_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.data_inicio DESC, t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('t.cliente_id', $params, 'tskf');
        $stmt = $this->db->prepare("SELECT t.*, c.nome_empresa AS cliente
            FROM tarefas t
            JOIN clientes c ON c.id = t.cliente_id
            WHERE t.id = :id AND $scope
            LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $clienteId = $this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if (!$this->canAccessClienteId($clienteId)) {
            return 0;
        }
        $titulo = trim((string)($data['titulo'] ?? ''));
        $descricao = trim((string)($data['descricao'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? '');
        $prioridade = (string)($data['prioridade'] ?? 'media');
        $status = (string)($data['status'] ?? 'Planejado');
        if ($titulo === '' || $dataInicio === '') {
            return 0;
        }
        if (!in_array($prioridade, ['baixa', 'media', 'alta'], true)) {
            $prioridade = 'media';
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('INSERT INTO tarefas (cliente_id, titulo, descricao, data_inicio, data_fim, prioridade, status) VALUES (:cliente_id, :titulo, :descricao, :data_inicio, :data_fim, :prioridade, :status)');
        $stmt->execute([
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'descricao' => $descricao !== '' ? $descricao : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'prioridade' => $prioridade,
            'status' => $status,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        $clienteId = $this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : (int)$existing['cliente_id']);
        if (!$this->canAccessClienteId($clienteId)) {
            return false;
        }
        $titulo = trim((string)($data['titulo'] ?? $existing['titulo'] ?? ''));
        $descricao = trim((string)($data['descricao'] ?? $existing['descricao'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? $existing['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? $existing['data_fim'] ?? '');
        $prioridade = (string)($data['prioridade'] ?? $existing['prioridade'] ?? 'media');
        $status = (string)($data['status'] ?? $existing['status'] ?? 'Planejado');
        if ($titulo === '' || $dataInicio === '') {
            return false;
        }
        if (!in_array($prioridade, ['baixa', 'media', 'alta'], true)) {
            $prioridade = 'media';
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('UPDATE tarefas SET cliente_id = :cliente_id, titulo = :titulo, descricao = :descricao, data_inicio = :data_inicio, data_fim = :data_fim, prioridade = :prioridade, status = :status WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'descricao' => $descricao !== '' ? $descricao : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'prioridade' => $prioridade,
            'status' => $status,
        ]);
    }

    public function finalize(int $id, ?int $userId = null): bool
    {
        $this->ensureTable();
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE tarefas SET status = :status, finalizado_em = NOW(), finalizado_por_user_id = :user_id WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'status' => 'Finalizado',
            'user_id' => $userId,
        ]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM tarefas WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

