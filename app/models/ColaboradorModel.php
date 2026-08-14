<?php
namespace App\Models;

class ColaboradorModel extends BaseModel
{
    /**
     * Item 15b: whitelist fixa para ordenacao por cabecalho clicavel na
     * listagem de Colaboradores. O navegador so envia a chave logica (ex.:
     * "nome"); o Model e' quem decide a coluna SQL real - nenhum valor de
     * $_GET chega a ser concatenado na query.
     */
    private const SORT_COLUMNS = [
        'nome' => 'col.nome',
        'email' => 'col.email',
        'unidade' => 'cli.nome_empresa',
        'funcao' => 'f.nome',
        'setor' => 's.nome',
        'departamento' => 'd.nome',
    ];

    public static function sortableColumns(): array
    {
        return array_keys(self::SORT_COLUMNS);
    }

    /**
     * Retorna null quando a chave e' vazia/invalida - sinaliza para manter
     * a ordenacao padrao atual (hierarquica: departamento > setor > funcao >
     * nome), em vez de forcar um fallback para uma coluna especifica.
     */
    public static function normalizeSortColumn(?string $value): ?string
    {
        $value = trim((string)$value);
        return array_key_exists($value, self::SORT_COLUMNS) ? $value : null;
    }

    public static function normalizeSortDirection(?string $value): string
    {
        return strtolower(trim((string)$value)) === 'desc' ? 'desc' : 'asc';
    }

    private function buildClienteScopeClause(string $column, array $clienteIds, array &$params, string $prefix): string
    {
        $holders = [];
        foreach (array_values($clienteIds) as $i => $clienteId) {
            $key = $prefix . $i;
            $holders[] = ':' . $key;
            $params[$key] = (int)$clienteId;
        }
        return $column . ' IN (' . implode(',', $holders) . ')';
    }

    private function statusExpression(): string
    {
        if (\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            return "CASE WHEN col.ativo = 1 THEN 'ativo' ELSE 'inativo' END";
        }
        if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
            return "COALESCE(NULLIF(TRIM(col.status_atual), ''), 'ativo')";
        }
        return "'ativo'";
    }

    private function applyStatusFilter(string $status, array &$conds, array &$params): void
    {
        $status = trim($status);
        if ($status === '') {
            return;
        }
        if (\App\Database\Database::columnExists('colaboradores', 'ativo') && ($status === 'ativo' || $status === 'inativo')) {
            $conds[] = 'col.ativo = :ativo';
            $params['ativo'] = $status === 'ativo' ? 1 : 0;
            return;
        }
        if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
            $conds[] = "COALESCE(NULLIF(TRIM(col.status_atual), ''), 'ativo') = :status";
            $params['status'] = $status;
        }
    }

    private function ensureTable(): void
    {
        if ($this->db->inTransaction()) {
            return;
        }
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS colaboradores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                email VARCHAR(180) NULL,
                documento VARCHAR(20) NULL,
                data_nascimento DATE NULL,
                celular VARCHAR(15) NULL,
                funcao_id INT NOT NULL,
                lider ENUM(\'não\',\'sim\') NOT NULL DEFAULT \'não\',
                cliente_id INT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_colaboradores_cliente (cliente_id),
                INDEX idx_colaboradores_funcao (funcao_id),
                UNIQUE KEY ux_colaboradores_documento (documento),
                UNIQUE KEY ux_colaboradores_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('colaboradores', 'cliente_id')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN cliente_id INT NULL');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'lider')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN lider ENUM(\'não\',\'sim\') NOT NULL DEFAULT \'não\' AFTER funcao_id');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'ativo')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'documento')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN documento VARCHAR(20) NULL AFTER nome');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'data_nascimento')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN data_nascimento DATE NULL');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'celular')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN celular VARCHAR(15) NULL');
            }
            try { $this->db->exec('ALTER TABLE colaboradores ADD UNIQUE INDEX ux_colaboradores_documento (documento)'); } catch (\PDOException $e) {}
            try { $this->db->exec('ALTER TABLE colaboradores ADD UNIQUE INDEX ux_colaboradores_email (email)'); } catch (\PDOException $e) {}
            try { $this->db->exec('ALTER TABLE colaboradores ADD INDEX idx_colaboradores_cliente (cliente_id)'); } catch (\PDOException $e) {}
            try { $this->db->exec('ALTER TABLE colaboradores ADD INDEX idx_colaboradores_funcao (funcao_id)'); } catch (\PDOException $e) {}
        } catch (\PDOException $e) {}
    }

    public function allByCliente(int $clienteId): array
    {
        $this->ensureTable();
        $sql = 'SELECT col.id, col.nome, col.email, col.funcao_id, col.cliente_id,
                       cli.nome_empresa AS unidade, f.nome AS funcao, s.nome AS setor, d.nome AS departamento
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN clientes cli ON cli.id = col.cliente_id
                WHERE col.cliente_id = :cid
                ORDER BY d.nome, s.nome, f.nome, col.nome';
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('col.cliente_id', $params, 'cabc');
        $sql = str_replace('WHERE col.cliente_id = :cid', 'WHERE col.cliente_id = :cid AND ' . $scope, $sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByCliente(int $clienteId): int
    {
        $this->ensureTable();
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cc');
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM colaboradores WHERE cliente_id = :cid AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function countByClienteWithFilters(int $clienteId, array $filters): int
    {
        $this->ensureTable();
        $conds = ['col.cliente_id = :cid'];
        $params = ['cid' => $clienteId];
        $conds[] = $this->tenantInCondition('col.cliente_id', $params, 'ccf');
        if (!empty($filters['lider'])) { $conds[] = 'col.lider = :lider'; $params['lider'] = $filters['lider']; }
        if (!empty($filters['departamento_id'])) { $conds[] = 'd.id = :dep'; $params['dep'] = (int)$filters['departamento_id']; }
        if (!empty($filters['setor_id'])) { $conds[] = 's.id = :setor'; $params['setor'] = (int)$filters['setor_id']; }
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
        if (!empty($filters['status'])) { $this->applyStatusFilter((string)$filters['status'], $conds, $params); }
        $sql = 'SELECT COUNT(*) AS total
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE ' . implode(' AND ', $conds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function countByClientesWithFilters(array $clienteIds, array $filters): int
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return 0;
        }
        $params = [];
        $conds = [$this->buildClienteScopeClause('col.cliente_id', $clienteIds, $params, 'ccmf_scope')];
        $conds[] = $this->tenantInCondition('col.cliente_id', $params, 'ccmf_tenant');
        if (!empty($filters['lider'])) { $conds[] = 'col.lider = :lider'; $params['lider'] = $filters['lider']; }
        if (!empty($filters['departamento_id'])) { $conds[] = 'd.id = :dep'; $params['dep'] = (int)$filters['departamento_id']; }
        if (!empty($filters['setor_id'])) { $conds[] = 's.id = :setor'; $params['setor'] = (int)$filters['setor_id']; }
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
        if (!empty($filters['status'])) { $this->applyStatusFilter((string)$filters['status'], $conds, $params); }
        $sql = 'SELECT COUNT(DISTINCT col.id) AS total
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE ' . implode(' AND ', $conds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function paginatedByCliente(int $clienteId, int $page, int $perPage): array
    {
        $this->ensureTable();
        $page = max(1, (int)$page);
        $perPage = max(1, min(200, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT col.id, col.nome, col.email, col.funcao_id, col.cliente_id,
                       cli.nome_empresa AS unidade, f.nome AS funcao, s.nome AS setor, d.nome AS departamento
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN clientes cli ON cli.id = col.cliente_id
                WHERE col.cliente_id = :cid
                ORDER BY d.nome, s.nome, f.nome, col.nome
                LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function paginatedByClienteWithFilters(int $clienteId, int $page, int $perPage, array $filters): array
    {
        $this->ensureTable();
        $page = max(1, (int)$page);
        $perPage = max(1, min(200, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $conds = ['col.cliente_id = :cid'];
        $params = ['cid' => $clienteId];
        $conds[] = $this->tenantInCondition('col.cliente_id', $params, 'cpf');
        if (!empty($filters['lider'])) { $conds[] = 'col.lider = :lider'; $params['lider'] = $filters['lider']; }
        if (!empty($filters['departamento_id'])) { $conds[] = 'd.id = :dep'; $params['dep'] = (int)$filters['departamento_id']; }
        if (!empty($filters['setor_id'])) { $conds[] = 's.id = :setor'; $params['setor'] = (int)$filters['setor_id']; }
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
        if (!empty($filters['status'])) { $this->applyStatusFilter((string)$filters['status'], $conds, $params); }
        $sql = 'SELECT col.id, col.nome, col.email, col.funcao_id, col.cliente_id,
                       cli.nome_empresa AS unidade, f.nome AS funcao, s.nome AS setor, d.nome AS departamento
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN clientes cli ON cli.id = col.cliente_id
                WHERE ' . implode(' AND ', $conds) . '
                ORDER BY d.nome, s.nome, f.nome, col.nome
                LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function paginatedByClientesWithFilters(array $clienteIds, int $page, int $perPage, array $filters, ?string $sort = null, ?string $dir = null): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        $page = max(1, (int)$page);
        $perPage = max(1, min(200, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $conds = [$this->buildClienteScopeClause('col.cliente_id', $clienteIds, $params, 'cpmf_scope')];
        $conds[] = $this->tenantInCondition('col.cliente_id', $params, 'cpmf_tenant');
        if (!empty($filters['lider'])) { $conds[] = 'col.lider = :lider'; $params['lider'] = $filters['lider']; }
        if (!empty($filters['departamento_id'])) { $conds[] = 'd.id = :dep'; $params['dep'] = (int)$filters['departamento_id']; }
        if (!empty($filters['setor_id'])) { $conds[] = 's.id = :setor'; $params['setor'] = (int)$filters['setor_id']; }
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
        if (!empty($filters['status'])) { $this->applyStatusFilter((string)$filters['status'], $conds, $params); }

        $orderBy = 'd.nome, s.nome, f.nome, col.nome';
        $sortKey = self::normalizeSortColumn($sort);
        if ($sortKey !== null) {
            $direction = self::normalizeSortDirection($dir) === 'desc' ? 'DESC' : 'ASC';
            $orderBy = self::SORT_COLUMNS[$sortKey] . ' ' . $direction . ', col.id ' . $direction;
        }

        $sql = 'SELECT DISTINCT col.id, col.nome, col.email, col.funcao_id, col.cliente_id,
                       cli.nome_empresa AS unidade, f.nome AS funcao, s.nome AS setor, d.nome AS departamento
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                LEFT JOIN clientes cli ON cli.id = col.cliente_id
                WHERE ' . implode(' AND ', $conds) . '
                ORDER BY ' . $orderBy . '
                LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cf');
        $cols = ['id', 'nome', 'email', 'funcao_id', 'cliente_id', 'lider'];
        if (\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            $cols[] = 'ativo';
        }
        if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
            $cols[] = 'status_atual';
        }
        if (\App\Database\Database::columnExists('colaboradores', 'matricula')) {
            $cols[] = 'matricula';
        }
        if (\App\Database\Database::columnExists('colaboradores', 'cpf')) {
            $cols[] = 'cpf';
        }
        if (\App\Database\Database::columnExists('colaboradores', 'data_admissao')) {
            $cols[] = 'data_admissao';
        }
        $stmt = $this->db->prepare("SELECT " . implode(', ', $cols) . " FROM colaboradores WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function changeAtivoWithAudit(int $id, int $newAtivo, int $userId, string $reason, ?string $ip = null, ?string $userAgent = null): array
    {
        $this->ensureTable();
        if (!\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            return ['ok' => false, 'message' => 'Coluna de status (ativo) não encontrada.'];
        }
        if (!\App\Database\Database::tableExists('colaborador_status_logs')) {
            return ['ok' => false, 'message' => 'Tabela de auditoria de status (colaborador_status_logs) não encontrada.'];
        }
        $id = (int)$id;
        $newAtivo = $newAtivo ? 1 : 0;
        $userId = (int)$userId;
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Justificativa obrigatória (mínimo 5 caracteres).'];
        }

        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'csc');

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT id, cliente_id, ativo FROM colaboradores WHERE id = :id AND $scope FOR UPDATE");
            $stmt->execute($params);
            $current = $stmt->fetch();
            if (!$current) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Colaborador não encontrado.'];
            }
            $oldAtivo = (int)($current['ativo'] ?? 1);
            if ($oldAtivo === $newAtivo) {
                $this->db->rollBack();
                return ['ok' => true, 'message' => 'Nenhuma alteração de status necessária.'];
            }

            if ($newAtivo === 0) {
                $blocks = $this->deactivationBlocks($id);
                if (!empty($blocks)) {
                    $this->db->rollBack();
                    return ['ok' => false, 'message' => implode(' ', $blocks)];
                }
            } else {
                $clienteId = (int)($current['cliente_id'] ?? 0);
                if ($clienteId > 0 && \App\Database\Database::columnExists('clientes', 'ativo')) {
                    $stmtCli = $this->db->prepare('SELECT ativo FROM clientes WHERE id = :id LIMIT 1');
                    $stmtCli->execute(['id' => $clienteId]);
                    $cliAtivo = $stmtCli->fetchColumn();
                    if ($cliAtivo !== false && (int)$cliAtivo !== 1) {
                        $this->db->rollBack();
                        return ['ok' => false, 'message' => 'Não é possível ativar colaborador em uma unidade inativa.'];
                    }
                }
            }

            $stmtUp = $this->db->prepare("UPDATE colaboradores SET ativo = :a WHERE id = :id AND $scope");
            $ok = $stmtUp->execute(['a' => $newAtivo, 'id' => $id] + array_diff_key($params, ['id' => true]));
            if (!$ok) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Não foi possível atualizar o status do colaborador.'];
            }

            if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
                $stmtSa = $this->db->prepare("UPDATE colaboradores SET status_atual = :s WHERE id = :id AND $scope AND (status_atual IS NULL OR TRIM(status_atual) = '' OR LOWER(TRIM(status_atual)) IN ('ativo','inativo'))");
                $stmtSa->execute(['s' => $newAtivo === 1 ? 'ativo' : 'inativo', 'id' => $id] + array_diff_key($params, ['id' => true]));
            }

            $stmtLog = $this->db->prepare('INSERT INTO colaborador_status_logs (colaborador_id, cliente_id, old_ativo, new_ativo, justificativa, changed_by, ip, user_agent) VALUES (:col, :cid, :old, :new, :j, :uid, :ip, :ua)');
            $stmtLog->execute([
                'col' => $id,
                'cid' => (int)($current['cliente_id'] ?? 0) ?: null,
                'old' => $oldAtivo,
                'new' => $newAtivo,
                'j' => $reason,
                'uid' => $userId > 0 ? $userId : null,
                'ip' => $ip !== null && trim($ip) !== '' ? mb_substr($ip, 0, 45) : null,
                'ua' => $userAgent !== null && trim($userAgent) !== '' ? mb_substr($userAgent, 0, 255) : null,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $newAtivo === 1 ? 'Colaborador ativado com sucesso.' : 'Colaborador inativado com sucesso.'];
        } catch (\Throwable $e) {
            try { if ($this->db->inTransaction()) $this->db->rollBack(); } catch (\Throwable $e2) {}
            return ['ok' => false, 'message' => 'Erro interno ao atualizar status.'];
        }
    }

    private function deactivationBlocks(int $colaboradorId): array
    {
        $blocks = [];
        $colaboradorId = (int)$colaboradorId;
        if ($colaboradorId <= 0) {
            return ['Colaborador inválido.'];
        }

        if (\App\Database\Database::tableExists('treinamento_colaboradores')) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM treinamento_colaboradores WHERE colaborador_id = :id AND status = 'pendente'");
            $stmt->execute(['id' => $colaboradorId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: existem treinamentos pendentes vinculados ao colaborador.';
            }
        }
        if (\App\Database\Database::tableExists('treinamento_participantes') && \App\Database\Database::tableExists('treinamentos_agenda')) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM treinamento_participantes tp JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id WHERE tp.colaborador_id = :id AND ta.data >= NOW()');
            $stmt->execute(['id' => $colaboradorId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: o colaborador possui participação em agendamentos futuros.';
            }
        }
        if (\App\Database\Database::tableExists('indicador_responsavel') && \App\Database\Database::tableExists('indicadores')) {
            $cond = '1=1';
            if (\App\Database\Database::columnExists('indicadores', 'deleted_at')) {
                $cond = 'i.deleted_at IS NULL';
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM indicador_responsavel ir JOIN indicadores i ON i.id = ir.indicador_id WHERE ir.colaborador_id = :id AND $cond");
            $stmt->execute(['id' => $colaboradorId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: o colaborador está vinculado como responsável em indicadores ativos.';
            }
        }
        return $blocks;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if (($data['cliente_id'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['cliente_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO colaboradores (nome, email, documento, data_nascimento, celular, funcao_id, lider, cliente_id, ativo) VALUES (:nome, :email, :documento, :data_nascimento, :celular, :funcao_id, :lider, :cliente_id, :ativo)');
        $stmt->execute([
            'nome' => $data['nome'],
            'email' => isset($data['email']) && $data['email'] !== '' ? $data['email'] : null,
            'documento' => isset($data['documento']) && $data['documento'] !== '' ? $data['documento'] : null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'celular' => isset($data['celular']) && $data['celular'] !== '' ? $data['celular'] : null,
            'funcao_id' => (int)$data['funcao_id'],
            'lider' => ($data['lider'] ?? 'não') === 'sim' ? 'sim' : 'não',
            'cliente_id' => $data['cliente_id'] ?? null,
            'ativo' => isset($data['ativo']) ? (int)(bool)$data['ativo'] : 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        $params = ['id' => (int)$id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cu');

        $set = [];
        if (array_key_exists('nome', $data)) { $set[] = 'nome = :nome'; $params['nome'] = $data['nome']; }
        if (array_key_exists('email', $data)) { $set[] = 'email = :email'; $params['email'] = $data['email'] !== '' ? $data['email'] : null; }
        if (array_key_exists('documento', $data) && \App\Database\Database::columnExists('colaboradores', 'documento')) { $set[] = 'documento = :documento'; $params['documento'] = $data['documento'] !== '' ? $data['documento'] : null; }
        if (array_key_exists('data_nascimento', $data) && \App\Database\Database::columnExists('colaboradores', 'data_nascimento')) { $set[] = 'data_nascimento = :data_nascimento'; $params['data_nascimento'] = $data['data_nascimento'] !== '' ? $data['data_nascimento'] : null; }
        if (array_key_exists('celular', $data) && \App\Database\Database::columnExists('colaboradores', 'celular')) { $set[] = 'celular = :celular'; $params['celular'] = $data['celular'] !== '' ? $data['celular'] : null; }
        if (array_key_exists('funcao_id', $data)) { $set[] = 'funcao_id = :funcao_id'; $params['funcao_id'] = (int)$data['funcao_id']; }
        if (array_key_exists('lider', $data) && \App\Database\Database::columnExists('colaboradores', 'lider')) { $set[] = 'lider = :lider'; $params['lider'] = ($data['lider'] ?? 'não') === 'sim' ? 'sim' : 'não'; }
        if (array_key_exists('cliente_id', $data) && \App\Database\Database::columnExists('colaboradores', 'cliente_id')) { $set[] = 'cliente_id = :cliente_id'; $params['cliente_id'] = (int)($data['cliente_id'] ?? 0) ?: null; }
        if (array_key_exists('ativo', $data) && \App\Database\Database::columnExists('colaboradores', 'ativo')) { $set[] = 'ativo = :ativo'; $params['ativo'] = (int)(bool)$data['ativo']; }
        if (array_key_exists('matricula', $data) && \App\Database\Database::columnExists('colaboradores', 'matricula')) { $set[] = 'matricula = :matricula'; $params['matricula'] = $data['matricula'] !== '' ? $data['matricula'] : null; }
        if (array_key_exists('cpf', $data) && \App\Database\Database::columnExists('colaboradores', 'cpf')) { $set[] = 'cpf = :cpf'; $params['cpf'] = $data['cpf'] !== '' ? $data['cpf'] : null; }
        if (array_key_exists('data_admissao', $data) && \App\Database\Database::columnExists('colaboradores', 'data_admissao')) { $set[] = 'data_admissao = :data_admissao'; $params['data_admissao'] = $data['data_admissao'] !== '' ? $data['data_admissao'] : null; }
        if (array_key_exists('status_atual', $data) && \App\Database\Database::columnExists('colaboradores', 'status_atual')) { $set[] = 'status_atual = :status_atual'; $params['status_atual'] = $data['status_atual'] !== '' ? $data['status_atual'] : 'ativo'; }

        if (empty($set)) {
            return true;
        }
        $stmt = $this->db->prepare("UPDATE colaboradores SET " . implode(', ', $set) . " WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function updateWithStatusAudit(int $id, array $data, int $newAtivo, int $userId, string $reason, ?string $ip = null, ?string $userAgent = null): array
    {
        $this->ensureTable();
        if (!\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            return ['ok' => false, 'message' => 'Coluna de status (ativo) não encontrada.'];
        }
        if (!\App\Database\Database::tableExists('colaborador_status_logs')) {
            return ['ok' => false, 'message' => 'Tabela de auditoria de status (colaborador_status_logs) não encontrada.'];
        }

        $id = (int)$id;
        $newAtivo = $newAtivo ? 1 : 0;
        $userId = (int)$userId;
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Justificativa obrigatória (mínimo 5 caracteres).'];
        }

        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'usau');
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT id, cliente_id, ativo FROM colaboradores WHERE id = :id AND $scope FOR UPDATE");
            $stmt->execute($params);
            $current = $stmt->fetch();
            if (!$current) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Colaborador não encontrado.'];
            }
            $oldAtivo = (int)($current['ativo'] ?? 1);
            if ($oldAtivo === $newAtivo) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Status não alterado.'];
            }

            if ($newAtivo === 0) {
                $blocks = $this->deactivationBlocks($id);
                if (!empty($blocks)) {
                    $this->db->rollBack();
                    return ['ok' => false, 'message' => implode(' ', $blocks)];
                }
            } else {
                $clienteId = (int)($current['cliente_id'] ?? 0);
                if ($clienteId > 0 && \App\Database\Database::columnExists('clientes', 'ativo')) {
                    $stmtCli = $this->db->prepare('SELECT ativo FROM clientes WHERE id = :id LIMIT 1');
                    $stmtCli->execute(['id' => $clienteId]);
                    $cliAtivo = $stmtCli->fetchColumn();
                    if ($cliAtivo !== false && (int)$cliAtivo !== 1) {
                        $this->db->rollBack();
                        return ['ok' => false, 'message' => 'Não é possível ativar colaborador em uma unidade inativa.'];
                    }
                }
            }

            $data['ativo'] = $newAtivo;
            $ok = $this->update($id, $data);
            if (!$ok) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Não foi possível salvar o colaborador.'];
            }

            if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
                $stmtSa = $this->db->prepare("UPDATE colaboradores SET status_atual = :s WHERE id = :id AND $scope AND (status_atual IS NULL OR TRIM(status_atual) = '' OR LOWER(TRIM(status_atual)) IN ('ativo','inativo'))");
                $stmtSa->execute(['s' => $newAtivo === 1 ? 'ativo' : 'inativo', 'id' => $id] + array_diff_key($params, ['id' => true]));
            }

            $stmtLog = $this->db->prepare('INSERT INTO colaborador_status_logs (colaborador_id, cliente_id, old_ativo, new_ativo, justificativa, changed_by, ip, user_agent) VALUES (:col, :cid, :old, :new, :j, :uid, :ip, :ua)');
            $stmtLog->execute([
                'col' => $id,
                'cid' => (int)($current['cliente_id'] ?? 0) ?: null,
                'old' => $oldAtivo,
                'new' => $newAtivo,
                'j' => $reason,
                'uid' => $userId > 0 ? $userId : null,
                'ip' => $ip !== null && trim($ip) !== '' ? mb_substr($ip, 0, 45) : null,
                'ua' => $userAgent !== null && trim($userAgent) !== '' ? mb_substr($userAgent, 0, 255) : null,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $newAtivo === 1 ? 'Colaborador ativado com sucesso.' : 'Colaborador inativado com sucesso.'];
        } catch (\Throwable $e) {
            try { if ($this->db->inTransaction()) $this->db->rollBack(); } catch (\Throwable $e2) {}
            return ['ok' => false, 'message' => 'Erro interno ao salvar colaborador.'];
        }
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cd');
        $stmt = $this->db->prepare("DELETE FROM colaboradores WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function searchByClienteName(int $clienteId, string $q, int $limit = 10): array
    {
        $this->ensureTable();
        $q = trim($q);
        if ($q === '') { return []; }
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cs');
        $stmt = $this->db->prepare("SELECT id, nome, email FROM colaboradores WHERE cliente_id = :cid AND $scope AND nome LIKE :q ORDER BY nome LIMIT :lim");
        $like = '%' . $q . '%';
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':q', $like, \PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchActiveByCliente(int $clienteId, string $q, int $limit = 15): array
    {
        $this->ensureTable();
        $q = trim($q);
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('col.cliente_id', $params, 'csrc');
        $sql = "SELECT col.id, col.nome, col.email, col.cliente_id, f.id AS funcao_id, s.id AS setor_id
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                WHERE col.cliente_id = :cid AND $scope";
        if (\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            $sql .= ' AND col.ativo = 1';
        }
        if ($q !== '') {
            $sql .= ' AND (col.nome LIKE :q OR col.email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY col.nome LIMIT :lim';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, $k === 'q' ? \PDO::PARAM_STR : \PDO::PARAM_INT);
        }
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function activeByIdsCliente(array $ids, int $clienteId): array
    {
        $this->ensureTable();
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($clienteId <= 0 || empty($ids)) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('col.cliente_id', $params, 'caids');
        $placeholders = [];
        foreach ($ids as $i => $id) {
            $key = 'id' . $i;
            $params[$key] = $id;
            $placeholders[] = ':' . $key;
        }
        $sql = "SELECT col.id, col.nome, col.email
                FROM colaboradores col
                WHERE col.cliente_id = :cid AND $scope";
        if (\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            $sql .= ' AND col.ativo = 1';
        }
        $sql .= ' AND col.id IN (' . implode(',', $placeholders) . ')
                  ORDER BY col.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByIdsCliente(array $ids, int $clienteId): array
    {
        $this->ensureTable();
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($clienteId <= 0 || empty($ids)) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('col.cliente_id', $params, 'cfids');
        $placeholders = [];
        foreach ($ids as $i => $id) {
            $key = 'id' . $i;
            $params[$key] = $id;
            $placeholders[] = ':' . $key;
        }
        $stmt = $this->db->prepare("SELECT col.id, col.nome, col.email
            FROM colaboradores col
            WHERE col.cliente_id = :cid AND $scope AND col.id IN (" . implode(',', $placeholders) . ')
            ORDER BY col.nome');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allBySetor(int $setorId, ?int $clienteId = null): array
    {
        $this->ensureTable();
        $params = ['sid' => $setorId];
        $conds = ['s.id = :sid'];
        if ($clienteId !== null && $clienteId > 0) {
            $conds[] = 'col.cliente_id = :cid';
            $params['cid'] = $clienteId;
        }
        $conds[] = $this->tenantInCondition('col.cliente_id', $params, 'cset');
        $sql = 'SELECT col.id, col.nome, col.email, col.cliente_id, f.id AS funcao_id, s.id AS setor_id
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                WHERE ' . implode(' AND ', $conds) . '
                ORDER BY col.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function searchActiveBySetor(int $setorId, int $clienteId, string $q, int $limit = 15): array
    {
        $this->ensureTable();
        $q = trim($q);
        if ($setorId <= 0 || $clienteId <= 0 || $q === '') {
            return [];
        }
        $params = [
            'sid' => $setorId,
            'cid' => $clienteId,
            'q' => '%' . $q . '%',
            'lim' => max(1, min(30, $limit)),
        ];
        $conds = [
            's.id = :sid',
            'col.cliente_id = :cid',
            $this->tenantInCondition('col.cliente_id', $params, 'cauto'),
        ];
        if (\App\Database\Database::columnExists('clientes', 'ativo')) {
            $conds[] = 'cli.ativo = 1';
        }
        $sql = 'SELECT col.id, col.nome
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN clientes cli ON cli.id = col.cliente_id
                WHERE ' . implode(' AND ', $conds) . '
                  AND col.nome LIKE :q
                ORDER BY col.nome
                LIMIT :lim';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = in_array($key, ['sid', 'cid', 'lim'], true) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function existsActiveNomeBySetor(int $setorId, int $clienteId, string $nome): bool
    {
        $this->ensureTable();
        $nome = trim($nome);
        if ($setorId <= 0 || $clienteId <= 0 || $nome === '') {
            return false;
        }
        $params = [
            'sid' => $setorId,
            'cid' => $clienteId,
            'nome' => mb_strtolower($nome),
        ];
        $conds = [
            's.id = :sid',
            'col.cliente_id = :cid',
            $this->tenantInCondition('col.cliente_id', $params, 'cex'),
            'LOWER(TRIM(col.nome)) = :nome',
        ];
        if (\App\Database\Database::columnExists('clientes', 'ativo')) {
            $conds[] = 'cli.ativo = 1';
        }
        $sql = 'SELECT col.id
                FROM colaboradores col
                JOIN funcoes f ON f.id = col.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN clientes cli ON cli.id = col.cliente_id
                WHERE ' . implode(' AND ', $conds) . '
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function statusOptionsByClientes(array $clienteIds): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        $params = [];
        $conds = [
            $this->buildClienteScopeClause('col.cliente_id', $clienteIds, $params, 'csoc_scope'),
            $this->tenantInCondition('col.cliente_id', $params, 'csoc_tenant'),
        ];
        $out = [];
        if (\App\Database\Database::columnExists('colaboradores', 'ativo')) {
            $out[] = 'ativo';
            $out[] = 'inativo';
        }
        if (\App\Database\Database::columnExists('colaboradores', 'status_atual')) {
            $sql = 'SELECT DISTINCT COALESCE(NULLIF(TRIM(col.status_atual), \'\'), \'ativo\') AS status_value
                    FROM colaboradores col
                    WHERE ' . implode(' AND ', $conds) . '
                    ORDER BY status_value
                    LIMIT 30';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            foreach (($stmt->fetchAll() ?: []) as $row) {
                $val = trim((string)($row['status_value'] ?? ''));
                if ($val !== '') {
                    $out[] = $val;
                }
            }
        }
        return array_values(array_unique(array_values(array_filter($out, static fn($v) => trim((string)$v) !== ''))));
    }
}
