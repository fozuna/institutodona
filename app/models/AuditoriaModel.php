<?php
namespace App\Models;

use App\Core\Auth;

class AuditoriaModel extends BaseModel
{
    private function ensureTables(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                setor_id INT NOT NULL,
                responsavel_id INT NOT NULL,
                data_auditoria DATE NOT NULL,
                pergunta VARCHAR(500) NOT NULL,
                objetivo TEXT NOT NULL,
                referencia_esperada VARCHAR(255) NOT NULL,
                status ENUM('Agendada','Realizada') NOT NULL DEFAULT 'Agendada',
                avaliacao TEXT NULL,
                obs TEXT NULL,
                realizada_at DATETIME NULL,
                created_by INT NULL,
                updated_by INT NULL,
                deleted_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_auditorias_cliente (cliente_id),
                INDEX idx_auditorias_setor (setor_id),
                INDEX idx_auditorias_responsavel (responsavel_id),
                INDEX idx_auditorias_data (data_auditoria)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_relatorios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                relatorio_ref VARCHAR(120) NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_auditoria_relatorios_auditoria (auditoria_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
        }
    }

    private function hasScopeRestriction(): bool
    {
        return !$this->canBypassScope() && count($this->tenantClientIds()) > 0;
    }

    private function canBypassScope(): bool
    {
        return Auth::isInstituto() || Auth::isConsultor();
    }

    public function list(array $filters, int $page, int $per): array
    {
        $this->ensureTables();
        if (!$this->canBypassScope() && count($this->tenantClientIds()) === 0) {
            return ['items' => [], 'total' => 0];
        }
        $offset = max(0, ($page - 1) * $per);
        $where = ['a.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['cliente'])) {
            $where[] = 'a.cliente_id = :cliente';
            $params['cliente'] = (int)$filters['cliente'];
        }
        if (!empty($filters['setor'])) {
            $where[] = 'a.setor_id = :setor';
            $params['setor'] = (int)$filters['setor'];
        }
        if (!empty($filters['responsavel'])) {
            $where[] = 'a.responsavel_id = :responsavel';
            $params['responsavel'] = (int)$filters['responsavel'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = (string)$filters['status'];
        }
        if (!empty($filters['inicio'])) {
            $where[] = 'a.data_auditoria >= :inicio';
            $params['inicio'] = (string)$filters['inicio'];
        }
        if (!empty($filters['fim'])) {
            $where[] = 'a.data_auditoria <= :fim';
            $params['fim'] = (string)$filters['fim'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(a.pergunta LIKE :q OR a.objetivo LIKE :q OR c.nome_empresa LIKE :q OR s.nome LIKE :q OR r.nome LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }

        if ($this->hasScopeRestriction()) {
            $where[] = $this->tenantInCondition('a.cliente_id', $params, 'audsc');
        }

        $sort = (string)($filters['sort'] ?? 'data_desc');
        $orderMap = [
            'data_desc' => 'a.data_auditoria DESC, a.id DESC',
            'data_asc' => 'a.data_auditoria ASC, a.id ASC',
            'status' => 'a.status ASC, a.data_auditoria DESC',
            'empresa' => 'c.nome_empresa ASC, a.data_auditoria DESC',
            'setor' => 's.nome ASC, a.data_auditoria DESC',
            'responsavel' => 'r.nome ASC, a.data_auditoria DESC',
        ];
        $order = $orderMap[$sort] ?? $orderMap['data_desc'];
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM auditorias a
            JOIN clientes c ON c.id = a.cliente_id
            JOIN setores s ON s.id = a.setor_id
            JOIN colaboradores r ON r.id = a.responsavel_id
            WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome, r.nome AS responsavel_nome
                FROM auditorias a
                JOIN clientes c ON c.id = a.cliente_id
                JOIN setores s ON s.id = a.setor_id
                JOIN colaboradores r ON r.id = a.responsavel_id
                WHERE $whereSql
                ORDER BY $order
                LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $per, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public function find(int $id): ?array
    {
        $this->ensureTables();
        if (!$this->canBypassScope() && count($this->tenantClientIds()) === 0) {
            return null;
        }
        $params = ['id' => $id];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('a.cliente_id', $params, 'audf')) : '';
        $stmt = $this->db->prepare("SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome, r.nome AS responsavel_nome
                                    FROM auditorias a
                                    JOIN clientes c ON c.id = a.cliente_id
                                    JOIN setores s ON s.id = a.setor_id
                                    JOIN colaboradores r ON r.id = a.responsavel_id
                                    WHERE a.id = :id AND a.deleted_at IS NULL$scope
                                    LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $this->ensureTables();
        $clienteId = (int)$data['cliente_id'];
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            return 0;
        }
        $stmt = $this->db->prepare("INSERT INTO auditorias
            (cliente_id, setor_id, responsavel_id, data_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
            VALUES
            (:cliente_id, :setor_id, :responsavel_id, :data_auditoria, :pergunta, :objetivo, :referencia_esperada, 'Agendada', :created_by, :updated_by)");
        $stmt->execute([
            'cliente_id' => $clienteId,
            'setor_id' => (int)$data['setor_id'],
            'responsavel_id' => (int)$data['responsavel_id'],
            'data_auditoria' => $data['data_auditoria'],
            'pergunta' => $data['pergunta'],
            'objetivo' => $data['objetivo'],
            'referencia_esperada' => $data['referencia_esperada'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateAgendada(int $id, array $data, int $userId): bool
    {
        $this->ensureTables();
        $clienteId = (int)$data['cliente_id'];
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            return false;
        }
        $params = [
            'id' => $id,
            'cliente_id' => $clienteId,
            'setor_id' => (int)$data['setor_id'],
            'responsavel_id' => (int)$data['responsavel_id'],
            'data_auditoria' => $data['data_auditoria'],
            'pergunta' => $data['pergunta'],
            'objetivo' => $data['objetivo'],
            'referencia_esperada' => $data['referencia_esperada'],
            'updated_by' => $userId,
        ];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audu')) : '';
        $stmt = $this->db->prepare("UPDATE auditorias
            SET cliente_id = :cliente_id, setor_id = :setor_id, responsavel_id = :responsavel_id, data_auditoria = :data_auditoria,
                pergunta = :pergunta, objetivo = :objetivo, referencia_esperada = :referencia_esperada, updated_by = :updated_by
            WHERE id = :id AND deleted_at IS NULL AND status = 'Agendada' AND realizada_at IS NULL$scope");
        return $stmt->execute($params) && $stmt->rowCount() > 0;
    }

    public function auditar(int $id, array $data, int $userId): bool
    {
        $this->ensureTables();
        $params = [
            'id' => $id,
            'avaliacao' => $data['avaliacao'],
            'obs' => $data['obs'] ?: null,
            'updated_by' => $userId,
        ];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audx')) : '';
        $stmt = $this->db->prepare("UPDATE auditorias
            SET avaliacao = :avaliacao, obs = :obs, status = 'Realizada', realizada_at = NOW(), updated_by = :updated_by
            WHERE id = :id AND deleted_at IS NULL AND status = 'Agendada' AND realizada_at IS NULL$scope");
        return $stmt->execute($params) && $stmt->rowCount() > 0;
    }

    public function countRelatoriosVinculados(int $id): int
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM auditoria_relatorios WHERE auditoria_id = :id AND ativo = 1');
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public function softDelete(int $id, int $userId): bool
    {
        $this->ensureTables();
        if ($this->countRelatoriosVinculados($id) > 0) {
            return false;
        }
        $params = ['id' => $id, 'deleted_by' => $userId];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audd')) : '';
        $stmt = $this->db->prepare("UPDATE auditorias SET deleted_at = NOW(), deleted_by = :deleted_by WHERE id = :id AND deleted_at IS NULL$scope");
        return $stmt->execute($params) && $stmt->rowCount() > 0;
    }
}
