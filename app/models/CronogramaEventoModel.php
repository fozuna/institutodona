<?php
namespace App\Models;

class CronogramaEventoModel extends BaseModel
{
    private function ensureTables(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS cronogramas (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_cliente INT NOT NULL,
              nome VARCHAR(255) NULL,
              ano INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS cronograma_eventos (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_cronograma INT NOT NULL,
              data DATE NOT NULL,
              topico VARCHAR(120) NOT NULL,
              unidade VARCHAR(120) NULL,
              atividade VARCHAR(255) NOT NULL,
              responsavel VARCHAR(255) NULL,
              modelo ENUM('Online','Presencial') NULL,
              status ENUM('Planejado','Realizado','Não Realizado') NOT NULL DEFAULT 'Planejado'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {
            // ignore
        }
    }

    public function byCronograma(int $idCronograma): array
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('SELECT id, data, topico, unidade, atividade, responsavel, modelo, status FROM cronograma_eventos WHERE id_cronograma = :id ORDER BY data');
        $stmt->execute(['id' => $idCronograma]);
        return $stmt->fetchAll();
    }

    public function create(int $idCronograma, array $data): int
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('INSERT INTO cronograma_eventos (id_cronograma, data, topico, unidade, atividade, responsavel, modelo, status) VALUES (:id_cronograma, :data, :topico, :unidade, :atividade, :responsavel, :modelo, :status)');
        $stmt->execute([
            'id_cronograma' => $idCronograma,
            'data' => $data['data'],
            'topico' => $data['topico'],
            'unidade' => $data['unidade'] ?? null,
            'atividade' => $data['atividade'],
            'responsavel' => $data['responsavel'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'status' => $data['status'] ?? 'Planejado',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('UPDATE cronograma_eventos SET data = :data, topico = :topico, unidade = :unidade, atividade = :atividade, responsavel = :responsavel, modelo = :modelo, status = :status WHERE id = :id');
        return $stmt->execute([
            'data' => $data['data'],
            'topico' => $data['topico'],
            'unidade' => $data['unidade'] ?? null,
            'atividade' => $data['atividade'],
            'responsavel' => $data['responsavel'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'status' => $data['status'] ?? 'Planejado',
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('DELETE FROM cronograma_eventos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
