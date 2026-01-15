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
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function countByCliente(int $clienteId): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM colaboradores WHERE cliente_id = :cid');
        $stmt->execute(['cid' => $clienteId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function countByClienteWithFilters(int $clienteId, array $filters): int
    {
        $this->ensureTable();
        $conds = ['col.cliente_id = :cid'];
        $params = ['cid' => $clienteId];
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
        $stmt = $this->db->prepare('SELECT id, nome, email, funcao_id, cliente_id, lider FROM colaboradores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
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
        $stmt = $this->db->prepare('UPDATE colaboradores SET nome = :nome, email = :email, funcao_id = :funcao_id, lider = :lider, cliente_id = :cliente_id WHERE id = :id');
        return $stmt->execute([
            'nome' => $data['nome'],
            'email' => $data['email'] ?? null,
            'funcao_id' => (int)$data['funcao_id'],
            'lider' => ($data['lider'] ?? 'não') === 'sim' ? 'sim' : 'não',
            'cliente_id' => $data['cliente_id'] ?? null,
            'id' => $id
        ]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('DELETE FROM colaboradores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function searchByClienteName(int $clienteId, string $q, int $limit = 10): array
    {
        $this->ensureTable();
        $q = trim($q);
        if ($q === '') { return []; }
        $stmt = $this->db->prepare('SELECT id, nome, email FROM colaboradores WHERE cliente_id = :cid AND nome LIKE :q ORDER BY nome LIMIT :lim');
        $like = '%' . $q . '%';
        $stmt->bindValue(':cid', $clienteId, \PDO::PARAM_INT);
        $stmt->bindValue(':q', $like, \PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
