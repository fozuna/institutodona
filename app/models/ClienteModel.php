<?php
namespace App\Models;

class ClienteModel extends BaseModel
{
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
        } catch (\PDOException $e) {
            // silencioso
        }
    }

    public function all(): array
    {
        $this->ensureColumns();
        [$scopeCond, $params] = $this->scopedParams('ca');
        try {
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
            $stmt->execute($params);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
                $stmt->execute($params);
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE $scopeCond ORDER BY nome_empresa");
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
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id FROM clientes WHERE id = :id');
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes WHERE id = :id');
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE id = :id');
            }
        }
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureColumns();
        try {
            $stmt = $this->db->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id) VALUES (:nome_empresa, :cnpj, :contato, :logo_path, :is_matriz, :matriz_id)');
            $stmt->execute([
                'nome_empresa' => $data['nome_empresa'],
                'cnpj' => $data['CNPJ'],
                'contato' => $data['contato'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'is_matriz' => $data['is_matriz'] ?? 1,
                'matriz_id' => $data['matriz_id'] ?? null,
            ]);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, logo_path) VALUES (:nome_empresa, :cnpj, :contato, :logo_path)');
                $stmt->execute([
                    'nome_empresa' => $data['nome_empresa'],
                    'cnpj' => $data['CNPJ'],
                    'contato' => $data['contato'] ?? null,
                    'logo_path' => $data['logo_path'] ?? null,
                ]);
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome_empresa, :cnpj, :contato)');
                $stmt->execute([
                    'nome_empresa' => $data['nome_empresa'],
                    'cnpj' => $data['CNPJ'],
                    'contato' => $data['contato'] ?? null,
                ]);
            }
        }
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->canAccessClienteId($id)) {
            return false;
        }
        $this->ensureColumns();
        try {
            $stmt = $this->db->prepare('UPDATE clientes SET nome_empresa = :nome_empresa, CNPJ = :cnpj, contato = :contato, logo_path = :logo_path, is_matriz = :is_matriz, matriz_id = :matriz_id WHERE id = :id');
            return $stmt->execute([
                'nome_empresa' => $data['nome_empresa'],
                'cnpj' => $data['CNPJ'],
                'contato' => $data['contato'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'is_matriz' => $data['is_matriz'] ?? 1,
                'matriz_id' => $data['matriz_id'] ?? null,
                'id' => $id,
            ]);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare('UPDATE clientes SET nome_empresa = :nome_empresa, CNPJ = :cnpj, contato = :contato, logo_path = :logo_path WHERE id = :id');
                return $stmt->execute([
                    'nome_empresa' => $data['nome_empresa'],
                    'cnpj' => $data['CNPJ'],
                    'contato' => $data['contato'] ?? null,
                    'logo_path' => $data['logo_path'] ?? null,
                    'id' => $id,
                ]);
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare('UPDATE clientes SET nome_empresa = :nome_empresa, CNPJ = :cnpj, contato = :contato WHERE id = :id');
                return $stmt->execute([
                    'nome_empresa' => $data['nome_empresa'],
                    'cnpj' => $data['CNPJ'],
                    'contato' => $data['contato'] ?? null,
                    'id' => $id,
                ]);
            }
        }
    }

    public function delete(int $id): bool
    {
        if (!$this->canAccessClienteId($id)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function matrizes(): array
    {
        $this->ensureColumns();
        [$scopeCond, $params] = $this->scopedParams('cm');
        $where = 'is_matriz = 1 AND ' . $scopeCond;
        try {
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id FROM clientes WHERE $where ORDER BY nome_empresa");
            $stmt->execute($params);
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes WHERE $where ORDER BY nome_empresa");
                $stmt->execute($params);
            } catch (\PDOException $e2) {
                $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE $where ORDER BY nome_empresa");
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
            $params = ['mid' => $matrizId];
            $scope = $this->tenantInCondition('id', $params, 'cf');
            $stmt = $this->db->prepare("SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE is_matriz = 0 AND matriz_id = :mid AND $scope ORDER BY nome_empresa");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}
