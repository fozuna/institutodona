<?php
namespace App\Models;

class CronogramaModel extends BaseModel
{
    private function ensureTable(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS cronogramas (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_cliente INT NOT NULL,
              nome VARCHAR(255) NULL,
              ano INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
            // ignore
        }
    }

    public function all(): array
    {
        $this->ensureTable();
        $params = [];
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'cra');
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, cli.nome_empresa AS cliente, c.id_cliente FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE $scope ORDER BY cli.nome_empresa, c.ano DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function byCliente(int $idCliente): array
    {
        $this->ensureTable();
        $params = ['id' => $idCliente];
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'crb');
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, cli.nome_empresa AS cliente, c.id_cliente FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE c.id_cliente = :id AND $scope ORDER BY c.ano DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('c.id_cliente', $params, 'crf');
        $stmt = $this->db->prepare("SELECT c.id, c.nome, c.ano, c.id_cliente, cli.nome_empresa AS cliente FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE c.id = :id AND $scope");
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
        $stmt = $this->db->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:id_cliente, :nome, :ano)');
        $stmt->execute([
            'id_cliente' => $data['id_cliente'],
            'nome' => $data['nome'] ?? null,
            'ano' => $data['ano'],
        ]);
        return (int)$this->db->lastInsertId();
    }
}
