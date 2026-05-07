<?php
namespace App\Models;

use App\Database\Database;

final class ProcessoModel extends BaseModel
{
    private const STATUS_VALUES = ['Planejado', 'Pendente', 'Andamento', 'Adiado', 'Finalizado'];

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS processos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                nome VARCHAR(180) NOT NULL,
                descricao TEXT NULL,
                responsavel VARCHAR(180) NULL,
                data_inicio DATE NOT NULL,
                data_fim DATE NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
                finalizado_em DATETIME NULL,
                finalizado_por_user_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_processos_cliente_data (cliente_id, data_inicio),
                INDEX idx_processos_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('processos', 'finalizado_por_user_id')) {
                $this->db->exec('ALTER TABLE processos ADD COLUMN finalizado_por_user_id INT NULL AFTER finalizado_em');
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
            $where[] = 'p.cliente_id = :cliente_id';
        }
        $where[] = $this->tenantInCondition('p.cliente_id', $params, 'prc');
        $stmt = $this->db->prepare("SELECT p.*, c.nome_empresa AS cliente
            FROM processos p
            JOIN clientes c ON c.id = p.cliente_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.data_inicio DESC, p.id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('p.cliente_id', $params, 'prcf');
        $stmt = $this->db->prepare("SELECT p.*, c.nome_empresa AS cliente
            FROM processos p
            JOIN clientes c ON c.id = p.cliente_id
            WHERE p.id = :id AND $scope
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
        $nome = trim((string)($data['nome'] ?? ''));
        $descricao = trim((string)($data['descricao'] ?? ''));
        $responsavel = trim((string)($data['responsavel'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? '');
        $status = (string)($data['status'] ?? 'Planejado');
        if ($nome === '' || $dataInicio === '') {
            return 0;
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('INSERT INTO processos (cliente_id, nome, descricao, responsavel, data_inicio, data_fim, status) VALUES (:cliente_id, :nome, :descricao, :responsavel, :data_inicio, :data_fim, :status)');
        $stmt->execute([
            'cliente_id' => $clienteId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'responsavel' => $responsavel !== '' ? $responsavel : null,
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
        $nome = trim((string)($data['nome'] ?? $existing['nome'] ?? ''));
        $descricao = trim((string)($data['descricao'] ?? $existing['descricao'] ?? ''));
        $responsavel = trim((string)($data['responsavel'] ?? $existing['responsavel'] ?? ''));
        $dataInicio = (string)($data['data_inicio'] ?? $existing['data_inicio'] ?? '');
        $dataFim = (string)($data['data_fim'] ?? $existing['data_fim'] ?? '');
        $status = (string)($data['status'] ?? $existing['status'] ?? 'Planejado');
        if ($nome === '' || $dataInicio === '') {
            return false;
        }
        if (!in_array($status, self::STATUS_VALUES, true)) {
            $status = 'Planejado';
        }
        $stmt = $this->db->prepare('UPDATE processos SET cliente_id = :cliente_id, nome = :nome, descricao = :descricao, responsavel = :responsavel, data_inicio = :data_inicio, data_fim = :data_fim, status = :status WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'cliente_id' => $clienteId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'responsavel' => $responsavel !== '' ? $responsavel : null,
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
        $stmt = $this->db->prepare('UPDATE processos SET status = :status, finalizado_em = NOW(), finalizado_por_user_id = :user_id WHERE id = :id');
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
        $stmt = $this->db->prepare('DELETE FROM processos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

