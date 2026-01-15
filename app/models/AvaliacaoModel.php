<?php
namespace App\Models;
 
class AvaliacaoModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS avaliacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NULL,
                empresa_nome VARCHAR(255) NULL,
                contato VARCHAR(255) NULL,
                respostas_json TEXT NULL,
                nota_financeiro TINYINT NOT NULL DEFAULT 0,
                nota_mercado TINYINT NOT NULL DEFAULT 0,
                nota_pessoas TINYINT NOT NULL DEFAULT 0,
                nota_processo TINYINT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } catch (\PDOException $e) {}
    }
 
    public function all(): array
    {
        $this->ensureTable();
        $sql = 'SELECT a.*, c.nome_empresa AS cliente_nome
                FROM avaliacoes a
                LEFT JOIN clientes c ON c.id = a.cliente_id
                ORDER BY a.created_at DESC';
        return $this->db->query($sql)->fetchAll();
    }
 
    public function byCliente(int $clienteId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM avaliacoes WHERE cliente_id = :cid ORDER BY created_at DESC');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }
 
    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM avaliacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
 
    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO avaliacoes (cliente_id, empresa_nome, contato, respostas_json, nota_financeiro, nota_mercado, nota_pessoas, nota_processo) VALUES (:cliente_id, :empresa_nome, :contato, :respostas_json, :nota_financeiro, :nota_mercado, :nota_pessoas, :nota_processo)');
        $stmt->execute([
            'cliente_id' => $data['cliente_id'] ?? null,
            'empresa_nome' => $data['empresa_nome'] ?? null,
            'contato' => $data['contato'] ?? null,
            'respostas_json' => $data['respostas_json'] ?? null,
            'nota_financeiro' => (int)($data['nota_financeiro'] ?? 0),
            'nota_mercado' => (int)($data['nota_mercado'] ?? 0),
            'nota_pessoas' => (int)($data['nota_pessoas'] ?? 0),
            'nota_processo' => (int)($data['nota_processo'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }
}
