<?php
namespace App\Models;

use App\Database\Database;

class CronogramaModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS cronogramas (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_cliente INT NOT NULL,
              nome VARCHAR(255) NULL,
              ano INT NOT NULL,
              ativo TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('cronogramas', 'ativo')) {
                $this->db->exec("ALTER TABLE cronogramas ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER ano");
            }
        } catch (\PDOException $e) {
            // ignore
        }
    }

    public function all(?array $order = null): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'cra');
        $orderBy = $this->buildOrderBy($order, 'cli.nome_empresa ASC, c.ano DESC');
        $selectAtivo = Database::columnExists('cronogramas', 'ativo') ? 'c.ativo' : '1 AS ativo';
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, cli.nome_empresa AS cliente, c.id_cliente, $selectAtivo FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE $scope ORDER BY $orderBy");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function byCliente(int $idCliente, ?array $order = null): array
    {
        return $this->byClientes([$idCliente], $order);
    }

    public function byClientes(array $clienteIds, ?array $order = null): array
    {
        $this->ensureTable();
        $clienteIds = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (empty($clienteIds)) {
            return [];
        }
        $params = [];
        $holders = [];
        foreach ($clienteIds as $i => $clienteId) {
            $key = 'id' . $i;
            $holders[] = ':' . $key;
            $params[$key] = $clienteId;
        }
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'crb');
        $orderBy = $this->buildOrderBy($order, 'c.ano DESC');
        $selectAtivo = Database::columnExists('cronogramas', 'ativo') ? 'c.ativo' : '1 AS ativo';
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, cli.nome_empresa AS cliente, c.id_cliente, $selectAtivo FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE c.id_cliente IN (" . implode(',', $holders) . ") AND $scope ORDER BY $orderBy");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'crf');
        $selectAtivo = Database::columnExists('cronogramas', 'ativo') ? 'c.ativo' : '1 AS ativo';
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, c.id_cliente, cli.nome_empresa AS cliente, $selectAtivo FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE c.id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $data['id_cliente'] = (int)$this->normalizeScopedClienteId(isset($data['id_cliente']) ? (int)$data['id_cliente'] : null);
        if (($data['id_cliente'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['id_cliente'])) {
            return 0;
        }
        $hasAtivo = Database::columnExists('cronogramas', 'ativo');
        $stmt = $this->db->prepare($hasAtivo
            ? 'INSERT INTO cronogramas (id_cliente, nome, ano, ativo) VALUES (:id_cliente, :nome, :ano, :ativo)'
            : 'INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:id_cliente, :nome, :ano)'
        );
        $params = [
            'id_cliente' => $data['id_cliente'],
            'nome' => $data['nome'] ?? null,
            'ano' => $data['ano'],
        ];
        if ($hasAtivo) {
            $params['ativo'] = !empty($data['ativo']) ? 1 : 0;
        }
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        if ($id <= 0) {
            return false;
        }
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        $data['id_cliente'] = (int)$this->normalizeScopedClienteId(isset($data['id_cliente']) ? (int)$data['id_cliente'] : null);
        if (($data['id_cliente'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['id_cliente'])) {
            return false;
        }
        $hasAtivo = Database::columnExists('cronogramas', 'ativo');
        $sql = $hasAtivo
            ? 'UPDATE cronogramas SET id_cliente = :id_cliente, nome = :nome, ano = :ano, ativo = :ativo WHERE id = :id'
            : 'UPDATE cronogramas SET id_cliente = :id_cliente, nome = :nome, ano = :ano WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $params = [
            'id' => $id,
            'id_cliente' => $data['id_cliente'],
            'nome' => $data['nome'] ?? null,
            'ano' => (int)($data['ano'] ?? 0),
        ];
        if ($hasAtivo) {
            $params['ativo'] = !empty($data['ativo']) ? 1 : 0;
        }
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        if ($id <= 0) {
            return false;
        }
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM cronogramas WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function toggleAtivo(int $id): ?int
    {
        $this->ensureTable();
        if ($id <= 0 || !Database::columnExists('cronogramas', 'ativo')) {
            return null;
        }
        $existing = $this->find($id);
        if (!$existing) {
            return null;
        }
        $current = (int)($existing['ativo'] ?? 1) === 1 ? 1 : 0;
        $next = $current === 1 ? 0 : 1;
        $stmt = $this->db->prepare('UPDATE cronogramas SET ativo = :a WHERE id = :id');
        if (!$stmt->execute(['a' => $next, 'id' => $id])) {
            return null;
        }
        return $next;
    }

    public function duplicate(int $id): int
    {
        $this->ensureTable();
        $existing = $this->find($id);
        if (!$existing) {
            return 0;
        }
        $nome = trim((string)($existing['nome'] ?? ''));
        $data = [
            'id_cliente' => (int)($existing['id_cliente'] ?? 0),
            'nome' => $nome === '' ? 'Cópia' : ($nome . ' (Cópia)'),
            'ano' => (int)($existing['ano'] ?? date('Y')),
            'ativo' => (int)($existing['ativo'] ?? 1) === 1 ? 1 : 0,
        ];
        return $this->create($data);
    }

    private function buildOrderBy(?array $order, string $fallback): string
    {
        if (!$order) {
            return $fallback;
        }
        $column = strtolower(trim((string)($order['column'] ?? '')));
        $direction = strtolower(trim((string)($order['direction'] ?? 'asc'))) === 'desc' ? 'DESC' : 'ASC';
        $columns = [
            'cliente' => 'cli.nome_empresa COLLATE utf8mb4_unicode_ci',
            'nome' => 'c.nome COLLATE utf8mb4_unicode_ci',
            'ano' => 'c.ano',
        ];
        if (!isset($columns[$column])) {
            return $fallback;
        }
        return $columns[$column] . ' ' . $direction;
    }
}
