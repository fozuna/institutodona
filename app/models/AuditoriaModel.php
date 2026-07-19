<?php
namespace App\Models;

use App\Core\Auth;

class AuditoriaModel extends BaseModel
{
    private static bool $tablesEnsured = false;
    private ?string $lastError = null;

    public static function percentSplit(int $conforme, int $naoConforme): array
    {
        $conforme = max(0, $conforme);
        $naoConforme = max(0, $naoConforme);
        $total = $conforme + $naoConforme;
        if ($total <= 0) {
            return ['conforme' => 0.00, 'nao_conforme' => 0.00];
        }
        $rawC = ($conforme / $total) * 100;
        $rawN = ($naoConforme / $total) * 100;
        $pctC = round($rawC, 2);
        $pctN = round($rawN, 2);
        $diff = round(100 - ($pctC + $pctN), 2);
        if (abs($diff) >= 0.01) {
            if ($rawC >= $rawN) {
                $pctC = round($pctC + $diff, 2);
            } else {
                $pctN = round($pctN + $diff, 2);
            }
        }
        $pctC = max(0.00, min(100.00, $pctC));
        $pctN = max(0.00, min(100.00, $pctN));
        return ['conforme' => $pctC, 'nao_conforme' => $pctN];
    }

    private function safeRollback(): void
    {
        try {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        } catch (\Throwable $e) {
        }
    }

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
                lock_version INT NOT NULL DEFAULT 1,
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
            if (!\App\Database\Database::columnExists('auditorias', 'lock_version')) {
                $this->db->exec("ALTER TABLE auditorias ADD COLUMN lock_version INT NOT NULL DEFAULT 1 AFTER updated_by");
            }
            try {
                $this->db->exec("ALTER TABLE auditorias MODIFY COLUMN responsavel_id INT NULL");
            } catch (\PDOException $e) {
            }
            try { $this->db->exec("ALTER TABLE auditorias MODIFY COLUMN status ENUM('Rascunho','Agendada','Em Auditoria','Realizada') NOT NULL DEFAULT 'Rascunho'"); } catch (\PDOException $e) {}
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
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_responsaveis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                colaborador_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_auditoria_responsavel (auditoria_id, colaborador_id),
                INDEX idx_auditoria_responsaveis_auditoria (auditoria_id),
                INDEX idx_auditoria_responsaveis_colaborador (colaborador_id),
                CONSTRAINT fk_auditoria_responsaveis_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_questao_responsaveis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                questao_id INT NOT NULL,
                colaborador_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_questao_responsavel (questao_id, colaborador_id),
                INDEX idx_auditoria_qresp_questao (questao_id),
                INDEX idx_auditoria_qresp_colaborador (colaborador_id),
                CONSTRAINT fk_auditoria_qresp_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE
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
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_avaliacoes_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                questao_id INT NOT NULL,
                old_observacoes TEXT NULL,
                new_observacoes TEXT NULL,
                updated_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_aud_av_log_aud (auditoria_id),
                INDEX idx_aud_av_log_q (questao_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_historico (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auditoria_id INT NOT NULL,
                dados_anteriores JSON NOT NULL,
                usuario_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_aud_hist_auditoria (auditoria_id),
                CONSTRAINT fk_aud_hist_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
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

    private function isValidAuditoriaCatalogSelection(int $clienteId, int $setorId): bool
    {
        if ($clienteId <= 0 || $setorId <= 0) {
            return false;
        }
        if (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId)) {
            return false;
        }
        return $this->setorBelongsToCatalogCliente($setorId, $clienteId);
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
        $where = array_merge($where, $this->buildListFilterConditions($filters, $params, 'lst'));

        if ($this->hasScopeRestriction()) {
            $where[] = $this->tenantInCondition('a.cliente_id', $params, 'audsc');
        }

        $sortCol = (string)($filters['sort_col'] ?? 'data');
        $sortDir = strtolower((string)($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $columnMap = [
            'nome' => 'a.nome_auditoria',
            'setor' => 's.nome',
            'departamento' => 'd.nome',
            'data' => 'a.data_auditoria',
            'status' => 'a.status',
            'empresa' => 'c.nome_empresa',
        ];
        $order = ($columnMap[$sortCol] ?? $columnMap['data']) . " {$sortDir}, a.id DESC";
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM auditorias a
            JOIN clientes c ON c.id = a.cliente_id
            JOIN setores s ON s.id = a.setor_id
            LEFT JOIN departamentos d ON d.id = s.departamento_id
            WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome,
                       COALESCE(d.nome, 'Não informado') AS departamento_nome,
                       COALESCE(NULLIF(resp.responsaveis_nomes, ''), legacy_resp.nome, '') AS responsaveis_nomes,
                       (SELECT COUNT(*) FROM auditoria_questoes q WHERE q.auditoria_id = a.id) AS total_questoes
                FROM auditorias a
                JOIN clientes c ON c.id = a.cliente_id
                JOIN setores s ON s.id = a.setor_id
                LEFT JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN colaboradores legacy_resp ON legacy_resp.id = a.responsavel_id
                LEFT JOIN (
                    SELECT ar.auditoria_id, GROUP_CONCAT(DISTINCT col.nome ORDER BY col.nome SEPARATOR ', ') AS responsaveis_nomes
                    FROM auditoria_responsaveis ar
                    JOIN colaboradores col ON col.id = ar.colaborador_id
                    GROUP BY ar.auditoria_id
                ) resp ON resp.auditoria_id = a.id
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

    public function summarizeMetrics(array $filters): array
    {
        $this->ensureTables();
        if (!$this->canBypassScope() && count($this->tenantClientIds()) === 0) {
            return [
                'geral' => ['count' => 0, 'media' => null],
                'filtrada' => ['count' => 0, 'media' => null],
            ];
        }

        $baseWhere = ['a.deleted_at IS NULL', "a.status = 'Realizada'", 'a.conformidade_pct IS NOT NULL'];
        $generalParams = [];
        $filteredParams = [];
        $generalFilters = $filters;
        unset($generalFilters['farol']);
        $baseWhere = array_merge($baseWhere, $this->buildListFilterConditions($generalFilters, $generalParams, 'smg'));
        if ($this->hasScopeRestriction()) {
            $baseWhere[] = $this->tenantInCondition('a.cliente_id', $generalParams, 'audmg');
        }
        $generalWhereSql = implode(' AND ', $baseWhere);

        $generalSql = "SELECT COUNT(*) AS total, AVG(a.conformidade_pct) AS media
                       FROM auditorias a
                       JOIN clientes c ON c.id = a.cliente_id
                       JOIN setores s ON s.id = a.setor_id
                       LEFT JOIN departamentos d ON d.id = s.departamento_id
                       WHERE $generalWhereSql";
        $stmt = $this->db->prepare($generalSql);
        $stmt->execute($generalParams);
        $general = $stmt->fetch() ?: [];

        $filteredWhere = ['a.deleted_at IS NULL', "a.status = 'Realizada'", 'a.conformidade_pct IS NOT NULL'];
        $filteredWhere = array_merge($filteredWhere, $this->buildListFilterConditions($filters, $filteredParams, 'smf'));
        if ($this->hasScopeRestriction()) {
            $filteredWhere[] = $this->tenantInCondition('a.cliente_id', $filteredParams, 'audmf');
        }
        $filteredWhereSql = implode(' AND ', $filteredWhere);

        $filteredSql = "SELECT COUNT(*) AS total, AVG(a.conformidade_pct) AS media
                        FROM auditorias a
                        JOIN clientes c ON c.id = a.cliente_id
                        JOIN setores s ON s.id = a.setor_id
                        LEFT JOIN departamentos d ON d.id = s.departamento_id
                        WHERE $filteredWhereSql";
        $stmt = $this->db->prepare($filteredSql);
        $stmt->execute($filteredParams);
        $filtered = $stmt->fetch() ?: [];

        return [
            'geral' => [
                'count' => (int)($general['total'] ?? 0),
                'media' => isset($general['media']) ? (float)$general['media'] : null,
            ],
            'filtrada' => [
                'count' => (int)($filtered['total'] ?? 0),
                'media' => isset($filtered['media']) ? (float)$filtered['media'] : null,
            ],
        ];
    }

    /**
     * Consolida dados para o relatório executivo de fechamento: quantidade de
     * auditorias realizadas no período/filtros, totais conforme/não conforme,
     * agrupamento por área (setor), lista de não conformidades com observação
     * e observações adicionais registradas em itens conforme/não se aplica.
     */
    public function executiveReportData(array $filters): array
    {
        $this->ensureTables();
        $empty = [
            'total_auditorias' => 0,
            'total_itens' => 0,
            'total_conforme' => 0,
            'total_nao_conforme' => 0,
            'total_nao_aplica' => 0,
            'conformidade_pct_media' => null,
            'por_area' => [],
            'nao_conformidades' => [],
            'observacoes_adicionais' => [],
        ];
        if (!$this->canBypassScope() && count($this->tenantClientIds()) === 0) {
            return $empty;
        }

        $where = ["a.deleted_at IS NULL", "a.status = 'Realizada'", 'a.conformidade_pct IS NOT NULL'];
        $params = [];
        $where = array_merge($where, $this->buildListFilterConditions($filters, $params, 'exec'));
        if ($this->hasScopeRestriction()) {
            $where[] = $this->tenantInCondition('a.cliente_id', $params, 'audexec');
        }
        $whereSql = implode(' AND ', $where);

        $sql = "SELECT a.id AS auditoria_id, a.nome_auditoria, a.data_auditoria, a.conformidade_pct, a.semaforo,
                       c.nome_empresa AS cliente_nome,
                       s.nome AS setor_nome,
                       COALESCE(d.nome, 'Não informado') AS departamento_nome,
                       q.id AS questao_id, q.pergunta, q.ordem,
                       av.conformidade, av.observacoes
                FROM auditorias a
                JOIN clientes c ON c.id = a.cliente_id
                JOIN setores s ON s.id = a.setor_id
                LEFT JOIN departamentos d ON d.id = s.departamento_id
                JOIN auditoria_questoes q ON q.auditoria_id = a.id
                LEFT JOIN auditoria_avaliacoes av ON av.auditoria_id = a.id AND av.questao_id = q.id
                WHERE $whereSql
                ORDER BY s.nome, a.data_auditoria, a.id, q.ordem, q.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $auditoriasVistas = [];
        $auditoriaConformidadePct = [];
        $questaoIndexPorAuditoria = [];
        $areas = [];
        $naoConformidades = [];
        $observacoesAdicionais = [];
        $totalConforme = 0;
        $totalNaoConforme = 0;
        $totalNaoAplica = 0;

        foreach ($stmt->fetchAll() as $row) {
            $auditoriaId = (int)$row['auditoria_id'];
            $setorNome = (string)$row['setor_nome'];
            if (!isset($auditoriasVistas[$auditoriaId])) {
                $auditoriasVistas[$auditoriaId] = true;
                $auditoriaConformidadePct[$auditoriaId] = $row['conformidade_pct'] !== null ? (float)$row['conformidade_pct'] : null;
            }
            if (!isset($areas[$setorNome])) {
                $areas[$setorNome] = [
                    'setor_nome' => $setorNome,
                    'departamento_nome' => (string)$row['departamento_nome'],
                    'auditoria_ids' => [],
                    'conforme' => 0,
                    'nao_conforme' => 0,
                    'nao_aplica' => 0,
                ];
            }
            $areas[$setorNome]['auditoria_ids'][$auditoriaId] = true;

            $questaoIndexPorAuditoria[$auditoriaId] = ($questaoIndexPorAuditoria[$auditoriaId] ?? 0) + 1;
            $questaoNumero = $questaoIndexPorAuditoria[$auditoriaId];
            $observacao = trim((string)($row['observacoes'] ?? ''));

            $conformidade = (string)($row['conformidade'] ?? '');
            if ($conformidade === 'conforme') {
                $totalConforme++;
                $areas[$setorNome]['conforme']++;
                if ($observacao !== '') {
                    $observacoesAdicionais[] = [
                        'setor_nome' => $setorNome,
                        'departamento_nome' => (string)$row['departamento_nome'],
                        'cliente_nome' => (string)$row['cliente_nome'],
                        'auditoria_id' => $auditoriaId,
                        'auditoria_nome' => (string)$row['nome_auditoria'],
                        'data_auditoria' => (string)$row['data_auditoria'],
                        'questao_numero' => $questaoNumero,
                        'pergunta' => (string)$row['pergunta'],
                        'conformidade' => 'Conforme',
                        'observacao' => $observacao,
                    ];
                }
            } elseif ($conformidade === 'nao_conforme') {
                $totalNaoConforme++;
                $areas[$setorNome]['nao_conforme']++;
                $naoConformidades[] = [
                    'setor_nome' => $setorNome,
                    'departamento_nome' => (string)$row['departamento_nome'],
                    'cliente_nome' => (string)$row['cliente_nome'],
                    'auditoria_id' => $auditoriaId,
                    'auditoria_nome' => (string)$row['nome_auditoria'],
                    'data_auditoria' => (string)$row['data_auditoria'],
                    'questao_numero' => $questaoNumero,
                    'pergunta' => (string)$row['pergunta'],
                    'observacao' => $observacao,
                ];
            } elseif ($conformidade === 'nao_aplica') {
                $totalNaoAplica++;
                $areas[$setorNome]['nao_aplica']++;
                if ($observacao !== '') {
                    $observacoesAdicionais[] = [
                        'setor_nome' => $setorNome,
                        'departamento_nome' => (string)$row['departamento_nome'],
                        'cliente_nome' => (string)$row['cliente_nome'],
                        'auditoria_id' => $auditoriaId,
                        'auditoria_nome' => (string)$row['nome_auditoria'],
                        'data_auditoria' => (string)$row['data_auditoria'],
                        'questao_numero' => $questaoNumero,
                        'pergunta' => (string)$row['pergunta'],
                        'conformidade' => 'Não se aplica',
                        'observacao' => $observacao,
                    ];
                }
            }
        }

        $porArea = [];
        foreach ($areas as $area) {
            $totalAreaAuditorias = count($area['auditoria_ids']);
            $split = self::percentSplit($area['conforme'], $area['nao_conforme']);
            $porArea[] = [
                'setor_nome' => $area['setor_nome'],
                'departamento_nome' => $area['departamento_nome'],
                'total_auditorias' => $totalAreaAuditorias,
                'conforme' => $area['conforme'],
                'nao_conforme' => $area['nao_conforme'],
                'nao_aplica' => $area['nao_aplica'],
                'conformidade_pct' => $split['conforme'],
            ];
        }

        $pctValues = array_values(array_filter($auditoriaConformidadePct, static fn($v): bool => $v !== null));
        $conformidadePctMedia = !empty($pctValues) ? array_sum($pctValues) / count($pctValues) : null;

        return [
            'total_auditorias' => count($auditoriasVistas),
            'total_itens' => $totalConforme + $totalNaoConforme + $totalNaoAplica,
            'total_conforme' => $totalConforme,
            'total_nao_conforme' => $totalNaoConforme,
            'total_nao_aplica' => $totalNaoAplica,
            'conformidade_pct_media' => $conformidadePctMedia,
            'por_area' => $porArea,
            'nao_conformidades' => $naoConformidades,
            'observacoes_adicionais' => $observacoesAdicionais,
        ];
    }

    private function buildListFilterConditions(array $filters, array &$params, string $prefix): array
    {
        $where = [];
        if (!empty($filters['cliente'])) {
            $where[] = 'a.cliente_id = :' . $prefix . '_cliente';
            $params[$prefix . '_cliente'] = (int)$filters['cliente'];
        }
        if (!empty($filters['setor'])) {
            $where[] = 'a.setor_id = :' . $prefix . '_setor';
            $params[$prefix . '_setor'] = (int)$filters['setor'];
        }
        if (!empty($filters['departamento'])) {
            $where[] = 's.departamento_id = :' . $prefix . '_departamento';
            $params[$prefix . '_departamento'] = (int)$filters['departamento'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'a.status = :' . $prefix . '_status';
            $params[$prefix . '_status'] = (string)$filters['status'];
        }
        if (!empty($filters['farol'])) {
            $where[] = 'a.semaforo = :' . $prefix . '_farol';
            $params[$prefix . '_farol'] = (string)$filters['farol'];
        }
        if (!empty($filters['inicio'])) {
            $where[] = 'a.data_auditoria >= :' . $prefix . '_inicio';
            $params[$prefix . '_inicio'] = (string)$filters['inicio'];
        }
        if (!empty($filters['fim'])) {
            $where[] = 'a.data_auditoria <= :' . $prefix . '_fim';
            $params[$prefix . '_fim'] = (string)$filters['fim'];
        }
        if (!empty($filters['q'])) {
            $qValue = '%' . trim((string)$filters['q']) . '%';
            $qColumns = ['a.nome_auditoria', 'a.pergunta', 'c.nome_empresa', 's.nome', 'd.nome'];
            $qConditions = [];
            foreach ($qColumns as $i => $column) {
                $holder = $prefix . '_q' . $i;
                $qConditions[] = $column . ' LIKE :' . $holder;
                $params[$holder] = $qValue;
            }
            $where[] = '(' . implode(' OR ', $qConditions) . ')';
        }
        return $where;
    }

    public function find(int $id): ?array
    {
        $this->ensureTables();
        if (!$this->canBypassScope() && count($this->tenantClientIds()) === 0) {
            return null;
        }
        $params = ['id' => $id];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('a.cliente_id', $params, 'audf')) : '';
        $stmt = $this->db->prepare("SELECT a.*, c.nome_empresa AS cliente_nome, s.nome AS setor_nome,
                                           COALESCE(NULLIF(resp.responsaveis_nomes, ''), legacy_resp.nome, '') AS responsaveis_nomes
                                    FROM auditorias a
                                    JOIN clientes c ON c.id = a.cliente_id
                                    JOIN setores s ON s.id = a.setor_id
                                    LEFT JOIN colaboradores legacy_resp ON legacy_resp.id = a.responsavel_id
                                    LEFT JOIN (
                                        SELECT ar.auditoria_id, GROUP_CONCAT(DISTINCT col.nome ORDER BY col.nome SEPARATOR ', ') AS responsaveis_nomes
                                        FROM auditoria_responsaveis ar
                                        JOIN colaboradores col ON col.id = ar.colaborador_id
                                        GROUP BY ar.auditoria_id
                                    ) resp ON resp.auditoria_id = a.id
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
        $item['responsaveis'] = $this->responsaveisByAuditoria((int)$item['id']);
        if (empty($item['responsaveis']) && !empty($item['responsaveis_nomes'])) {
            $item['responsaveis'] = $this->responsaveisFromNames((string)$item['responsaveis_nomes']);
        }
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
        $questaoResponsaveis = $this->responsaveisByQuestaoIds(array_map(static fn($row) => (int)$row['id'], $rows));
        foreach ($rows as &$row) {
            $qid = (int)$row['id'];
            $selected = $questaoResponsaveis[$qid] ?? [];
            $row['responsavel_ids'] = array_map(static fn($item) => (int)$item['id'], $selected);
            $storedLabels = array_values(array_filter(array_map('trim', explode(',', (string)($row['responsavel_nome'] ?? '')))));
            $selectedLabels = array_map(static fn($item) => (string)$item['nome'], $selected);
            $row['responsavel_labels'] = array_values(array_unique(array_merge($selectedLabels, $storedLabels)));
            $row['responsavel_ids'] = array_pad($row['responsavel_ids'], count($row['responsavel_labels']), 0);
            if (!empty($row['responsavel_labels'])) {
                $row['responsavel_nome'] = implode(', ', $row['responsavel_labels']);
            }
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
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by, lock_version = lock_version + 1 WHERE id = :id AND deleted_at IS NULL AND status IN ('Rascunho','Agendada')$scope")
                ->execute($params);
        } catch (\PDOException $e) {
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isNomeDisponivel(string $nome, ?int $excludeId = null): bool
    {
        $this->ensureTables();
        $params = ['nome' => $nome];
        $sql = 'SELECT COUNT(*) FROM auditorias WHERE nome_auditoria = :nome';
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function validarNomeAuditoria(string $nome): ?string
    {
        $nome = trim($nome);
        if ($nome === '') return 'Nome da auditoria é obrigatório.';
        if (mb_strlen($nome) > 100) return 'Nome da auditoria deve ter no máximo 100 caracteres.';
        if (!preg_match('/^[\p{L}\p{N}\s._-]+$/u', $nome)) return 'Nome contém caracteres inválidos.';
        return null;
    }

    public function renomear(int $id, string $novoNome, int $userId): bool
    {
        $this->ensureTables();
        $err = $this->validarNomeAuditoria($novoNome);
        if ($err !== null) return false;
        if (!$this->isNomeDisponivel($novoNome, $id)) return false;
        $params = ['id' => $id, 'nome' => $novoNome, 'uid' => $userId];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audrn')) : '';
        $stmt = $this->db->prepare("UPDATE auditorias SET nome_auditoria = :nome, updated_by = :uid WHERE id = :id$scope");
        return $stmt->execute($params) && $stmt->rowCount() > 0;
    }

    public function duplicateFrom(array $src, array $newData, int $userId): int
    {
        $this->ensureTables();
        $this->lastError = null;
        $tryStatus = ['Rascunho','Agendada']; // fallback caso enum não tenha Rascunho
        foreach ($tryStatus as $status) {
            $this->db->beginTransaction();
            try {
            $stmt = $this->db->prepare("INSERT INTO auditorias
                (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
                VALUES
                (:cliente_id, :setor_id, NULL, :data_auditoria, :nome_auditoria, :pergunta, :objetivo, :referencia_esperada, :status, :created_by, :updated_by)");
            $primeira = $src['questoes'][0] ?? ['pergunta' => '', 'referencia_esperada' => '', 'responsavel_nome' => ''];
            $err = $this->validarNomeAuditoria((string)($newData['nome_auditoria'] ?? ''));
            if ($err !== null) { $this->lastError = $err; $this->safeRollback(); return 0; }
            if (!$this->isNomeDisponivel((string)$newData['nome_auditoria'])) { $this->lastError = 'Já existe uma auditoria com este nome.'; $this->safeRollback(); return 0; }
            $stmt->execute([
                'cliente_id' => (int)$src['cliente_id'],
                'setor_id' => (int)$src['setor_id'],
                'data_auditoria' => (string)($newData['data_auditoria'] ?? $src['data_auditoria']),
                'nome_auditoria' => (string)($newData['nome_auditoria'] ?? ($src['nome_auditoria'] ?? 'Cópia')),
                'pergunta' => (string)$primeira['pergunta'],
                'objetivo' => (string)($primeira['responsavel_nome'] ?? ''),
                'referencia_esperada' => (string)$primeira['referencia_esperada'],
                'status' => $status,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $newId = (int)$this->db->lastInsertId();
            $insQ = $this->db->prepare("INSERT INTO auditoria_questoes
                (auditoria_id, responsavel_nome, pergunta, referencia_esperada, processos_json, ordem)
                VALUES (:aid, :resp, :perg, :ref, :proc, :ord)");
            $ordem = 1;
            foreach (($src['questoes'] ?? []) as $q) {
                $insQ->execute([
                    'aid' => $newId,
                    'resp' => (string)($q['responsavel_nome'] ?? ''),
                    'perg' => (string)($q['pergunta'] ?? ''),
                    'ref' => (string)($q['referencia_esperada'] ?? ''),
                    'proc' => isset($q['processos_json']) ? (string)$q['processos_json'] : json_encode($q['processos'] ?? []),
                    'ord' => $ordem++,
                ]);
                $this->syncQuestaoResponsaveis((int)$this->db->lastInsertId(), $this->normalizeResponsavelIds($q['responsavel_ids'] ?? []));
            }
            $duplicatedResponsavelIds = $this->resolveAuditoriaResponsavelIds($src);
            $this->syncAuditoriaResponsaveis($newId, $duplicatedResponsavelIds);
            $this->syncLegacyResponsavelId($newId, $duplicatedResponsavelIds);
            $this->db->commit();
            return $newId;
            } catch (\Throwable $e) {
                $this->safeRollback();
                $this->lastError = $e->getMessage();
                // tenta próximo status (fallback)
            }
        }
        return 0;
    }

    public function create(array $data, int $userId): int
    {
        $this->ensureTables();
        $clienteId = (int)$data['cliente_id'];
        $setorId = (int)($data['setor_id'] ?? 0);
        if (!$this->isValidAuditoriaCatalogSelection($clienteId, $setorId)) {
            return 0;
        }
        $this->db->beginTransaction();
        try {
            $primeira = $data['questoes'][0] ?? ['pergunta' => '', 'referencia_esperada' => '', 'responsavel_nome' => ''];
            $responsavelIds = $this->resolveAuditoriaResponsavelIds($data);
            $stmt = $this->db->prepare("INSERT INTO auditorias
                (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
                VALUES
                (:cliente_id, :setor_id, :responsavel_id, :data_auditoria, :nome_auditoria, :pergunta, :objetivo, :referencia_esperada, 'Agendada', :created_by, :updated_by)");
            $stmt->execute([
                'cliente_id' => $clienteId,
                'setor_id' => $setorId,
                'responsavel_id' => !empty($responsavelIds) ? $responsavelIds[0] : null,
                'data_auditoria' => (string)$data['data_auditoria'],
                'nome_auditoria' => (string)$data['nome_auditoria'],
                'pergunta' => (string)$primeira['pergunta'],
                'objetivo' => (string)($primeira['responsavel_nome'] ?? ''),
                'referencia_esperada' => (string)$primeira['referencia_esperada'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $id = (int)$this->db->lastInsertId();
            $this->persistQuestoes($id, $data['questoes']);
            $this->syncAuditoriaResponsaveis($id, $responsavelIds);
            $this->syncLegacyResponsavelId($id, $responsavelIds);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return 0;
        }
    }

    public function updateAgendada(int $id, array $data, int $userId, ?string $prevUpdatedAt = null, ?int $prevLockVersion = null): bool
    {
        $this->ensureTables();
        $this->lastError = null;
        $clienteId = (int)$data['cliente_id'];
        $setorId = (int)($data['setor_id'] ?? 0);
        if (!$this->isValidAuditoriaCatalogSelection($clienteId, $setorId)) {
            $this->lastError = 'scope';
            return false;
        }
        $this->db->beginTransaction();
        try {
            $primeira = $data['questoes'][0] ?? ['pergunta' => '', 'referencia_esperada' => '', 'responsavel_nome' => ''];
            $responsavelIds = $this->resolveAuditoriaResponsavelIds($data);
            $params = [
            'id' => $id,
            'cliente_id' => $clienteId,
            'setor_id' => $setorId,
            'responsavel_id' => !empty($responsavelIds) ? (int)$responsavelIds[0] : null,
            'data_auditoria' => $data['data_auditoria'],
            'nome_auditoria' => (string)$data['nome_auditoria'],
            'pergunta' => (string)$primeira['pergunta'],
            'objetivo' => (string)($primeira['responsavel_nome'] ?? ''),
            'referencia_esperada' => (string)$primeira['referencia_esperada'],
            'updated_by' => $userId,
            ];
            if ($prevLockVersion !== null) {
                $params['prev_lock_version'] = $prevLockVersion;
            } elseif ($prevUpdatedAt !== null) {
                $params['prev'] = $prevUpdatedAt;
            }
            $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audu')) : '';
            if ($prevLockVersion !== null) {
                $concurrency = ' AND lock_version = :prev_lock_version';
            } elseif ($prevUpdatedAt !== null) {
                $concurrency = ' AND updated_at = :prev';
            } else {
                $concurrency = '';
            }
            $this->saveHistorySnapshot($id, $userId);
            $stmt = $this->db->prepare("UPDATE auditorias
                SET cliente_id = :cliente_id, setor_id = :setor_id, responsavel_id = :responsavel_id, data_auditoria = :data_auditoria, nome_auditoria = :nome_auditoria,
                    pergunta = :pergunta, objetivo = :objetivo, referencia_esperada = :referencia_esperada, updated_by = :updated_by,
                    lock_version = lock_version + 1
                WHERE id = :id AND deleted_at IS NULL$scope$concurrency");
            $updated = $stmt->execute($params) && $stmt->rowCount() > 0;
            if (!$updated) {
                $this->db->rollBack();
                $this->lastError = $this->detectWriteConflict($id, $prevUpdatedAt, $prevLockVersion);
                return false;
            }
            $this->db->prepare('DELETE FROM auditoria_questoes WHERE auditoria_id = :id')->execute(['id' => $id]);
            $this->persistQuestoes($id, $data['questoes']);
            $this->syncAuditoriaResponsaveis($id, $responsavelIds);
            $this->syncLegacyResponsavelId($id, $responsavelIds);
            $this->db->prepare('DELETE FROM auditoria_avaliacoes WHERE auditoria_id = :id AND finalized_at IS NULL')->execute(['id' => $id]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->lastError = 'exception';
            return false;
        }
    }

    public function updatePartial(int $id, array $data, int $userId, ?string $prevUpdatedAt = null, ?int $prevLockVersion = null): bool
    {
        $this->ensureTables();
        $this->lastError = null;
        $current = $this->find($id);
        if (!$current) {
            return false;
        }
        $params = ['id' => $id, 'updated_by' => $userId];
        $set = ['updated_by = :updated_by'];

        $effectiveClienteId = (int)($data['cliente_id'] ?? ($current['cliente_id'] ?? 0));
        $effectiveSetorId = (int)($data['setor_id'] ?? ($current['setor_id'] ?? 0));
        if (!$this->isValidAuditoriaCatalogSelection($effectiveClienteId, $effectiveSetorId)) {
            return false;
        }

        $clienteId = (int)($data['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $params['cliente_id'] = $clienteId;
            $set[] = 'cliente_id = :cliente_id';
        }
        if (array_key_exists('responsavel_ids', $data)) {
            $responsavelIds = $this->resolveAuditoriaResponsavelIds($data);
            $params['responsavel_id'] = !empty($responsavelIds) ? $responsavelIds[0] : null;
            $set[] = 'responsavel_id = :responsavel_id';
        } else {
            $responsavelIds = null;
        }
        $setorId = (int)($data['setor_id'] ?? 0);
        if ($setorId > 0) {
            $params['setor_id'] = $setorId;
            $set[] = 'setor_id = :setor_id';
        }
        $nome = trim((string)($data['nome_auditoria'] ?? ''));
        if ($nome !== '') {
            $params['nome_auditoria'] = $nome;
            $set[] = 'nome_auditoria = :nome_auditoria';
        }
        $dataAud = (string)($data['data_auditoria'] ?? '');
        if ($dataAud !== '') {
            $params['data_auditoria'] = $dataAud;
            $set[] = 'data_auditoria = :data_auditoria';
        }
        $set[] = 'lock_version = lock_version + 1';
        if ($prevLockVersion !== null) {
            $params['prev_lock_version'] = $prevLockVersion;
            $concurrency = ' AND lock_version = :prev_lock_version';
        } elseif ($prevUpdatedAt !== null) {
            $params['prev'] = $prevUpdatedAt;
            $concurrency = ' AND updated_at = :prev';
        } else {
            $concurrency = '';
        }

        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audup')) : '';
        $this->db->beginTransaction();
        try {
            $this->saveHistorySnapshot($id, $userId);
            $stmt = $this->db->prepare("UPDATE auditorias SET " . implode(', ', $set) . " WHERE id = :id AND deleted_at IS NULL$scope$concurrency");
            $ok = $stmt->execute($params);
            if (!$ok || $stmt->rowCount() <= 0) {
                $this->db->rollBack();
                $this->lastError = $this->detectWriteConflict($id, $prevUpdatedAt, $prevLockVersion);
                return false;
            }
            if (!empty($data['questoes']) && is_array($data['questoes'])) {
                $this->db->prepare('DELETE FROM auditoria_questoes WHERE auditoria_id = :id')->execute(['id' => $id]);
                $this->persistQuestoes($id, $data['questoes']);
            }
            if (is_array($responsavelIds)) {
                $this->syncAuditoriaResponsaveis($id, $responsavelIds);
                $this->syncLegacyResponsavelId($id, $responsavelIds);
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->safeRollback();
            $this->lastError = 'exception';
            return false;
        }
    }

    public function updateRealizadaCompleta(int $id, array $data, array $avaliacoes, int $userId, ?string $prevUpdatedAt = null, ?int $prevLockVersion = null): bool
    {
        $this->ensureTables();
        $this->lastError = null;
        $current = $this->findWithQuestoes($id);
        if (!$current) {
            $this->lastError = 'not_found';
            return false;
        }
        $clienteId = (int)($current['cliente_id'] ?? 0);
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            $this->lastError = 'scope';
            return false;
        }

        $existingQuestions = [];
        foreach (($current['questoes'] ?? []) as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid > 0) {
                $existingQuestions[$qid] = $q;
            }
        }
        if (empty($existingQuestions)) {
            $this->lastError = 'no_questions';
            return false;
        }

        $incomingQuestions = [];
        foreach (($data['questoes'] ?? []) as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid <= 0 || !isset($existingQuestions[$qid])) {
                $this->lastError = 'invalid_question_set';
                return false;
            }
            $incomingQuestions[$qid] = $q;
        }
        if (count($incomingQuestions) !== count($existingQuestions)) {
            $this->lastError = 'invalid_question_set';
            return false;
        }

        $avaliacoesMap = [];
        foreach ($avaliacoes as $a) {
            $qid = (int)($a['questao_id'] ?? 0);
            if ($qid > 0) {
                $avaliacoesMap[$qid] = $a;
            }
        }

        $this->db->beginTransaction();
        try {
            $responsavelIds = $this->resolveAuditoriaResponsavelIds($data);
            $params = [
                'id' => $id,
                'responsavel_id' => !empty($responsavelIds) ? (int)$responsavelIds[0] : null,
                'data_auditoria' => $data['data_auditoria'],
                'nome_auditoria' => (string)$data['nome_auditoria'],
                'pergunta' => (string)(($data['questoes'][0]['pergunta'] ?? '') ?: ($current['pergunta'] ?? '')),
                'objetivo' => (string)(($data['questoes'][0]['responsavel_nome'] ?? '') ?: ($current['objetivo'] ?? '')),
                'referencia_esperada' => (string)(($data['questoes'][0]['referencia_esperada'] ?? '') ?: ($current['referencia_esperada'] ?? '')),
                'updated_by' => $userId,
            ];
            if ($prevLockVersion !== null) {
                $params['prev_lock_version'] = $prevLockVersion;
                $concurrency = ' AND lock_version = :prev_lock_version';
            } elseif ($prevUpdatedAt !== null) {
                $params['prev'] = $prevUpdatedAt;
                $concurrency = ' AND updated_at = :prev';
            } else {
                $concurrency = '';
            }
            $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audur')) : '';
            $this->saveHistorySnapshot($id, $userId);
            $stmt = $this->db->prepare("UPDATE auditorias
                SET data_auditoria = :data_auditoria,
                    nome_auditoria = :nome_auditoria,
                    responsavel_id = :responsavel_id,
                    pergunta = :pergunta,
                    objetivo = :objetivo,
                    referencia_esperada = :referencia_esperada,
                    updated_by = :updated_by,
                    lock_version = lock_version + 1
                WHERE id = :id AND deleted_at IS NULL$scope$concurrency");
            $ok = $stmt->execute($params) && $stmt->rowCount() > 0;
            if (!$ok) {
                $this->db->rollBack();
                $this->lastError = $this->detectWriteConflict($id, $prevUpdatedAt, $prevLockVersion);
                return false;
            }

            $upQuestao = $this->db->prepare('UPDATE auditoria_questoes SET responsavel_nome = :responsavel_nome, pergunta = :pergunta, referencia_esperada = :referencia_esperada, processos_json = :processos_json, ordem = :ordem WHERE id = :id AND auditoria_id = :auditoria_id');
            $ordem = 1;
            foreach ($data['questoes'] as $q) {
                $qid = (int)$q['id'];
                $upQuestao->execute([
                    'id' => $qid,
                    'auditoria_id' => $id,
                    'responsavel_nome' => $this->joinResponsavelLabels($q),
                    'pergunta' => (string)($q['pergunta'] ?? ''),
                    'referencia_esperada' => (string)($q['referencia_esperada'] ?? ''),
                    'processos_json' => json_encode(array_values($q['processos'] ?? []), JSON_UNESCAPED_UNICODE),
                    'ordem' => $ordem++,
                ]);
                $this->syncQuestaoResponsaveis($qid, $this->normalizeResponsavelIds($q['responsavel_ids'] ?? []));
            }
            $this->syncAuditoriaResponsaveis($id, $responsavelIds);
            $this->syncLegacyResponsavelId($id, $responsavelIds);

            $payloadAvaliacoes = [];
            foreach (array_keys($existingQuestions) as $qid) {
                $payloadAvaliacoes[] = [
                    'questao_id' => $qid,
                    'conformidade' => (string)($avaliacoesMap[$qid]['conformidade'] ?? 'pendente'),
                    'observacoes' => (string)($avaliacoesMap[$qid]['observacoes'] ?? ''),
                ];
            }
            $this->persistAvaliacoesNoTx($id, $payloadAvaliacoes, $userId);

            $stats = $this->computeConformidadeStats($id);
            $resumo = 'Conforme: ' . $stats['conforme'] . ' | Não conforme: ' . $stats['nao_conforme'] . ' | N/A: ' . $stats['nao_aplica'];
            $this->db->prepare("UPDATE auditorias
                SET avaliacao = :avaliacao, conformidade_pct = :pct, semaforo = :sem
                WHERE id = :id")
                ->execute([
                    'id' => $id,
                    'avaliacao' => $resumo,
                    'pct' => $stats['pct'],
                    'sem' => $stats['semaforo'],
                ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->safeRollback();
            $this->lastError = 'exception';
            return false;
        }
    }

    /**
     * Atualiza somente as observações por questão de uma auditoria já finalizada
     * (fluxo "Editar Observações"). Calcula o diff antes de abrir transação: se
     * nenhuma observação realmente mudou, retorna sucesso com updated=0 sem tocar
     * em lock_version, histórico ou log — evitando registros fantasmas.
     *
     * @param array<int,string> $observacoesPorQuestao questao_id => novo texto
     * @return array{ok: bool, updated: int}
     */
    public function updateObservacoesRealizada(int $id, array $observacoesPorQuestao, int $userId, ?string $prevUpdatedAt = null, ?int $prevLockVersion = null): array
    {
        $this->ensureTables();
        $this->lastError = null;
        $current = $this->find($id);
        if (!$current || (string)($current['status'] ?? '') !== 'Realizada') {
            $this->lastError = 'not_found';
            return ['ok' => false, 'updated' => 0];
        }
        $clienteId = (int)($current['cliente_id'] ?? 0);
        if ($clienteId <= 0 || (!$this->canBypassScope() && !$this->canAccessClienteId($clienteId))) {
            $this->lastError = 'scope';
            return ['ok' => false, 'updated' => 0];
        }

        $old = $this->respostasByAuditoria($id);
        $changes = [];
        foreach ($observacoesPorQuestao as $qid => $newObs) {
            $qid = (int)$qid;
            if ($qid <= 0 || !isset($old[$qid])) {
                continue;
            }
            $oldObs = trim((string)($old[$qid]['observacoes'] ?? ''));
            $newObsTrim = trim((string)$newObs);
            if ($oldObs === $newObsTrim) {
                continue;
            }
            $changes[$qid] = ['old' => $oldObs, 'new' => $newObsTrim];
        }

        if (empty($changes)) {
            return ['ok' => true, 'updated' => 0];
        }

        try {
            if (!$this->db->beginTransaction()) {
                $this->lastError = 'exception';
                return ['ok' => false, 'updated' => 0];
            }
        } catch (\Throwable $e) {
            $this->lastError = 'exception';
            return ['ok' => false, 'updated' => 0];
        }

        try {
            $params = ['id' => $id, 'updated_by' => $userId];
            if ($prevLockVersion !== null) {
                $params['prev_lock_version'] = $prevLockVersion;
                $concurrency = ' AND lock_version = :prev_lock_version';
            } elseif ($prevUpdatedAt !== null) {
                $params['prev'] = $prevUpdatedAt;
                $concurrency = ' AND updated_at = :prev';
            } else {
                $concurrency = '';
            }
            $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audobs')) : '';
            $lockStmt = $this->db->prepare("UPDATE auditorias
                SET updated_by = :updated_by, lock_version = lock_version + 1
                WHERE id = :id AND deleted_at IS NULL AND status = 'Realizada'$scope$concurrency");
            $lockStmt->execute($params);
            if ($lockStmt->rowCount() <= 0) {
                $this->safeRollback();
                $this->lastError = $this->detectWriteConflict($id, $prevUpdatedAt, $prevLockVersion);
                return ['ok' => false, 'updated' => 0];
            }

            $this->saveHistorySnapshot($id, $userId);

            $stmtUp = $this->db->prepare('UPDATE auditoria_avaliacoes SET observacoes = :obs, updated_by = :uid, updated_at = NOW() WHERE auditoria_id = :aid AND questao_id = :qid');
            $stmtLog = $this->db->prepare('INSERT INTO auditoria_avaliacoes_log (auditoria_id, questao_id, old_observacoes, new_observacoes, updated_by) VALUES (:aid, :qid, :old, :new, :uid)');
            $updated = 0;
            foreach ($changes as $qid => $diff) {
                $stmtUp->execute(['obs' => $diff['new'], 'uid' => $userId, 'aid' => $id, 'qid' => $qid]);
                $stmtLog->execute(['aid' => $id, 'qid' => $qid, 'old' => $diff['old'], 'new' => $diff['new'], 'uid' => $userId]);
                $updated++;
            }

            $this->db->commit();
            return ['ok' => true, 'updated' => $updated];
        } catch (\Throwable $e) {
            $this->safeRollback();
            $this->lastError = 'exception';
            return ['ok' => false, 'updated' => 0];
        }
    }

    public function autosaveAvaliacoes(int $auditoriaId, array $avaliacoes, int $userId): bool
    {
        $this->ensureTables();
        $auditoria = $this->find($auditoriaId);
        if (!$auditoria || !in_array((string)$auditoria['status'], ['Agendada', 'Em Auditoria'], true)) {
            return false;
        }
        try {
            if (!$this->db->beginTransaction()) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        try {
            $this->persistAvaliacoesNoTx($auditoriaId, $avaliacoes, $userId);
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by, lock_version = lock_version + 1 WHERE id = :id AND status = 'Agendada'")
                ->execute(['id' => $auditoriaId, 'updated_by' => $userId]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->safeRollback();
            return false;
        }
    }

    public function finalizarAuditoria(int $auditoriaId, array $avaliacoes, int $userId): bool
    {
        $this->ensureTables();
        $this->lastError = null;
        $auditoria = $this->find($auditoriaId);
        if (!$auditoria || !in_array((string)$auditoria['status'], ['Agendada', 'Em Auditoria'], true)) {
            return false;
        }
        try {
            if (!$this->db->beginTransaction()) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        try {
            $this->persistAvaliacoesNoTx($auditoriaId, $avaliacoes, $userId);
            $this->db->prepare("UPDATE auditorias SET status = 'Em Auditoria', updated_by = :updated_by, lock_version = lock_version + 1 WHERE id = :id AND status = 'Agendada'")
                ->execute(['id' => $auditoriaId, 'updated_by' => $userId]);
            $this->db->prepare('UPDATE auditoria_avaliacoes SET finalized_at = NOW() WHERE auditoria_id = :id')
                ->execute(['id' => $auditoriaId]);
            $stats = $this->computeConformidadeStats($auditoriaId);
            $resumo = 'Conforme: ' . $stats['conforme'] . ' | Não conforme: ' . $stats['nao_conforme'] . ' | N/A: ' . $stats['nao_aplica'];
            $up = $this->db->prepare("UPDATE auditorias
                SET status = 'Realizada', realizada_at = NOW(), avaliacao = :avaliacao, conformidade_pct = :pct, semaforo = :sem, updated_by = :updated_by,
                    lock_version = lock_version + 1
                WHERE id = :id AND deleted_at IS NULL AND realizada_at IS NULL");
            $ok = $up->execute([
                'id' => $auditoriaId,
                'avaliacao' => $resumo,
                'pct' => $stats['pct'],
                'sem' => $stats['semaforo'],
                'updated_by' => $userId
            ]) && $up->rowCount() > 0;
            if (!$ok) {
                $check = $this->find($auditoriaId);
                if ($check && ($check['status'] ?? '') === 'Realizada' && !empty($check['realizada_at'])) {
                    try { $this->db->commit(); } catch (\Throwable $e) { $this->safeRollback(); return false; }
                    return true;
                }
                $this->safeRollback();
                return false;
            }
            try {
                (new SetorMetricaModel())->registrarConclusao((int)$auditoria['setor_id'], $stats);
            } catch (\Throwable $e) {
            }
            try {
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->safeRollback();
                $check = $this->find($auditoriaId);
                if ($check && ($check['status'] ?? '') === 'Realizada' && !empty($check['realizada_at'])) {
                    return true;
                }
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->safeRollback();
            $check = $this->find($auditoriaId);
            if ($check && ($check['status'] ?? '') === 'Realizada' && !empty($check['realizada_at'])) {
                return true;
            }
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
                'responsavel_nome' => $this->joinResponsavelLabels($questao),
                'pergunta' => (string)($questao['pergunta'] ?? ''),
                'referencia_esperada' => (string)($questao['referencia_esperada'] ?? ''),
                'processos_json' => json_encode(array_values($questao['processos'] ?? []), JSON_UNESCAPED_UNICODE),
                'ordem' => $ordem++,
            ]);
            $questaoId = (int)$this->db->lastInsertId();
            $this->syncQuestaoResponsaveis($questaoId, $this->normalizeResponsavelIds($questao['responsavel_ids'] ?? []));
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

    private function detectWriteConflict(int $id, ?string $prevUpdatedAt, ?int $prevLockVersion): string
    {
        $params = ['id' => $id];
        $scope = $this->hasScopeRestriction() ? (' AND ' . $this->tenantInCondition('cliente_id', $params, 'audver')) : '';
        $stmt = $this->db->prepare("SELECT id, updated_at, lock_version FROM auditorias WHERE id = :id AND deleted_at IS NULL$scope LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return 'not_found';
        }
        if ($prevLockVersion !== null && (int)$row['lock_version'] !== $prevLockVersion) {
            return 'concurrency_conflict';
        }
        if ($prevLockVersion === null && $prevUpdatedAt !== null && (string)$row['updated_at'] !== $prevUpdatedAt) {
            return 'concurrency_conflict';
        }
        return 'unchanged_or_rule';
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
        $split = self::percentSplit($conforme, $naoConforme);
        $pct = $split['conforme'];
        $semaforo = 'vermelho';
        if ($pct >= 100) $semaforo = 'verde';
        elseif ($pct >= 75) $semaforo = 'amarelo';
        return [
            'conforme' => $conforme,
            'nao_conforme' => $naoConforme,
            'nao_aplica' => $naoAplica,
            'validas' => $validas,
            'pct' => $pct,
            'pct_conforme' => $split['conforme'],
            'pct_nao_conforme' => $split['nao_conforme'],
            'semaforo' => $semaforo,
        ];
    }

    public function responsaveisByAuditoria(int $auditoriaId): array
    {
        $this->ensureTables();
        $stmt = $this->db->prepare("SELECT ar.colaborador_id AS id, c.nome
                                    FROM auditoria_responsaveis ar
                                    JOIN colaboradores c ON c.id = ar.colaborador_id
                                    WHERE ar.auditoria_id = :id
                                    ORDER BY c.nome");
        $stmt->execute(['id' => $auditoriaId]);
        $items = $stmt->fetchAll() ?: [];
        if (!empty($items)) {
            return $items;
        }

        $legacyStmt = $this->db->prepare("SELECT c.id, c.nome
                                          FROM auditorias a
                                          JOIN colaboradores c ON c.id = a.responsavel_id
                                          WHERE a.id = :id AND a.responsavel_id IS NOT NULL
                                          LIMIT 1");
        $legacyStmt->execute(['id' => $auditoriaId]);
        $legacy = $legacyStmt->fetch();
        return $legacy ? [[
            'id' => (int)$legacy['id'],
            'nome' => (string)$legacy['nome'],
        ]] : [];
    }

    private function responsaveisByQuestaoIds(array $questaoIds): array
    {
        $this->ensureTables();
        $questaoIds = array_values(array_filter(array_map('intval', $questaoIds)));
        if (empty($questaoIds)) {
            return [];
        }
        $params = [];
        $placeholders = [];
        foreach ($questaoIds as $i => $id) {
            $key = 'qid' . $i;
            $params[$key] = $id;
            $placeholders[] = ':' . $key;
        }
        $stmt = $this->db->prepare("SELECT qr.questao_id, qr.colaborador_id AS id, c.nome
                                    FROM auditoria_questao_responsaveis qr
                                    JOIN colaboradores c ON c.id = qr.colaborador_id
                                    WHERE qr.questao_id IN (" . implode(',', $placeholders) . ")
                                    ORDER BY c.nome");
        $stmt->execute($params);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['questao_id']][] = ['id' => (int)$row['id'], 'nome' => (string)$row['nome']];
        }
        return $map;
    }

    private function syncAuditoriaResponsaveis(int $auditoriaId, array $responsavelIds): void
    {
        $this->db->prepare('DELETE FROM auditoria_responsaveis WHERE auditoria_id = :id')->execute(['id' => $auditoriaId]);
        $responsavelIds = $this->normalizeResponsavelIds($responsavelIds);
        if (empty($responsavelIds)) {
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO auditoria_responsaveis (auditoria_id, colaborador_id) VALUES (:auditoria_id, :colaborador_id)');
        foreach ($responsavelIds as $responsavelId) {
            $stmt->execute([
                'auditoria_id' => $auditoriaId,
                'colaborador_id' => $responsavelId,
            ]);
        }
    }

    private function syncLegacyResponsavelId(int $auditoriaId, array $responsavelIds): void
    {
        $responsavelIds = $this->normalizeResponsavelIds($responsavelIds);
        $this->db->prepare('UPDATE auditorias SET responsavel_id = :responsavel_id WHERE id = :id')
            ->execute([
                'id' => $auditoriaId,
                'responsavel_id' => !empty($responsavelIds) ? $responsavelIds[0] : null,
            ]);
    }

    private function syncQuestaoResponsaveis(int $questaoId, array $responsavelIds): void
    {
        $this->db->prepare('DELETE FROM auditoria_questao_responsaveis WHERE questao_id = :id')->execute(['id' => $questaoId]);
        $responsavelIds = $this->normalizeResponsavelIds($responsavelIds);
        if (empty($responsavelIds)) {
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO auditoria_questao_responsaveis (questao_id, colaborador_id) VALUES (:questao_id, :colaborador_id)');
        foreach ($responsavelIds as $responsavelId) {
            $stmt->execute([
                'questao_id' => $questaoId,
                'colaborador_id' => $responsavelId,
            ]);
        }
    }

    private function normalizeResponsavelIds(array $responsavelIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $responsavelIds), static fn($id) => $id > 0)));
    }

    private function resolveAuditoriaResponsavelIds(array $data): array
    {
        $ids = $this->normalizeResponsavelIds($data['responsavel_ids'] ?? []);
        if (!empty($ids)) {
            return $ids;
        }
        $derived = [];
        foreach (($data['questoes'] ?? []) as $questao) {
            $derived = array_merge($derived, $this->normalizeResponsavelIds($questao['responsavel_ids'] ?? []));
        }
        return $this->normalizeResponsavelIds($derived);
    }

    private function joinResponsavelLabels(array $questao): string
    {
        $labels = array_values(array_filter(array_map('trim', $questao['responsavel_labels'] ?? [])));
        if (!empty($labels)) {
            return implode(', ', array_unique($labels));
        }
        return trim((string)($questao['responsavel_nome'] ?? ''));
    }

    private function responsaveisFromNames(string $names): array
    {
        $items = [];
        foreach (explode(',', $names) as $name) {
            $label = trim($name);
            if ($label === '') {
                continue;
            }
            $items[] = ['id' => 0, 'nome' => $label];
        }
        return $items;
    }

    public function saveHistorySnapshot(int $auditoriaId, int $userId): bool
    {
        $snapshot = $this->snapshotForHistory($auditoriaId);
        if ($snapshot === null) {
            return false;
        }
        $stmt = $this->db->prepare('INSERT INTO auditoria_historico (auditoria_id, dados_anteriores, usuario_id) VALUES (:auditoria_id, :dados_anteriores, :usuario_id)');
        return $stmt->execute([
            'auditoria_id' => $auditoriaId,
            'dados_anteriores' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'usuario_id' => $userId,
        ]);
    }

    private function snapshotForHistory(int $auditoriaId): ?array
    {
        $item = $this->findWithQuestoes($auditoriaId);
        if (!$item) {
            return null;
        }
        return [
            'auditoria' => $item,
            'respostas' => $this->respostasByAuditoria($auditoriaId),
        ];
    }
}
