<?php
namespace App\Models;

use App\Database\Database;

final class CoachingModel extends BaseModel
{
    private const STATUS_VALUES = ['Planejado', 'Pendente', 'Andamento', 'Adiado', 'Finalizado'];

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS coachings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                titulo VARCHAR(180) NOT NULL,
                coach VARCHAR(180) NULL,
                observacoes TEXT NULL,
                data_inicio DATETIME NOT NULL,
                data_fim DATETIME NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
                finalizado_em DATETIME NULL,
                finalizado_por_user_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_coaching_cliente_data (cliente_id, data_inicio),
                INDEX idx_coaching_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('coachings', 'finalizado_por_user_id')) {
                $this->db->exec('ALTER TABLE coachings ADD COLUMN finalizado_por_user_id INT NULL AFTER finalizado_em');
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
            $where[] = 'cch.cliente_id = :cliente_id';
        }
        $where[] = $this->tenantInCondition('cch.cliente_id', $params, 'cch');
        $stmt = $this->db->prepare("SELECT cch.*, c.nome_empresa AS cliente
            FROM coachings cch
            JOIN clientes c ON c.id = cch.cliente_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY cch.data_inicio DESC, cch.id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cch.cliente_id', $params, 'cchf');
        $stmt = $this->db->prepare("SELECT cch.*, c.nome_empresa AS cliente
            FROM coachings cch
            JOIN clientes c ON c.id = cch.cliente_id
            WHERE cch.id = :id AND $scope
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
        $coach = trim((string)($data['coach'] ?? ''));
        $obs = trim((string)($data['observacoes'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? '');
        $status = (string)($data['status'] ?? 'Planejado');
        if ($titulo === '' || $dataInicio === '') {
            return 0;
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('INSERT INTO coachings (cliente_id, titulo, coach, observacoes, data_inicio, data_fim, status) VALUES (:cliente_id, :titulo, :coach, :observacoes, :data_inicio, :data_fim, :status)');
        $stmt->execute([
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'coach' => $coach !== '' ? $coach : null,
            'observacoes' => $obs !== '' ? $obs : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
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
        $coach = trim((string)($data['coach'] ?? $existing['coach'] ?? ''));
        $obs = trim((string)($data['observacoes'] ?? $existing['observacoes'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? $existing['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? $existing['data_fim'] ?? '');
        $status = (string)($data['status'] ?? $existing['status'] ?? 'Planejado');
        if ($titulo === '' || $dataInicio === '') {
            return false;
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('UPDATE coachings SET cliente_id = :cliente_id, titulo = :titulo, coach = :coach, observacoes = :observacoes, data_inicio = :data_inicio, data_fim = :data_fim, status = :status WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'coach' => $coach !== '' ? $coach : null,
            'observacoes' => $obs !== '' ? $obs : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
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
        $stmt = $this->db->prepare('UPDATE coachings SET status = :status, finalizado_em = NOW(), finalizado_por_user_id = :user_id WHERE id = :id');
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
        $stmt = $this->db->prepare('DELETE FROM coachings WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

