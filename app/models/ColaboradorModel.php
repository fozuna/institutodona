<?php
namespace App\Models;

class ColaboradorModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS colaboradores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(180) NOT NULL,
                email VARCHAR(180) NULL,
                funcao_id INT NOT NULL,
                lider ENUM(\'não\',\'sim\') NOT NULL DEFAULT \'não\',
                cliente_id INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (!\App\Database\Database::columnExists('colaboradores', 'cliente_id')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN cliente_id INT NULL');
            }
            if (!\App\Database\Database::columnExists('colaboradores', 'lider')) {
                $this->db->exec('ALTER TABLE colaboradores ADD COLUMN lider ENUM(\'não\',\'sim\') NOT NULL DEFAULT \'não\' AFTER funcao_id');
            }
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
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
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
        if (!empty($filters['funcao_id'])) { $conds[] = 'f.id = :func'; $params['func'] = (int)$filters['funcao_id']; }
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

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cf');
        $stmt = $this->db->prepare("SELECT id, nome, email, funcao_id, cliente_id, lider FROM colaboradores WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if (($data['cliente_id'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['cliente_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO colaboradores (nome, email, funcao_id, lider, cliente_id) VALUES (:nome, :email, :funcao_id, :lider, :cliente_id)');
        $stmt->execute([
            'nome' => $data['nome'],
            'email' => $data['email'] ?? null,
            'funcao_id' => (int)$data['funcao_id'],
            'lider' => ($data['lider'] ?? 'não') === 'sim' ? 'sim' : 'não',
            'cliente_id' => $data['cliente_id'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $data['cliente_id'] = (int)$this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        $params = [
            'nome' => $data['nome'],
            'email' => $data['email'] ?? null,
            'funcao_id' => (int)$data['funcao_id'],
            'lider' => ($data['lider'] ?? 'não') === 'sim' ? 'sim' : 'não',
            'cliente_id' => $data['cliente_id'] ?? null,
            'id' => $id
        ];
        $scope = $this->tenantInCondition('cliente_id', $params, 'cu');
        $stmt = $this->db->prepare("UPDATE colaboradores SET nome = :nome, email = :email, funcao_id = :funcao_id, lider = :lider, cliente_id = :cliente_id WHERE id = :id AND $scope");
        return $stmt->execute($params);
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
}
