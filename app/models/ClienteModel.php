<?php
namespace App\Models;

class ClienteModel extends BaseModel
{
    private function ensureColumns(): void
    {
        try {
            if (!\App\Database\Database::columnExists('clientes', 'is_matriz')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN is_matriz TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!\App\Database\Database::columnExists('clientes', 'matriz_id')) {
                $this->db->exec('ALTER TABLE clientes ADD COLUMN matriz_id INT NULL');
            }
        } catch (\PDOException $e) {
            // silencioso
        }
    }

    public function all(): array
    {
        $this->ensureColumns();
        try {
            $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id FROM clientes ORDER BY nome_empresa');
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes ORDER BY nome_empresa');
            } catch (\PDOException $e2) {
                $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato FROM clientes ORDER BY nome_empresa');
            }
        }
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
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
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function matrizes(): array
    {
        $this->ensureColumns();
        try {
            $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato, logo_path, is_matriz, matriz_id FROM clientes WHERE is_matriz = 1 ORDER BY nome_empresa');
        } catch (\PDOException $e) {
            try {
                $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato, logo_path FROM clientes WHERE is_matriz = 1 ORDER BY nome_empresa');
            } catch (\PDOException $e2) {
                $stmt = $this->db->query('SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE is_matriz = 1 ORDER BY nome_empresa');
            }
        }
        return $stmt->fetchAll();
    }

    public function filiaisByMatriz(int $matrizId): array
    {
        $this->ensureColumns();
        try {
            $stmt = $this->db->prepare('SELECT id, nome_empresa, CNPJ, contato FROM clientes WHERE is_matriz = 0 AND matriz_id = :mid ORDER BY nome_empresa');
            $stmt->execute(['mid' => $matrizId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}
