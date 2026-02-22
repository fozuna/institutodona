<?php
namespace App\Models;

class IndicadorModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS indicadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                nome VARCHAR(180) NOT NULL,
                unidade VARCHAR(32) NULL,
                referencia DATE NULL,
                meta DECIMAL(14,2) NOT NULL DEFAULT 0,
                realizado DECIMAL(14,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ind_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function all(): array
    {
        $this->ensure();
        $stmt = $this->db->query('SELECT i.*, c.nome_empresa AS cliente_nome FROM indicadores i JOIN clientes c ON c.id = i.cliente_id ORDER BY cliente_nome, nome, referencia');
        return $stmt->fetchAll();
    }

    public function byCliente(int $clienteId): array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT i.*, c.nome_empresa AS cliente_nome FROM indicadores i JOIN clientes c ON c.id = i.cliente_id WHERE cliente_id = :cid ORDER BY referencia IS NULL, referencia, nome');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensure();
        $stmt = $this->db->prepare('SELECT i.*, c.nome_empresa AS cliente_nome FROM indicadores i JOIN clientes c ON c.id = i.cliente_id WHERE i.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensure();
        $stmt = $this->db->prepare('INSERT INTO indicadores (cliente_id, nome, unidade, referencia, meta, realizado) VALUES (:cliente_id, :nome, :unidade, :referencia, :meta, :realizado)');
        $stmt->execute([
            'cliente_id' => $data['cliente_id'],
            'nome' => $data['nome'],
            'unidade' => $data['unidade'] ?? null,
            'referencia' => $data['referencia'] ?? null,
            'meta' => $data['meta'] ?? 0,
            'realizado' => $data['realizado'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensure();
        $stmt = $this->db->prepare('UPDATE indicadores SET cliente_id = :cliente_id, nome = :nome, unidade = :unidade, referencia = :referencia, meta = :meta, realizado = :realizado WHERE id = :id');
        return $stmt->execute([
            'cliente_id' => $data['cliente_id'],
            'nome' => $data['nome'],
            'unidade' => $data['unidade'] ?? null,
            'referencia' => $data['referencia'] ?? null,
            'meta' => $data['meta'] ?? 0,
            'realizado' => $data['realizado'] ?? 0,
            'id' => $id,
        ]);
    }

    public function updateRealizado(int $id, float $realizado): bool
    {
        $this->ensure();
        $stmt = $this->db->prepare('UPDATE indicadores SET realizado = :realizado WHERE id = :id');
        return $stmt->execute(['realizado' => $realizado, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->ensure();
        $stmt = $this->db->prepare('DELETE FROM indicadores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
