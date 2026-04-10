<?php
namespace App\Models;

use App\Core\Auth;

class AvaliacaoModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS avaliacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NULL,
                empresa_nome VARCHAR(255) NULL,
                nome VARCHAR(150) NOT NULL DEFAULT "",
                whatsapp VARCHAR(20) NOT NULL DEFAULT "",
                email VARCHAR(180) NOT NULL DEFAULT "",
                numero_funcionarios INT UNSIGNED NOT NULL DEFAULT 0,
                numero_lideres INT UNSIGNED NOT NULL DEFAULT 0,
                faturamento_medio_anual BIGINT UNSIGNED NOT NULL DEFAULT 0,
                tomador_decisao TINYINT(1) NOT NULL DEFAULT 0,
                origem_cadastro VARCHAR(30) NOT NULL DEFAULT "cliente_existente",
                created_by_user_id INT NULL,
                cliente_associado_em DATETIME NULL,
                contato VARCHAR(255) NULL,
                respostas_json TEXT NULL,
                nota_financeiro TINYINT NOT NULL DEFAULT 0,
                nota_mercado TINYINT NOT NULL DEFAULT 0,
                nota_pessoas TINYINT NOT NULL DEFAULT 0,
                nota_processo TINYINT NOT NULL DEFAULT 0,
                realidade_financeiro TINYINT NULL,
                realidade_mercado TINYINT NULL,
                realidade_pessoas TINYINT NULL,
                realidade_processo TINYINT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('avaliacoes', 'cliente_id')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN cliente_id INT NULL AFTER id');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'empresa_nome')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN empresa_nome VARCHAR(255) NULL AFTER cliente_id');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'nome')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN nome VARCHAR(150) NOT NULL DEFAULT "" AFTER empresa_nome');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'whatsapp')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN whatsapp VARCHAR(20) NOT NULL DEFAULT "" AFTER nome');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'email')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN email VARCHAR(180) NOT NULL DEFAULT "" AFTER whatsapp');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'numero_funcionarios')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN numero_funcionarios INT UNSIGNED NOT NULL DEFAULT 0 AFTER email');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'numero_lideres')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN numero_lideres INT UNSIGNED NOT NULL DEFAULT 0 AFTER numero_funcionarios');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'faturamento_medio_anual')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN faturamento_medio_anual BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER numero_lideres');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'tomador_decisao')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN tomador_decisao TINYINT(1) NOT NULL DEFAULT 0 AFTER faturamento_medio_anual');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'origem_cadastro')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN origem_cadastro VARCHAR(30) NOT NULL DEFAULT "cliente_existente" AFTER tomador_decisao');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'created_by_user_id')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN created_by_user_id INT NULL AFTER origem_cadastro');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'cliente_associado_em')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN cliente_associado_em DATETIME NULL AFTER created_by_user_id');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'contato')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN contato VARCHAR(255) NULL AFTER empresa_nome');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'respostas_json')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN respostas_json TEXT NULL AFTER contato');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'nota_financeiro')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN nota_financeiro TINYINT NOT NULL DEFAULT 0 AFTER respostas_json');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'nota_mercado')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN nota_mercado TINYINT NOT NULL DEFAULT 0 AFTER nota_financeiro');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'nota_pessoas')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN nota_pessoas TINYINT NOT NULL DEFAULT 0 AFTER nota_mercado');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'nota_processo')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN nota_processo TINYINT NOT NULL DEFAULT 0 AFTER nota_pessoas');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'realidade_financeiro')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN realidade_financeiro TINYINT NULL AFTER nota_processo');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'realidade_mercado')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN realidade_mercado TINYINT NULL AFTER realidade_financeiro');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'realidade_pessoas')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN realidade_pessoas TINYINT NULL AFTER realidade_mercado');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'realidade_processo')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN realidade_processo TINYINT NULL AFTER realidade_pessoas');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'created_at')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }
        } catch (\PDOException $e) {}
    }
 
    public function all(): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->scopeClause('a', $params, 'avall');
        $sql = 'SELECT a.*, c.nome_empresa AS cliente_nome,
                       ap.token AS publico_token,
                       ap.status AS publico_status,
                       ap.nome AS publico_nome,
                       ap.empresa AS publico_empresa,
                       ap.expiracao AS publico_expiracao,
                       ap.data_criacao AS publico_data_envio,
                       ap.data_conclusao AS publico_data_conclusao
                FROM avaliacoes a
                LEFT JOIN clientes c ON c.id = a.cliente_id
                LEFT JOIN avaliacoes_publicas ap ON ap.avaliacao_id = a.id
                WHERE ' . $scope . '
                ORDER BY a.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
 
    public function byCliente(int $clienteId): array
    {
        $this->ensureTable();
        $params = ['cid' => $clienteId];
        $scope = $this->tenantInCondition('cliente_id', $params, 'avbc');
        $stmt = $this->db->prepare("SELECT * FROM avaliacoes WHERE cliente_id = :cid AND $scope ORDER BY created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
 
    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->scopeClause('', $params, 'avf');
        $stmt = $this->db->prepare("SELECT a.*, c.nome_empresa AS cliente_nome FROM avaliacoes a LEFT JOIN clientes c ON c.id = a.cliente_id WHERE a.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
 
    public function create(array $data): int
    {
        $this->ensureTable();
        $clienteId = isset($data['cliente_id']) && (int)$data['cliente_id'] > 0 ? (int)$data['cliente_id'] : null;
        if ($clienteId !== null) {
            $clienteId = $this->normalizeScopedClienteId($clienteId);
            if (($clienteId ?? 0) <= 0 || !$this->canAccessClienteId((int)$clienteId)) {
                return 0;
            }
        }
        $insert = [
            'cliente_id' => $clienteId,
            'empresa_nome' => $data['empresa_nome'] ?? null,
            'nome' => $data['nome'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'email' => $data['email'] ?? '',
            'numero_funcionarios' => isset($data['numero_funcionarios']) ? (int)$data['numero_funcionarios'] : 0,
            'numero_lideres' => isset($data['numero_lideres']) ? (int)$data['numero_lideres'] : 0,
            'faturamento_medio_anual' => isset($data['faturamento_medio_anual']) ? (int)$data['faturamento_medio_anual'] : 0,
            'tomador_decisao' => !empty($data['tomador_decisao']) ? 1 : 0,
            'origem_cadastro' => $data['origem_cadastro'] ?? ($clienteId ? 'cliente_existente' : 'potencial_cliente'),
            'created_by_user_id' => $data['created_by_user_id'] ?? (Auth::user()['id'] ?? null),
            'cliente_associado_em' => $data['cliente_associado_em'] ?? null,
            'contato' => $data['contato'] ?? null,
            'respostas_json' => $data['respostas_json'] ?? null,
            'nota_financeiro' => (int)($data['nota_financeiro'] ?? 0),
            'nota_mercado' => (int)($data['nota_mercado'] ?? 0),
            'nota_pessoas' => (int)($data['nota_pessoas'] ?? 0),
            'nota_processo' => (int)($data['nota_processo'] ?? 0),
            'realidade_financeiro' => isset($data['realidade_financeiro']) ? (int)$data['realidade_financeiro'] : null,
            'realidade_mercado' => isset($data['realidade_mercado']) ? (int)$data['realidade_mercado'] : null,
            'realidade_pessoas' => isset($data['realidade_pessoas']) ? (int)$data['realidade_pessoas'] : null,
            'realidade_processo' => isset($data['realidade_processo']) ? (int)$data['realidade_processo'] : null,
        ];
        $legacyMap = [
            'financeiro_nota' => $insert['nota_financeiro'],
            'mercado_nota' => $insert['nota_mercado'],
            'pessoas_nota' => $insert['nota_pessoas'],
            'processo_nota' => $insert['nota_processo'],
            'financeiro_realidade' => $insert['realidade_financeiro'],
            'mercado_realidade' => $insert['realidade_mercado'],
            'pessoas_realidade' => $insert['realidade_pessoas'],
            'processo_realidade' => $insert['realidade_processo'],
        ];
        foreach ($legacyMap as $column => $value) {
            if (\App\Database\Database::columnExists('avaliacoes', $column)) {
                $insert[$column] = $value;
            }
        }
        foreach ($this->requiredFallbackColumns() as $column => $meta) {
            if (array_key_exists($column, $insert)) {
                continue;
            }
            $insert[$column] = $this->fallbackValueForColumn((string)$meta['Type']);
        }
        $columns = array_keys($insert);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $stmt = $this->db->prepare('INSERT INTO avaliacoes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($insert);
        return (int)$this->db->lastInsertId();
    }

    public function associateCliente(int $avaliacaoId, int $clienteId): bool
    {
        $this->ensureTable();
        if ($clienteId <= 0 || !$this->canAccessClienteId($clienteId)) {
            return false;
        }
        $params = ['id' => $avaliacaoId];
        $scope = $this->scopeClause('', $params, 'avassoc');
        $stmt = $this->db->prepare("SELECT id, cliente_id FROM avaliacoes a WHERE a.id = :id AND $scope LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE avaliacoes SET cliente_id = :cliente_id, cliente_associado_em = NOW() WHERE id = :id');
        return $stmt->execute([
            'cliente_id' => $clienteId,
            'id' => $avaliacaoId,
        ]);
    }

    private function scopeClause(string $alias, array &$params, string $prefix): string
    {
        if (Auth::isInstituto()) {
            return '1=1';
        }
        $columnCliente = $alias !== '' ? $alias . '.cliente_id' : 'a.cliente_id';
        $columnCreator = $alias !== '' ? $alias . '.created_by_user_id' : 'a.created_by_user_id';
        $parts = [];
        $clientIds = Auth::allowedClientIds();
        if (!empty($clientIds)) {
            $holders = [];
            foreach (array_values($clientIds) as $i => $id) {
                $key = $prefix . 'c' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $parts[] = $columnCliente . ' IN (' . implode(',', $holders) . ')';
        }
        $userId = (int)(Auth::user()['id'] ?? 0);
        if ($userId > 0) {
            $params[$prefix . 'u'] = $userId;
            $parts[] = '(' . $columnCliente . ' IS NULL AND ' . $columnCreator . ' = :' . $prefix . 'u)';
        }
        if (empty($parts)) {
            return '1=0';
        }
        return '(' . implode(' OR ', $parts) . ')';
    }

    private function requiredFallbackColumns(): array
    {
        $columns = [];
        $stmt = $this->db->query('SHOW COLUMNS FROM avaliacoes');
        foreach ($stmt->fetchAll() as $column) {
            $field = (string)($column['Field'] ?? '');
            if ($field === '' || $field === 'id') {
                continue;
            }
            if (($column['Null'] ?? '') === 'YES') {
                continue;
            }
            $default = $column['Default'] ?? null;
            if ($default !== null) {
                continue;
            }
            $columns[$field] = $column;
        }
        return $columns;
    }

    private function fallbackValueForColumn(string $type)
    {
        $type = strtolower($type);
        if (preg_match('/int|decimal|float|double|bit/', $type)) {
            return 0;
        }
        if (preg_match('/date|time|year/', $type)) {
            return date('Y-m-d H:i:s');
        }
        return '';
    }
}
