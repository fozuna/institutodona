<?php
namespace App\Models;

use App\Database\Database;

final class TarefaModel extends BaseModel
{
    private const STATUS_VALUES = ['Planejado', 'Pendente', 'Andamento', 'Adiado', 'Finalizado'];
    private const PRIORIDADE_VALUES = ['baixa', 'media', 'alta'];

    /**
     * Item 17: whitelist fixa de ordenacao - cada chave mapeia para uma
     * expressao SQL inteiramente conhecida em tempo de compilacao, nunca
     * construida a partir de valor cru de $_GET (normalizeOrder() sempre
     * cai no default se a chave nao existir aqui). FIELD() para prioridade
     * e literal (nao concatena o valor do filtro), expressando a ordem de
     * negocio alta > media > baixa (e o inverso).
     */
    private const ORDER_OPTIONS = [
        'data_inicio_desc' => 't.data_inicio DESC, t.id DESC',
        'data_inicio_asc' => 't.data_inicio ASC, t.id ASC',
        'created_at_desc' => 't.created_at DESC, t.id DESC',
        'created_at_asc' => 't.created_at ASC, t.id ASC',
        'prioridade_desc' => "FIELD(t.prioridade,'alta','media','baixa'), t.data_inicio DESC",
        'prioridade_asc' => "FIELD(t.prioridade,'baixa','media','alta'), t.data_inicio DESC",
    ];
    private const DEFAULT_ORDER = 'data_inicio_desc';

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

    public static function statusValues(): array
    {
        return self::STATUS_VALUES;
    }

    public static function prioridadeValues(): array
    {
        return self::PRIORIDADE_VALUES;
    }

    public static function orderOptions(): array
    {
        return array_keys(self::ORDER_OPTIONS);
    }

    public static function normalizeStatusFilter(?string $value): ?string
    {
        $value = trim((string)$value);
        return in_array($value, self::STATUS_VALUES, true) ? $value : null;
    }

    public static function normalizePrioridadeFilter(?string $value): ?string
    {
        $value = trim((string)$value);
        return in_array($value, self::PRIORIDADE_VALUES, true) ? $value : null;
    }

    public static function normalizeOrder(?string $value): string
    {
        $value = trim((string)$value);
        return array_key_exists($value, self::ORDER_OPTIONS) ? $value : self::DEFAULT_ORDER;
    }

    /**
     * Monta a clausula WHERE compartilhada por count() e paginate() (Item
     * 17), garantindo que os dois usem exatamente os mesmos filtros. Mesma
     * defesa em profundidade de all()/find(): cliente_id (se informado) e
     * remapeado por normalizeScopedClienteId() e a condicao de tenant
     * (tenantInCondition) e sempre ANDada por cima, independente do filtro.
     */
    private function buildFilterClause(array $filters, array &$params, string $prefix): string
    {
        $where = [];
        $clienteId = isset($filters['cliente_id']) ? (int)$filters['cliente_id'] : 0;
        if ($clienteId > 0) {
            $key = $prefix . '_cliente';
            $params[$key] = $this->normalizeScopedClienteId($clienteId);
            $where[] = 't.cliente_id = :' . $key;
        }
        $where[] = $this->tenantInCondition('t.cliente_id', $params, $prefix . '_tenant');

        $status = self::normalizeStatusFilter($filters['status'] ?? null);
        if ($status !== null) {
            $key = $prefix . '_status';
            $params[$key] = $status;
            $where[] = 't.status = :' . $key;
        }

        $prioridade = self::normalizePrioridadeFilter($filters['prioridade'] ?? null);
        if ($prioridade !== null) {
            $key = $prefix . '_prioridade';
            $params[$key] = $prioridade;
            $where[] = 't.prioridade = :' . $key;
        }

        return implode(' AND ', $where);
    }

    /**
     * Item 17: contagem usada para calcular o total de paginas, com os
     * mesmos filtros de paginate() (via buildFilterClause compartilhado).
     */
    public function count(array $filters = []): int
    {
        $this->ensureTable();
        $params = [];
        $where = $this->buildFilterClause($filters, $params, 'tkc');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tarefas t WHERE $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Item 17: listagem paginada e filtrada (cliente/status/prioridade) com
     * ordenacao configuravel via whitelist (normalizeOrder()). Nao substitui
     * all() - metodo novo, adicional, usado apenas por
     * TarefasController::index().
     */
    public function paginate(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $this->ensureTable();
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildFilterClause($filters, $params, 'tkp');
        $orderKey = self::normalizeOrder($filters['ordem'] ?? null);
        $orderSql = self::ORDER_OPTIONS[$orderKey];

        $sql = "SELECT t.*, c.nome_empresa AS cliente
                FROM tarefas t
                JOIN clientes c ON c.id = t.cliente_id
                WHERE $where
                ORDER BY $orderSql
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
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
        if (!in_array($prioridade, self::PRIORIDADE_VALUES, true)) {
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
        if (!in_array($prioridade, self::PRIORIDADE_VALUES, true)) {
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

