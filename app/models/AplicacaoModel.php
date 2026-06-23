<?php
namespace App\Models;

class AplicacaoModel extends BaseModel
{
    private function scopedApplicationClienteId(int $aplicacaoId): ?int
    {
        $aplicacaoId = (int)$aplicacaoId;
        if ($aplicacaoId <= 0) {
            return null;
        }
        $params = ['id' => $aplicacaoId];
        $scope = $this->tenantInCondition('id_cliente', $params, 'apcl');
        $stmt = $this->db->prepare("SELECT id_cliente FROM aplicacoes WHERE id = :id AND $scope LIMIT 1");
        $stmt->execute($params);
        $clienteId = $stmt->fetchColumn();
        return $clienteId !== false ? (int)$clienteId : null;
    }

    /**
     * @param array<int> $funcaoIds
     * @return array<int>
     */
    private function filterAplicacaoFuncaoIds(int $aplicacaoId, array $funcaoIds): array
    {
        $clienteId = $this->scopedApplicationClienteId($aplicacaoId);
        if ($clienteId === null || $clienteId <= 0) {
            return [];
        }
        $valid = [];
        foreach (array_values(array_unique(array_filter(array_map('intval', $funcaoIds)))) as $funcaoId) {
            if ($this->funcaoBelongsToCatalogCliente($funcaoId, $clienteId)) {
                $valid[] = $funcaoId;
            }
        }
        return $valid;
    }

    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                id_metodologia INT NOT NULL,
                status ENUM('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado',
                consultor_id INT NULL,
                data_prevista DATE NULL,
                data_conclusao DATE NULL,
                funcao_id INT NULL,
                CONSTRAINT fk_apl_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
                CONSTRAINT fk_apl_metodologia FOREIGN KEY (id_metodologia) REFERENCES metodologias(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!\App\Database\Database::columnExists('aplicacoes', 'consultor_id')) {
                $this->db->exec('ALTER TABLE aplicacoes ADD COLUMN consultor_id INT NULL');
            }
            if (!\App\Database\Database::columnExists('aplicacoes', 'data_prevista')) {
                $this->db->exec('ALTER TABLE aplicacoes ADD COLUMN data_prevista DATE NULL');
            }
            if (!\App\Database\Database::columnExists('aplicacoes', 'data_conclusao')) {
                $this->db->exec('ALTER TABLE aplicacoes ADD COLUMN data_conclusao DATE NULL');
            }
            if (!\App\Database\Database::columnExists('aplicacoes', 'funcao_id')) {
                $this->db->exec('ALTER TABLE aplicacoes ADD COLUMN funcao_id INT NULL');
            }
            $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_funcoes (
                aplicacao_id INT NOT NULL,
                funcao_id INT NOT NULL,
                PRIMARY KEY (aplicacao_id, funcao_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_colaboradores (
                aplicacao_id INT NOT NULL,
                colaborador_id INT NOT NULL,
                PRIMARY KEY (aplicacao_id, colaborador_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            try {
                $this->db->exec("UPDATE aplicacoes SET status = 'Planejado' WHERE status = 'A Fazer'");
                $this->db->exec("ALTER TABLE aplicacoes MODIFY COLUMN status ENUM('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado'");
            } catch (\PDOException $e) {}
        } catch (\PDOException $e) {}
    }

    public function addUpdate(int $aplicacaoId, string $userEmail, ?string $userNome, string $summary, array $payload = []): bool
    {
        $this->ensureTable();
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_updates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aplicacao_id INT NOT NULL,
                user_email VARCHAR(255) NULL,
                user_nome VARCHAR(255) NULL,
                summary TEXT NOT NULL,
                payload_json TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $this->db->prepare('INSERT INTO aplicacao_updates (aplicacao_id, user_email, user_nome, summary, payload_json) VALUES (:ap, :em, :nm, :su, :pl)');
            return $stmt->execute([
                'ap' => $aplicacaoId,
                'em' => $userEmail,
                'nm' => $userNome,
                'su' => $summary,
                'pl' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\PDOException $e) { return false; }
    }
    public function updatesForAplicacao(int $aplicacaoId): array
    {
        $this->ensureTable();
        $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aplicacao_id INT NOT NULL,
            user_email VARCHAR(255) NULL,
            user_nome VARCHAR(255) NULL,
            summary TEXT NOT NULL,
            payload_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $this->db->prepare('SELECT id, user_email, user_nome, summary, payload_json, created_at FROM aplicacao_updates WHERE aplicacao_id = :id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['id' => $aplicacaoId]);
        return $stmt->fetchAll();
    }
    public function deleteUpdate(int $id, int $aplicacaoId): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM aplicacao_updates WHERE id = :id AND aplicacao_id = :ap');
        return $stmt->execute(['id' => $id, 'ap' => $aplicacaoId]);
    }

    public function addArquivo(int $aplicacaoId, int $clienteId, string $nomeOriginal, string $path, string $mime, int $size): bool
    {
        $clienteId = (int)$this->normalizeScopedClienteId($clienteId);
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return false;
        }
        $this->ensureTable();
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_arquivos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aplicacao_id INT NOT NULL,
                cliente_id INT NOT NULL,
                nome_original VARCHAR(255) NOT NULL,
                arquivo_path VARCHAR(255) NOT NULL,
                mime VARCHAR(100) NOT NULL,
                tamanho INT NOT NULL,
                uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $this->db->prepare('INSERT INTO aplicacao_arquivos (aplicacao_id, cliente_id, nome_original, arquivo_path, mime, tamanho) VALUES (:ap, :cl, :no, :pa, :mi, :ta)');
            return $stmt->execute([
                'ap' => $aplicacaoId,
                'cl' => $clienteId,
                'no' => $nomeOriginal,
                'pa' => $path,
                'mi' => $mime,
                'ta' => $size,
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function arquivosForAplicacao(int $aplicacaoId): array
    {
        $this->ensureTable();
        $this->db->exec("CREATE TABLE IF NOT EXISTS aplicacao_arquivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aplicacao_id INT NOT NULL,
            cliente_id INT NOT NULL,
            nome_original VARCHAR(255) NOT NULL,
            arquivo_path VARCHAR(255) NOT NULL,
            mime VARCHAR(100) NOT NULL,
            tamanho INT NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $params = ['id' => $aplicacaoId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'afa');
        $stmt = $this->db->prepare("SELECT id, nome_original, arquivo_path, mime, tamanho, uploaded_at FROM aplicacao_arquivos WHERE aplicacao_id = :id AND $scope ORDER BY uploaded_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateAssignment(int $idAplicacao, ?int $funcaoId): bool
    {
        try {
            if (!\App\Database\Database::columnExists('aplicacoes', 'funcao_id')) {
                $this->db->exec('ALTER TABLE aplicacoes ADD COLUMN funcao_id INT NULL');
            }
            $clienteId = $this->scopedApplicationClienteId($idAplicacao);
            if ($clienteId === null || $clienteId <= 0) {
                return false;
            }
            if ($funcaoId) {
                if (!$this->funcaoBelongsToCatalogCliente($funcaoId, $clienteId)) {
                    return false;
                }
                $chk = $this->db->prepare('SELECT 1 FROM aplicacao_funcoes WHERE aplicacao_id = :ap AND funcao_id = :fn');
                $chk->execute(['ap' => $idAplicacao, 'fn' => $funcaoId]);
                if (!$chk->fetch()) { return false; }
            }
            $params = ['funcao_id' => $funcaoId, 'id' => $idAplicacao];
            $scope = $this->tenantInCondition('id_cliente', $params, 'aua');
            $stmt = $this->db->prepare("UPDATE aplicacoes SET funcao_id = :funcao_id WHERE id = :id AND $scope");
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            return false;
        }
    }
    public function byCliente(int $idCliente): array
    {
        $this->ensureTable();
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');
        $hasTipoCol = \App\Database\Database::columnExists('metodologias', 'tipo');
        $hasArquivoCol = \App\Database\Database::columnExists('metodologias', 'arquivo_path');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, p.nome' : 'ORDER BY p.nome';
        $selectTipo = $hasTipoCol ? 'm.tipo' : 'NULL AS tipo';
        $selectArquivo = $hasArquivoCol ? 'm.arquivo_path' : 'NULL AS arquivo_path';

        $hasColabTbl = \App\Database\Database::tableExists('colaboradores');
        $selectColabs = $hasColabTbl ? "GROUP_CONCAT(DISTINCT col.nome ORDER BY col.nome SEPARATOR ', ') AS colabs_vinculados" : "NULL AS colabs_vinculados";
        $joinColabs = $hasColabTbl ? "LEFT JOIN aplicacao_colaboradores ac ON ac.aplicacao_id = a.id LEFT JOIN colaboradores col ON col.id = ac.colaborador_id" : "";
        $sql = "SELECT a.id, a.status, a.id_metodologia, a.id_cliente, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, $selectTipo, $selectArquivo, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons,
                       $selectColabs
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinColabs
                $joinCons
                WHERE a.id_cliente = :id_cliente
                GROUP BY a.id
                $order";
        $params = ['id_cliente' => $idCliente];
        $scope = $this->tenantInCondition('a.id_cliente', $params, 'abc');
        $sql = str_replace('WHERE a.id_cliente = :id_cliente', 'WHERE a.id_cliente = :id_cliente AND ' . $scope, $sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $this->ensureTable();
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');
        $hasTipoCol = \App\Database\Database::columnExists('metodologias', 'tipo');
        $hasArquivoCol = \App\Database\Database::columnExists('metodologias', 'arquivo_path');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, cli.nome_empresa, p.nome' : 'ORDER BY cli.nome_empresa, p.nome';
        $selectTipo = $hasTipoCol ? 'm.tipo' : 'NULL AS tipo';
        $selectArquivo = $hasArquivoCol ? 'm.arquivo_path' : 'NULL AS arquivo_path';

        $params = [];
        $scope = $this->tenantInCondition('a.id_cliente', $params, 'aal');
        $sql = "SELECT a.id, a.status, a.id_metodologia, a.id_cliente, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, $selectTipo, $selectArquivo, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                WHERE $scope
                $order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statsByPilar(?int $idCliente = null): array
    {
        $this->ensureTable();
        $where = '';
        $params = [];
        if ($idCliente !== null) {
            $where = 'WHERE a.id_cliente = :id_cliente';
            $params['id_cliente'] = $idCliente;
        }
        $scope = $this->tenantInCondition('a.id_cliente', $params, 'asp');
        if ($where === '') {
            $where = 'WHERE ' . $scope;
        } else {
            $where .= ' AND ' . $scope;
        }
        $sql = "SELECT p.nome AS pilar, a.status, COUNT(*) AS total
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                $where
                GROUP BY p.nome, a.status
                ORDER BY p.nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(int $idCliente, int $idMetodologia, string $status, ?int $consultorId = null, ?string $dataPrevista = null): int
    {
        $idCliente = (int)$this->normalizeScopedClienteId($idCliente);
        if ($idCliente <= 0 || !$this->canAccessClienteId($idCliente)) {
            return 0;
        }
        try {
            $this->ensureTable();
            $stmt = $this->db->prepare('INSERT INTO aplicacoes (id_cliente, id_metodologia, status, consultor_id, data_prevista) VALUES (:id_cliente, :id_metodologia, :status, :consultor_id, :data_prevista)');
            $stmt->execute([
                'id_cliente' => $idCliente,
                'id_metodologia' => $idMetodologia,
                'status' => $status,
                'consultor_id' => $consultorId,
                'data_prevista' => $dataPrevista,
            ]);
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare('INSERT INTO aplicacoes (id_cliente, id_metodologia, status) VALUES (:id_cliente, :id_metodologia, :status)');
            $stmt->execute([
                'id_cliente' => $idCliente,
                'id_metodologia' => $idMetodologia,
                'status' => $status,
            ]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function addFunctions(int $aplicacaoId, array $funcaoIds): void
    {
        $this->ensureTable();
        $funcaoIds = $this->filterAplicacaoFuncaoIds($aplicacaoId, $funcaoIds);
        if (empty($funcaoIds)) {
            return;
        }
        $stmt = $this->db->prepare('INSERT IGNORE INTO aplicacao_funcoes (aplicacao_id, funcao_id) VALUES (:ap, :fn)');
        foreach ($funcaoIds as $fid) {
            $fid = (int)$fid;
            if ($fid > 0) { $stmt->execute(['ap' => $aplicacaoId, 'fn' => $fid]); }
        }
    }

    public function functionsForAplicacao(int $aplicacaoId): array
    {
        $this->ensureTable();
        $clienteId = $this->scopedApplicationClienteId($aplicacaoId);
        if ($clienteId === null || $clienteId <= 0) {
            return [];
        }
        $catalogClienteId = (int)($this->resolveCatalogClienteId($clienteId) ?? 0);
        if ($catalogClienteId <= 0) {
            return [];
        }
        $sql = 'SELECT f.id, f.nome, f.setor_id, s.nome AS setor, d.nome AS departamento
                FROM aplicacao_funcoes af
                JOIN aplicacoes a ON a.id = af.aplicacao_id
                JOIN funcoes f ON f.id = af.funcao_id
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE af.aplicacao_id = :id
                  AND d.cliente_id = :catalog_cliente_id
                ORDER BY d.nome, s.nome, f.nome';
        $params = [
            'id' => $aplicacaoId,
            'catalog_cliente_id' => $catalogClienteId,
        ];
        $scopeAplicacao = $this->tenantInCondition('a.id_cliente', $params, 'affa');
        $scopeCatalogo = $this->tenantCatalogInCondition('d.cliente_id', $params, 'affc');
        $sql = str_replace(
            'ORDER BY d.nome, s.nome, f.nome',
            'AND ' . $scopeAplicacao . ' AND ' . $scopeCatalogo . ' ORDER BY d.nome, s.nome, f.nome',
            $sql
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addColaboradores(int $aplicacaoId, array $colabIds): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT IGNORE INTO aplicacao_colaboradores (aplicacao_id, colaborador_id) VALUES (:ap, :cb)');
        foreach ($colabIds as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) { $stmt->execute(['ap' => $aplicacaoId, 'cb' => $cid]); }
        }
    }

    public function setColaboradores(int $aplicacaoId, array $colabIds): void
    {
        $this->ensureTable();
        $colabIds = array_values(array_unique(array_filter(array_map('intval', $colabIds))));
        $in = $colabIds ? implode(',', $colabIds) : 'NULL';
        $sqlDel = $colabIds
            ? "DELETE FROM aplicacao_colaboradores WHERE aplicacao_id = :ap AND colaborador_id NOT IN ($in)"
            : "DELETE FROM aplicacao_colaboradores WHERE aplicacao_id = :ap";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->execute(['ap' => $aplicacaoId]);
        $stmtIns = $this->db->prepare('INSERT IGNORE INTO aplicacao_colaboradores (aplicacao_id, colaborador_id) VALUES (:ap, :cb)');
        foreach ($colabIds as $cid) { if ($cid > 0) { $stmtIns->execute(['ap' => $aplicacaoId, 'cb' => $cid]); } }
    }

    public function colaboradoresForAplicacao(int $aplicacaoId): array
    {
        $this->ensureTable();
        if (!\App\Database\Database::tableExists('colaboradores')) { return []; }
        $sql = 'SELECT col.id, col.nome, col.email
                FROM aplicacao_colaboradores ac
                JOIN colaboradores col ON col.id = ac.colaborador_id
                WHERE ac.aplicacao_id = :id
                ORDER BY col.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $aplicacaoId]);
        return $stmt->fetchAll();
    }

    public function setFunctions(int $aplicacaoId, array $funcaoIds): void
    {
        $this->ensureTable();
        $funcaoIds = $this->filterAplicacaoFuncaoIds($aplicacaoId, $funcaoIds);
        if ($this->scopedApplicationClienteId($aplicacaoId) === null) {
            return;
        }
        // Remove não selecionadas
        $in = $funcaoIds ? implode(',', $funcaoIds) : 'NULL';
        $sqlDel = $funcaoIds
            ? "DELETE FROM aplicacao_funcoes WHERE aplicacao_id = :ap AND funcao_id NOT IN ($in)"
            : "DELETE FROM aplicacao_funcoes WHERE aplicacao_id = :ap";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->execute(['ap' => $aplicacaoId]);
        // Adiciona novas
        $stmtIns = $this->db->prepare('INSERT IGNORE INTO aplicacao_funcoes (aplicacao_id, funcao_id) VALUES (:ap, :fn)');
        foreach ($funcaoIds as $fid) { if ($fid > 0) { $stmtIns->execute(['ap' => $aplicacaoId, 'fn' => $fid]); } }
    }

    public function byClienteWithFilters(int $idCliente, array $filters = []): array
    {
        $this->ensureTable();
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');
        $hasTipoCol = \App\Database\Database::columnExists('metodologias', 'tipo');
        $hasArquivoCol = \App\Database\Database::columnExists('metodologias', 'arquivo_path');
        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $order = $hasPrevistaCol ? 'ORDER BY a.data_prevista IS NULL, a.data_prevista, p.nome' : 'ORDER BY p.nome';
        $selectTipo = $hasTipoCol ? 'm.tipo' : 'NULL AS tipo';
        $selectArquivo = $hasArquivoCol ? 'm.arquivo_path' : 'NULL AS arquivo_path';
        $conds = ['a.id_cliente = :id_cliente'];
        $params = ['id_cliente' => $idCliente];
        $conds[] = $this->tenantInCondition('a.id_cliente', $params, 'abf');
        if (!empty($filters['status'])) { $conds[] = 'a.status = :status'; $params['status'] = $filters['status']; }
        if (!empty($filters['consultor_id'])) { $conds[] = 'a.consultor_id = :cid'; $params['cid'] = (int)$filters['consultor_id']; }
        $hasColabTbl = \App\Database\Database::tableExists('colaboradores');
        $selectColabs = $hasColabTbl
            ? "(SELECT GROUP_CONCAT(DISTINCT col.nome ORDER BY col.nome SEPARATOR ', ') FROM aplicacao_colaboradores ac LEFT JOIN colaboradores col ON col.id = ac.colaborador_id WHERE ac.aplicacao_id = a.id) AS colabs_vinculados"
            : "NULL AS colabs_vinculados";
        $sql = "SELECT a.id, a.status, a.id_metodologia, a.id_cliente, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, $selectTipo, $selectArquivo, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons,
                       $selectColabs
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                WHERE " . implode(' AND ', $conds) . "
                $order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function updateStatus(int $idAplicacao, string $status): bool
    {
        $this->ensureTable();
        $params = [
            'status' => $status,
            'id' => $idAplicacao,
        ];
        $scope = $this->tenantInCondition('id_cliente', $params, 'aus');
        $stmt = $this->db->prepare("UPDATE aplicacoes SET status = :status WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function updateSchedule(int $idAplicacao, ?string $dataPrevista, ?int $consultorId): bool
    {
        try {
            $this->ensureTable();
            $params = [
                'data_prevista' => $dataPrevista,
                'consultor_id' => $consultorId,
                'id' => $idAplicacao,
            ];
            $scope = $this->tenantInCondition('id_cliente', $params, 'auc');
            $stmt = $this->db->prepare("UPDATE aplicacoes SET data_prevista = :data_prevista, consultor_id = :consultor_id WHERE id = :id AND $scope");
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function find(int $idAplicacao): ?array
    {
        $this->ensureTable();
        $hasConsTbl = \App\Database\Database::tableExists('consultores');
        $hasPrevistaCol = \App\Database\Database::columnExists('aplicacoes', 'data_prevista');
        $hasConclusaoCol = \App\Database\Database::columnExists('aplicacoes', 'data_conclusao');
        $hasConsultorCol = \App\Database\Database::columnExists('aplicacoes', 'consultor_id');
        $hasTipoCol = \App\Database\Database::columnExists('metodologias', 'tipo');
        $hasArquivoCol = \App\Database\Database::columnExists('metodologias', 'arquivo_path');

        $selectPrevista = $hasPrevistaCol ? 'a.data_prevista' : 'NULL AS data_prevista';
        $selectConclusao = $hasConclusaoCol ? 'a.data_conclusao' : 'NULL AS data_conclusao';
        $selectConsultorId = $hasConsultorCol ? 'a.consultor_id' : 'NULL AS consultor_id';
        $selectCons = $hasConsTbl && $hasConsultorCol ? 'c.nome AS consultor_nome' : 'NULL AS consultor_nome';
        $joinCons = $hasConsTbl && $hasConsultorCol ? 'LEFT JOIN consultores c ON c.id = a.consultor_id' : '';
        $selectTipo = $hasTipoCol ? 'm.tipo' : 'NULL AS tipo';
        $selectArquivo = $hasArquivoCol ? 'm.arquivo_path' : 'NULL AS arquivo_path';

        $sql = "SELECT a.id, a.id_cliente, a.id_metodologia, a.status, $selectPrevista, $selectConclusao, $selectConsultorId,
                       m.item_pilar, $selectTipo, $selectArquivo, p.nome AS pilar_nome, cli.nome_empresa AS cliente_nome, $selectCons
                FROM aplicacoes a
                JOIN metodologias m ON m.id = a.id_metodologia
                JOIN pilares p ON p.id = m.id_pilar
                JOIN clientes cli ON cli.id = a.id_cliente
                $joinCons
                WHERE a.id = :id LIMIT 1";
        $params = ['id' => $idAplicacao];
        $scope = $this->tenantInCondition('a.id_cliente', $params, 'afn');
        $sql = str_replace('WHERE a.id = :id LIMIT 1', 'WHERE a.id = :id AND ' . $scope . ' LIMIT 1', $sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function delete(int $idAplicacao): bool
    {
        $this->ensureTable();
        $params = ['id' => $idAplicacao];
        $scope = $this->tenantInCondition('id_cliente', $params, 'adl');
        $stmt = $this->db->prepare("DELETE FROM aplicacoes WHERE id = :id AND $scope");
        return $stmt->execute($params);
    }
}
