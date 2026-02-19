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
        $stmt = $this->db->query('SELECT c.id, c.nome, c.ano, cli.nome_empresa AS cliente, c.id_cliente FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente ORDER BY cli.nome_empresa, c.ano DESC');
        return $stmt->fetchAll();
    }

    public function byCliente(int $idCliente): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id, nome, ano FROM cronogramas WHERE id_cliente = :id ORDER BY ano DESC');
        $stmt->execute(['id' => $idCliente]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT c.id, c.nome, c.ano, c.id_cliente, cli.nome_empresa AS cliente FROM cronogramas c JOIN clientes cli ON cli.id = c.id_cliente WHERE c.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:id_cliente, :nome, :ano)');
        $stmt->execute([
            'id_cliente' => $data['id_cliente'],
            'nome' => $data['nome'] ?? null,
            'ano' => $data['ano'],
        ]);
        return (int)$this->db->lastInsertId();
    }
}
