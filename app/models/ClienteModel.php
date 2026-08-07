<?php
namespace App\Models;

use App\Database\Database;

class ClienteModel extends BaseModel
{
    private function clienteWritePayload(array $data): array
    {
        $payload = [
            'nome_empresa' => $data['nome_empresa'],
            'CNPJ' => $data['CNPJ'],
            'contato' => $data['contato'] ?? null,
        ];

        $optionalColumns = [
            'logo_path' => null,
            'dominio_publico' => null,
            'is_matriz' => 1,
            'matriz_id' => null,
        ];

        foreach ($optionalColumns as $column => $default) {
            if (Database::columnExists('clientes', $column)) {
                $payload[$column] = $data[$column] ?? $default;
            }
        }

        if (array_key_exists('matriz_id', $payload)) {
            $matrizId = isset($payload['matriz_id']) ? (int)$payload['matriz_id'] : 0;
            if ($matrizId > 0) {
                $payload['matriz_id'] = $matrizId;
                if (array_key_exists('is_matriz', $payload)) {
                    $payload['is_matriz'] = 0;
                }
            } else {
                $payload['matriz_id'] = null;
                if (array_key_exists('is_matriz', $payload)) {
                    $payload['is_matriz'] = 1;
                }
            }
        }

        if (Database::columnExists('clientes', 'ativo') && array_key_exists('ativo', $data)) {
            $payload['ativo'] = (int)$data['ativo'];
        }

        return $payload;
    }

    private function scopedParams(string $prefix = 'cli'): array
    {
        $params = [];
        $cond = $this->tenantInCondition('id', $params, $prefix);
        return [$cond, $params];
    }

    private function ensureColumns(): void
    {
        try {
            if (!\App\Database\Database::columnExists('clientes', 'is_matriz')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN is_matriz TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('clientes', 'matriz_id')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN matriz_id INT NULL');
            }
            if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('clientes', 'acesso_restrito')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN acesso_restrito TINYINT(1) NOT NULL DEFAULT 0');
            }
            if (!\App\Database\Database::columnExists('clientes', 'dominio_publico')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN dominio_publico VARCHAR(255) NULL');
            }
        } catch (\PDOException $e) {
            // silencioso
        }
    }

    public function all(): array
    {
        $this->ensureColumns();
        [$scopeCond, $params] = $this->scopedParams('ca');
        try {
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico, is_matriz, matriz_id FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
            $stmt->execute($params);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, is_matriz, matriz_id FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
                $stmt->execute($params);
            } catch (\PDOException $eMid) {
                try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
                $stmt->execute($params);
                } catch (\PDOException $e2) {
                    $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
                    $stmt->execute($params);
                }
            }
        }
        return $stmt->fetchAll();
    }

    public function allActive(): array
    {
        $this->ensureColumns();
        [$scopeCond, $params] = $this->scopedParams('caa');
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $whereAtivo = $hasAtivo ? ' AND ativo = 1' : '';
        try {
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico, is_matriz, matriz_id FROM clientes WHERE $scopeCond$whereAtivo ORDER BY nome_empresa");
            $stmt->execute($params);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, is_matriz, matriz_id FROM clientes WHERE $scopeCond$whereAtivo ORDER BY nome_empresa");
                $stmt->execute($params);
            } catch (\PDOException $eMid) {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE $scopeCond$whereAtivo ORDER BY nome_empresa");
                $stmt->execute($params);
            }
        }
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        if (!$this->canAccessClienteId($id)) {
            return null;
        }
        $this->ensureColumns();
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $ativoCol = $hasAtivo ? ', ativo' : '';
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico, is_matriz, matriz_id' . $ativoCol . ' FROM clientes WHERE id = :id');
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, is_matriz, matriz_id' . $ativoCol . ' FROM clientes WHERE id = :id');
            } catch (\PDOException $eMid) {
                try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico' . $ativoCol . ' FROM clientes WHERE id = :id');
                } catch (\PDOException $e2) {
                    $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato' . $ativoCol . ' FROM clientes WHERE id = :id');
                }
            }
        }
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActive(int $id): ?array
    {
        if (!$this->canAccessClienteId($id)) {
            return null;
        }
        $this->ensureColumns();
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $whereAtivo = $hasAtivo ? ' AND ativo = 1' : '';
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico, is_matriz, matriz_id' . ($hasAtivo ? ', ativo' : '') . ' FROM clientes WHERE id = :id' . $whereAtivo . ' LIMIT 1');
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico' . ($hasAtivo ? ', ativo' : '') . ' FROM clientes WHERE id = :id' . $whereAtivo . ' LIMIT 1');
            } catch (\PDOException $e2) {
                try {
                    $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path' . ($hasAtivo ? ', ativo' : '') . ' FROM clientes WHERE id = :id' . $whereAtivo . ' LIMIT 1');
                } catch (\PDOException $e3) {
                    $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato' . ($hasAtivo ? ', ativo' : '') . ' FROM clientes WHERE id = :id' . $whereAtivo . ' LIMIT 1');
                }
            }
        }
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function searchActiveByName(string $term, int $limit = 10): array
    {
        $this->ensureColumns();
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $params = ['q' => '%' . $term . '%'];
        $scope = $this->tenantInCondition('id', $params, 'cli_active');
        $where = [$scope, 'nome_empresa LIKE :q'];
        if (\App\Database\Database::columnExists('clientes', 'ativo')) {
            $where[] = 'ativo = 1';
        }
        $sql = 'SELECT id, nome_empresa, CNPJ, contato
                FROM clientes
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY nome_empresa
                LIMIT :lim';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, $key === 'q' ? \PDO::PARAM_STR : \PDO::PARAM_INT);
        }
        $stmt->bindValue(':lim', max(1, min(30, $limit)), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $this->ensureColumns();
        $payload = $this->clienteWritePayload($data);
        $columns = array_keys($payload);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO clientes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($payload);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->canAccessClienteId($id)) {
            return false;
        }
        $this->ensureColumns();
        $payload = $this->clienteWritePayload($data);
        $assignments = array_map(
            static fn(string $column): string => $column . ' = :' . $column,
            array_keys($payload)
        );
        $payload['id'] = $id;
        $stmt = $this->db->prepare('UPDATE clientes SET ' . implode(', ', $assignments) . ' WHERE id = :id');
        return $stmt->execute($payload);
    }

    public function delete(int $id): bool
    {
        if (!$this->canAccessClienteId($id)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function changeAtivoWithAudit(int $id, int $newAtivo, int $userId, string $reason, ?string $ip = null, ?string $userAgent = null): array
    {
        $this->ensureColumns();
        if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
            return ['ok' => false, 'message' => 'Coluna de status (ativo) não encontrada.'];
        }
        if (!\App\Database\Database::tableExists('cliente_status_logs')) {
            return ['ok' => false, 'message' => 'Tabela de auditoria de status (cliente_status_logs) não encontrada.'];
        }
        $id = (int)$id;
        $newAtivo = $newAtivo ? 1 : 0;
        $userId = (int)$userId;
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Justificativa obrigatória (mínimo 5 caracteres).'];
        }
        if (!$this->canAccessClienteId($id)) {
            return ['ok' => false, 'message' => 'Cliente não encontrado.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT id, matriz_id, ativo FROM clientes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetch();
            if (!$current) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Cliente não encontrado.'];
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
                $matrizId = (int)($current['matriz_id'] ?? 0);
                if ($matrizId > 0) {
                    $stmtMz = $this->db->prepare('SELECT ativo FROM clientes WHERE id = :id LIMIT 1');
                    $stmtMz->execute(['id' => $matrizId]);
                    $mzAtivo = $stmtMz->fetchColumn();
                    if ($mzAtivo !== false && (int)$mzAtivo !== 1) {
                        $this->db->rollBack();
                        return ['ok' => false, 'message' => 'Não é possível ativar a filial: a matriz está inativa.'];
                    }
                }
            }

            $stmtUp = $this->db->prepare('UPDATE clientes SET ativo = :a WHERE id = :id');
            $ok = $stmtUp->execute(['a' => $newAtivo, 'id' => $id]);
            if (!$ok) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Não foi possível atualizar o status do cliente.'];
            }

            $stmtLog = $this->db->prepare('INSERT INTO cliente_status_logs (cliente_id, old_ativo, new_ativo, justificativa, changed_by, ip, user_agent) VALUES (:cid, :old, :new, :j, :uid, :ip, :ua)');
            $stmtLog->execute([
                'cid' => $id,
                'old' => $oldAtivo,
                'new' => $newAtivo,
                'j' => $reason,
                'uid' => $userId > 0 ? $userId : null,
                'ip' => $ip !== null && trim($ip) !== '' ? mb_substr($ip, 0, 45) : null,
                'ua' => $userAgent !== null && trim($userAgent) !== '' ? mb_substr($userAgent, 0, 255) : null,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $newAtivo === 1 ? 'Cliente ativado com sucesso.' : 'Cliente inativado com sucesso.'];
        } catch (\Throwable $e) {
            try { if ($this->db->inTransaction()) $this->db->rollBack(); } catch (\Throwable $e2) {}
            return ['ok' => false, 'message' => 'Erro interno ao atualizar status.'];
        }
    }

    public function updateWithStatusAudit(int $id, array $data, int $newAtivo, int $userId, string $reason, ?string $ip = null, ?string $userAgent = null): array
    {
        $this->ensureColumns();
        if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
            return ['ok' => false, 'message' => 'Coluna de status (ativo) não encontrada.'];
        }
        if (!\App\Database\Database::tableExists('cliente_status_logs')) {
            return ['ok' => false, 'message' => 'Tabela de auditoria de status (cliente_status_logs) não encontrada.'];
        }
        $id = (int)$id;
        $newAtivo = $newAtivo ? 1 : 0;
        $userId = (int)$userId;
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Justificativa obrigatória (mínimo 5 caracteres).'];
        }
        if (!$this->canAccessClienteId($id)) {
            return ['ok' => false, 'message' => 'Cliente não encontrado.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT id, matriz_id, ativo FROM clientes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetch();
            if (!$current) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Cliente não encontrado.'];
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
                $matrizId = (int)($current['matriz_id'] ?? 0);
                if ($matrizId > 0) {
                    $stmtMz = $this->db->prepare('SELECT ativo FROM clientes WHERE id = :id LIMIT 1');
                    $stmtMz->execute(['id' => $matrizId]);
                    $mzAtivo = $stmtMz->fetchColumn();
                    if ($mzAtivo !== false && (int)$mzAtivo !== 1) {
                        $this->db->rollBack();
                        return ['ok' => false, 'message' => 'Não é possível ativar a filial: a matriz está inativa.'];
                    }
                }
            }

            $data['ativo'] = $newAtivo;
            $payload = $this->clienteWritePayload($data);
            $assignments = array_map(static fn(string $column): string => $column . ' = :' . $column, array_keys($payload));
            $payload['id'] = $id;
            $stmtUp = $this->db->prepare('UPDATE clientes SET ' . implode(', ', $assignments) . ' WHERE id = :id');
            $ok = $stmtUp->execute($payload);
            if (!$ok) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Não foi possível salvar o cliente.'];
            }

            $stmtLog = $this->db->prepare('INSERT INTO cliente_status_logs (cliente_id, old_ativo, new_ativo, justificativa, changed_by, ip, user_agent) VALUES (:cid, :old, :new, :j, :uid, :ip, :ua)');
            $stmtLog->execute([
                'cid' => $id,
                'old' => $oldAtivo,
                'new' => $newAtivo,
                'j' => $reason,
                'uid' => $userId > 0 ? $userId : null,
                'ip' => $ip !== null && trim($ip) !== '' ? mb_substr($ip, 0, 45) : null,
                'ua' => $userAgent !== null && trim($userAgent) !== '' ? mb_substr($userAgent, 0, 255) : null,
            ]);

            $this->db->commit();
            return ['ok' => true, 'message' => $newAtivo === 1 ? 'Cliente ativado com sucesso.' : 'Cliente inativado com sucesso.'];
        } catch (\Throwable $e) {
            try { if ($this->db->inTransaction()) $this->db->rollBack(); } catch (\Throwable $e2) {}
            return ['ok' => false, 'message' => 'Erro interno ao salvar cliente.'];
        }
    }

    private function deactivationBlocks(int $clienteId): array
    {
        $blocks = [];
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return ['Cliente inválido.'];
        }

        if (\App\Database\Database::tableExists('tarefas') && \App\Database\Database::columnExists('tarefas', 'status')) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tarefas WHERE cliente_id = :id AND status <> 'Finalizado'");
            $stmt->execute(['id' => $clienteId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: existem tarefas em aberto vinculadas ao cliente.';
            }
        }
        if (\App\Database\Database::tableExists('reunioes') && \App\Database\Database::columnExists('reunioes', 'status')) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM reunioes WHERE cliente_id = :id AND status <> 'Finalizado'");
            $stmt->execute(['id' => $clienteId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: existem reuniões em aberto vinculadas ao cliente.';
            }
        }
        if (\App\Database\Database::tableExists('auditorias')) {
            $cond = "status <> 'Realizada'";
            if (\App\Database\Database::columnExists('auditorias', 'deleted_at')) {
                $cond .= ' AND deleted_at IS NULL';
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM auditorias WHERE cliente_id = :id AND $cond");
            $stmt->execute(['id' => $clienteId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $blocks[] = 'Não é possível inativar: existem auditorias abertas vinculadas ao cliente.';
            }
        }
        return $blocks;
    }

    public function matrizes(): array
    {
        $this->ensureColumns();
        [$scopeCond, $params] = $this->scopedParams('cm');
        $where = 'is_matriz = 1 AND ' . $scopeCond;
        $hasAtivo = \App\Database\Database::columnExists('clientes', 'ativo');
        $ativoCol = $hasAtivo ? ', ativo' : '';
        try {
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id$ativoCol FROM clientes WHERE $where ORDER BY nome_empresa");
            $stmt->execute($params);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path$ativoCol FROM clientes WHERE $where ORDER BY nome_empresa");
                $stmt->execute($params);
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato$ativoCol FROM clientes WHERE $where ORDER BY nome_empresa");
                $stmt->execute($params);
            }
        }
        return $stmt->fetchAll();
    }

    public function filiaisByMatriz(int $matrizId): array
    {
        if (!$this->canAccessClienteId($matrizId)) {
            return [];
        }
        $this->ensureColumns();
        try {
            $params = ['mid' => $matrizId, 'self_id' => $matrizId];
            $scope = $this->tenantInCondition('id', $params, 'cf');
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE matriz_id = :mid AND id <> :self_id AND $scope ORDER BY nome_empresa");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function groupCompanies(int $clienteId): array
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return [];
        }
        $rootId = $this->catalogRootIdFor($clienteId);
        if ($rootId <= 0) {
            return [];
        }
        if (!$this->canAccessClienteId($clienteId) && !$this->canAccessClienteId($rootId)) {
            return [];
        }

        $params = ['root_id' => $rootId];
        $scope = $this->tenantInCondition('id', $params, 'cgroup');
        $stmt = $this->db->prepare(
            "SELECT id, nome_empresa, is_matriz, matriz_id
             FROM clientes
             WHERE (id = :root_id OR matriz_id = :root_id)
               AND $scope
             ORDER BY is_matriz DESC, nome_empresa"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int>
     */
    public function groupCompanyIds(int $clienteId): array
    {
        return array_values(array_filter(array_map(
            static fn(array $cliente): int => (int)($cliente['id'] ?? 0),
            $this->groupCompanies($clienteId)
        )));
    }

    public function catalogRootIdFor(int $clienteId): int
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return 0;
        }
        $selected = $this->find($clienteId);
        if (!$selected) {
            return 0;
        }
        $matrizId = (int)($selected['matriz_id'] ?? 0);
        $isMatriz = $matrizId <= 0 && (int)($selected['is_matriz'] ?? 1) === 1;
        $rootId = $isMatriz ? $clienteId : $matrizId;
        if ($rootId <= 0) {
            $rootId = $clienteId;
        }
        if ($rootId !== $clienteId && !$this->find($rootId)) {
            $rootId = $clienteId;
        }
        return $rootId;
    }

    public function isFilial(int $clienteId): bool
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return false;
        }
        $selected = $this->find($clienteId);
        if (!$selected) {
            return false;
        }
        return (int)($selected['is_matriz'] ?? 1) !== 1 && (int)($selected['matriz_id'] ?? 0) > 0;
    }

    public function byIds(array $ids): array
    {
        $this->ensureColumns();
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }
        $params = [];
        $holders = [];
        foreach ($ids as $i => $id) {
            $k = 'id' . $i;
            $holders[] = ':' . $k;
            $params[$k] = $id;
        }
        $scope = $this->tenantInCondition('id', $params, 'cbids');
        $sql = 'SELECT id, nome_empresa, is_matriz, matriz_id
                FROM clientes
                WHERE id IN (' . implode(',', $holders) . ') AND ' . $scope . '
                ORDER BY nome_empresa';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findByPublicHost(string $host): ?array
    {
        $this->ensureColumns();
        $normalized = strtolower(trim($host));
        $normalized = preg_replace('/:\d+$/', '', $normalized) ?: $normalized;
        $normalized = preg_replace('/^www\./', '', $normalized) ?: $normalized;
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico FROM clientes WHERE REPLACE(LOWER(dominio_publico), "www.", "") = :host LIMIT 1');
            $stmt->execute(['host' => $normalized]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    public function findAny(int $id): ?array
    {
        $this->ensureColumns();
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico, is_matriz, matriz_id FROM clientes WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, is_matriz, matriz_id FROM clientes WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                return $row ?: null;
            } catch (\PDOException $eMid) {
                try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, dominio_publico FROM clientes WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                return $row ?: null;
                } catch (\PDOException $e2) {
                    try {
                        $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes WHERE id = :id');
                        $stmt->execute(['id' => $id]);
                        $row = $stmt->fetch();
                        return $row ?: null;
                    } catch (\PDOException $e3) {
                        try {
                            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE id = :id');
                            $stmt->execute(['id' => $id]);
                            $row = $stmt->fetch();
                            return $row ?: null;
                        } catch (\PDOException $e4) {
                            return null;
                        }
                    }
                }
            }
        }
    }

    public function manualPortalScopeIds(int $empresaId): array
    {
        $empresa = $this->findAny($empresaId);
        if (!$empresa) {
            return [];
        }
        if ((int)($empresa['is_matriz'] ?? 1) !== 1) {
            return [$empresaId];
        }
        $ids = [$empresaId];
        try {
            $stmt = $this->db->prepare('SELECT id FROM clientes WHERE matriz_id = :mid AND id <> :self_id ORDER BY id');
            $stmt->execute(['mid' => $empresaId, 'self_id' => $empresaId]);
            foreach ($stmt->fetchAll() as $row) {
                $ids[] = (int)$row['id'];
            }
        } catch (\PDOException $e) {
        }
        return array_values(array_unique(array_filter($ids)));
    }
}
