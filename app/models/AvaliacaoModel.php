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
            if (!\App\Database\Database::columnExists('avaliacoes', 'contato')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN contato VARCHAR(255) NULL AFTER empresa_nome');
            }
            if (!\App\Database\Database::columnExists('avaliacoes', 'respostas_json')) {
                $this->db->exec('ALTER TABLE avaliacoes ADD COLUMN respostas_json TEXT NULL AFTER contato');
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
        $scope = $this->tenantInCondition('a.cliente_id', $params, 'avall');
        $sql = 'SELECT a.*, c.nome_empresa AS cliente_nome
                FROM avaliacoes a
                LEFT JOIN clientes c ON c.id = a.cliente_id
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
        $scope = $this->tenantInCondition('cliente_id', $params, 'avf');
        $stmt = $this->db->prepare("SELECT * FROM avaliacoes WHERE id = :id AND $scope");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
 
    public function create(array $data): int
    {
        $this->ensureTable();
        $data['cliente_id'] = $this->normalizeScopedClienteId(isset($data['cliente_id']) ? (int)$data['cliente_id'] : null);
        if (($data['cliente_id'] ?? 0) <= 0 || !$this->canAccessClienteId((int)$data['cliente_id'])) {
            return 0;
        }
        $stmt = $this->db->prepare('INSERT INTO avaliacoes (cliente_id, empresa_nome, contato, respostas_json, nota_financeiro, nota_mercado, nota_pessoas, nota_processo, realidade_financeiro, realidade_mercado, realidade_pessoas, realidade_processo) VALUES (:cliente_id, :empresa_nome, :contato, :respostas_json, :nota_financeiro, :nota_mercado, :nota_pessoas, :nota_processo, :realidade_financeiro, :realidade_mercado, :realidade_pessoas, :realidade_processo)');
        $stmt->execute([
            'cliente_id' => $data['cliente_id'] ?? null,
            'empresa_nome' => $data['empresa_nome'] ?? null,
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
        ]);
        return (int)$this->db->lastInsertId();
    }
}
