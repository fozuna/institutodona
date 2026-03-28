<?php
namespace App\Models;

use App\Core\Auth;

class AuditoriaModel extends BaseModel
{
    private static bool $tablesEnsured = false;

    private function ensureTables(): void
    {
        if (self::$tablesEnsured) {
            return;
        }
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                setor_id INT NOT NULL,
                responsavel_id INT NULL,
                data_auditoria DATE NOT NULL,
                nome_auditoria VARCHAR(180) NOT NULL DEFAULT '',
                pergunta VARCHAR(500) NOT NULL,
                objetivo TEXT NOT NULL,
                referencia_esperada VARCHAR(255) NOT NULL,
                status ENUM('Agendada','Em Auditoria','Realizada') NOT NULL DEFAULT 'Agendada',
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
                INDEX idx_auditorias_nome (nome_auditoria),
                INDEX idx_auditorias_responsavel (responsavel_id),
                INDEX idx_auditorias_data (data_auditoria)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!\App\Database\Database::columnExists('auditorias', 'nome_auditoria')) {
                $this->db->exec("ALTER TABLE auditorias ADD COLUMN nome_auditoria VARCHAR(180) NOT NULL DEFAULT '' AFTER data_auditoria");
            }
            try {
                $this->db->exec("ALTER TABLE auditorias MODIFY COLUMN responsavel_id INT NULL");
            } catch (\PDOException $e) {
            }
            try {
                $this->db->exec("ALTER TABLE auditorias MODIFY COLUMN status ENUM('Agendada','Em Auditoria','Realizada') NOT NULL DEFAULT 'Agendada'");
            } catch (\PDOException $e) {
            }
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_relatorios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                relatorio_ref VARCHAR(120) NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_auditoria_relatorios_auditoria (auditoria_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_questoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                responsavel_nome VARCHAR(180) NOT NULL,
                pergunta TEXT NOT NULL,
                referencia_esperada TEXT NOT NULL,
                processos_json TEXT NULL,
                ordem INT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_auditoria_questoes_auditoria (auditoria_id),
                CONSTRAINT fk_auditoria_questoes_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_avaliacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                questao_id INT NOT NULL,
                conformidade ENUM('pendente','conforme','nao_conforme','nao_aplica') NOT NULL DEFAULT 'pendente',
                observacoes TEXT NULL,
                auto_saved_at DATETIME NULL,
                finalized_at DATETIME NULL,
                updated_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_auditoria_questao (auditoria_id, questao_id),
                INDEX idx_auditoria_avaliacoes_auditoria (auditoria_id),
                CONSTRAINT fk_auditoria_avaliacoes_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
                CONSTRAINT fk_auditoria_avaliacoes_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            try {
                $this->db->exec("ALTER TABLE auditoria_avaliacoes MODIFY COLUMN conformidade ENUM('pendente','conforme','nao_conforme','nao_aplica') NOT NULL DEFAULT 'pendente'");
            } catch (\PDOException $e) {}
            if (!\App\Database\Database::columnExists('auditorias', 'conformidade_pct')) {
                $this->db->exec("ALTER TABLE auditorias ADD COLUMN conformidade_pct DECIMAL(5,2) NULL AFTER avaliacao");
            }
            if (!\App\Database\Database::columnExists('auditorias', 'semaforo')) {
                $this->db->exec("ALTER TABLE auditorias ADD COLUMN semaforo ENUM('vermelho','amarelo','verde') NULL AFTER conformidade_pct");
            }
            self::$tablesEnsured = true;
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
            $where[] = '(a.nome_auditoria LIKE :q OR a.pergunta LIKE :q OR c.nome_empresa LIKE :q OR s.nome LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }

        if ($this->hasScopeRestriction()) {
            $where[] = $this->tenantInCondition('a.cliente_id', $params, 'audsc');
        }

        $sortCol = (string)($filters['sort_col'] ?? 'data');
        $sortDir = strtolower((string)($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $columnMap = [
            'nome' => 'a.nome_auditoria',
            'setor' => 's.nome',
            'data' => 'a.data_auditoria',
            'status' => 'a.status',
            'empresa' => 'c.nome_empresa',
        ];
        $order = ($columnMap[$sortCol] ?? $columnMap['data']) . " {$sortDir}, a.id DESC";
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM auditorias a
            JOIN clientes c ON c.id = a.cliente_id
            JOIN setores s ON s.id = a.setor_id
            WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome,
                       (SELECT COUNT(*) FROM auditoria_questoes q WHERE q.auditoria_id = a.id) AS total_questoes
                FROM auditorias a
                JOIN clientes c ON c.id = a.cliente_id
                JOIN setores s ON s.id = a.setor_id
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
        $stmt = $this->db->prepare("SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome
                                    FROM auditorias a
                                    JOIN clientes c ON c.id = a.cliente_id
                                    JOIN setores s ON s.id = a.setor_id
                                    WHERE a.id = :id AND a.deleted_at IS NULL$scope
                                    LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findWithQuestoes(int $id): ?array
    {
        $item = $this->find($id);
        if (!$item) {
            return null;
        }
        $item['questoes'] = $this->questoesByAuditoria((int)$item['id']);
        return $item;
    }

    public function questoesByAuditoria(int $auditoriaId): array
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('SELECT id, responsavel_nome, pergunta, referencia_esperada, processos_json, ordem FROM auditoria_questoes WHERE auditoria_id = :id ORDER BY ordem, id');
        $stmt->execute(['id' => $auditoriaId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $decoded = json_decode((string)($row['processos_json'] ?? ''), true);
            $row['processos'] = is_array($decoded) ? $decoded : [];
        }
        return $rows;
    }

    public function questaoPertence(int $auditoriaId, int $questaoId): bool
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('SELECT id FROM auditoria_questoes WHERE id = :qid AND auditoria_id = :aid LIMIT 1');
        $stmt->execute(['qid' => $questaoId, 'aid' => $auditoriaId]);
        return (bool)$stmt->fetchColumn();
    }

    public function iniciarExecucao(int $auditoriaId, int $userId): void
    {
        $this->ensureTables();
        $params = ['id' => $auditoriaId, 'updated_by' => $userId];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audstart')) : '';
        try {
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by WHERE id = :id AND deleted_at IS NULL AND status = 'Agendada'$scope")
                ->execute($params);
        } catch (\PDOException $e) {
        }
    }

    public function create(array $data, int $userId): int
    {
        $this->ensureTables();
        $clienteId = (int)$data['cliente_id'];
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            return 0;
        }
        $this->db->beginTransaction();
        try {
            $primeira = $data['questoes'][0] ?? ['pergunta' => '', 'referencia_esperada' => '', 'responsavel_nome' => ''];
            $stmt = $this->db->prepare("INSERT INTO auditorias
                (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
                VALUES
                (:cliente_id, :setor_id, NULL, :data_auditoria, :nome_auditoria, :pergunta, :objetivo, :referencia_esperada, 'Agendada', :created_by, :updated_by)");
            $stmt->execute([
                'cliente_id' => $clienteId,
                'setor_id' => (int)$data['setor_id'],
                'data_auditoria' => (string)$data['data_auditoria'],
                'nome_auditoria' => (string)$data['nome_auditoria'],
                'pergunta' => (string)$primeira['pergunta'],
                'objetivo' => (string)$primeira['responsavel_nome'],
                'referencia_esperada' => (string)$primeira['referencia_esperada'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $id = (int)$this->db->lastInsertId();
            $this->persistQuestoes($id, $data['questoes']);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return 0;
        }
    }

    public function updateAgendada(int $id, array $data, int $userId): bool
    {
        $this->ensureTables();
        $clienteId = (int)$data['cliente_id'];
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $primeira = $data['questoes'][0] ?? ['pergunta' => '', 'referencia_esperada' => '', 'responsavel_nome' => ''];
            $params = [
            'id' => $id,
            'cliente_id' => $clienteId,
            'setor_id' => (int)$data['setor_id'],
            'data_auditoria' => $data['data_auditoria'],
            'nome_auditoria' => (string)$data['nome_auditoria'],
            'pergunta' => (string)$primeira['pergunta'],
            'objetivo' => (string)$primeira['responsavel_nome'],
            'referencia_esperada' => (string)$primeira['referencia_esperada'],
            'updated_by' => $userId,
            ];
            $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audu')) : '';
            $stmt = $this->db->prepare("UPDATE auditorias
                SET cliente_id = :cliente_id, setor_id = :setor_id, data_auditoria = :data_auditoria, nome_auditoria = :nome_auditoria,
                    pergunta = :pergunta, objetivo = :objetivo, referencia_esperada = :referencia_esperada, updated_by = :updated_by
                WHERE id = :id AND deleted_at IS NULL AND status IN ('Agendada','Em Auditoria') AND realizada_at IS NULL$scope");
            $updated = $stmt->execute($params) && $stmt->rowCount() > 0;
            if (!$updated) {
                $this->db->rollBack();
                return false;
            }
            $this->db->prepare('DELETE FROM auditoria_questoes WHERE auditoria_id = :id')->execute(['id' => $id]);
            $this->persistQuestoes($id, $data['questoes']);
            $this->db->prepare('DELETE FROM auditoria_avaliacoes WHERE auditoria_id = :id AND finalized_at IS NULL')->execute(['id' => $id]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function autosaveAvaliacoes(int $auditoriaId, array $avaliacoes, int $userId): bool
    {
        $this->ensureTables();
        $auditoria = $this->find($auditoriaId);
        if (!$auditoria || !in_array((string)$auditoria['status'], ['Agendada', 'Em Auditoria'], true)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $this->persistAvaliacoesNoTx($auditoriaId, $avaliacoes, $userId);
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by WHERE id = :id AND status = 'Agendada'")
                ->execute(['id' => $auditoriaId, 'updated_by' => $userId]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function finalizarAuditoria(int $auditoriaId, array $avaliacoes, int $userId): bool
    {
        $this->ensureTables();
        $auditoria = $this->find($auditoriaId);
        if (!$auditoria || !in_array((string)$auditoria['status'], ['Agendada', 'Em Auditoria'], true)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $this->persistAvaliacoesNoTx($auditoriaId, $avaliacoes, $userId);
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by WHERE id = :id AND status = 'Agendada'")
                ->execute(['id' => $auditoriaId, 'updated_by' => $userId]);
            $this->db->prepare('UPDATE auditoria_avaliacoes SET finalized_at = NOW() WHERE auditoria_id = :id')
                ->execute(['id' => $auditoriaId]);
            $stats = $this->computeConformidadeStats($auditoriaId);
            $resumo = 'Conforme: ' . $stats['conforme'] . ' | Não conforme: ' . $stats['nao_conforme'] . ' | N/A: ' . $stats['nao_aplica'];
            $up = $this->db->prepare("UPDATE auditorias
                SET status = 'Realizada', realizada_at = NOW(), avaliacao = :avaliacao, conformidade_pct = :pct, semaforo = :sem, updated_by = :updated_by
                WHERE id = :id AND deleted_at IS NULL AND realizada_at IS NULL");
            $ok = $up->execute([
                'id' => $auditoriaId,
                'avaliacao' => $resumo,
                'pct' => $stats['pct'],
                'sem' => $stats['semaforo'],
                'updated_by' => $userId
            ]) && $up->rowCount() > 0;
            if (!$ok) {
                $this->db->rollBack();
                return false;
            }
            try {
                (new SetorMetricaModel())->registrarConclusao((int)$auditoria['setor_id'], $stats);
            } catch (\Throwable $e) {
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function respostasByAuditoria(int $auditoriaId): array
    {
        $this->ensureTables();
        $stmt = $this->db->prepare("SELECT questao_id, conformidade, observacoes, auto_saved_at, finalized_at
                                    FROM auditoria_avaliacoes
                                    WHERE auditoria_id = :id");
        $stmt->execute(['id' => $auditoriaId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int)$row['questao_id']] = $row;
        }
        return $rows;
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

    private function persistQuestoes(int $auditoriaId, array $questoes): void
    {
        $stmt = $this->db->prepare("INSERT INTO auditoria_questoes
            (auditoria_id, responsavel_nome, pergunta, referencia_esperada, processos_json, ordem)
            VALUES (:auditoria_id, :responsavel_nome, :pergunta, :referencia_esperada, :processos_json, :ordem)");
        $ordem = 1;
        foreach ($questoes as $questao) {
            $stmt->execute([
                'auditoria_id' => $auditoriaId,
                'responsavel_nome' => (string)($questao['responsavel_nome'] ?? ''),
                'pergunta' => (string)($questao['pergunta'] ?? ''),
                'referencia_esperada' => (string)($questao['referencia_esperada'] ?? ''),
                'processos_json' => json_encode(array_values($questao['processos'] ?? []), JSON_UNESCAPED_UNICODE),
                'ordem' => $ordem++,
            ]);
        }
    }

    private function persistAvaliacoesNoTx(int $auditoriaId, array $avaliacoes, int $userId): void
    {
        $up = $this->db->prepare("INSERT INTO auditoria_avaliacoes
            (auditoria_id, questao_id, conformidade, observacoes, auto_saved_at, updated_by)
            VALUES (:auditoria_id, :questao_id, :conformidade, :observacoes, NOW(), :updated_by)
            ON DUPLICATE KEY UPDATE
            conformidade = VALUES(conformidade),
            observacoes = VALUES(observacoes),
            auto_saved_at = NOW(),
            updated_by = VALUES(updated_by)");
        foreach ($avaliacoes as $item) {
            $conf = (string)$item['conformidade'];
            if (!in_array($conf, ['pendente','conforme','nao_conforme','nao_aplica'], true)) {
                $conf = 'pendente';
            }
            $up->execute([
                'auditoria_id' => $auditoriaId,
                'questao_id' => (int)$item['questao_id'],
                'conformidade' => $conf,
                'observacoes' => (string)$item['observacoes'],
                'updated_by' => $userId,
            ]);
        }
    }

    private function computeConformidadeStats(int $auditoriaId): array
    {
        $sumStmt = $this->db->prepare("SELECT
                SUM(CASE WHEN conformidade = 'conforme' THEN 1 ELSE 0 END) AS total_conforme,
                SUM(CASE WHEN conformidade = 'nao_conforme' THEN 1 ELSE 0 END) AS total_nao_conforme,
                SUM(CASE WHEN conformidade = 'nao_aplica' THEN 1 ELSE 0 END) AS total_nao_aplica
            FROM auditoria_avaliacoes
            WHERE auditoria_id = :id");
        $sumStmt->execute(['id' => $auditoriaId]);
        $sum = $sumStmt->fetch() ?: ['total_conforme' => 0, 'total_nao_conforme' => 0, 'total_nao_aplica' => 0];
        $conforme = (int)$sum['total_conforme'];
        $naoConforme = (int)$sum['total_nao_conforme'];
        $naoAplica = (int)$sum['total_nao_aplica'];
        $validas = max(0, $conforme + $naoConforme);
        $pct = $validas > 0 ? round(($conforme / $validas) * 100, 2) : 0.00;
        $semaforo = 'vermelho';
        if ($pct >= 91) $semaforo = 'verde';
        elseif ($pct >= 76) $semaforo = 'amarelo';
        return [
            'conforme' => $conforme,
            'nao_conforme' => $naoConforme,
            'nao_aplica' => $naoAplica,
            'validas' => $validas,
            'pct' => $pct,
            'semaforo' => $semaforo,
        ];
    }
}
