<?php
namespace App\Models;

use App\Core\AuditLogger;

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
        $params = ['id' => $idCronograma];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceb');
        $stmt = $this->db->prepare("SELECT ce.id, ce.data, ce.topico, ce.unidade, ce.atividade, ce.responsavel, ce.modelo, ce.status FROM cronograma_eventos ce JOIN cronogramas cr ON cr.id = ce.id_cronograma WHERE ce.id_cronograma = :id AND $scope ORDER BY ce.data");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(int $idCronograma, array $data): int
    {
        $this->ensureTables();
        try {
            $scopeParams = ['id' => $idCronograma];
            $scope = $this->tenantInCondition('id_cliente', $scopeParams, 'cec');
            $check = $this->db->prepare("SELECT id FROM cronogramas WHERE id = :id AND $scope LIMIT 1");
            $check->execute($scopeParams);
            if (!$check->fetch()) {
                return 0;
            }
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
            $id = (int)$this->db->lastInsertId();
            AuditLogger::log('cronograma_evento_created', 'cronograma_evento', $id, [
                'id_cronograma' => $idCronograma,
            ]);
            return $id;
        } catch (\PDOException $e) {
            AuditLogger::log('cronograma_evento_create_error', 'cronograma_evento', null, [
                'id_cronograma' => $idCronograma,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTables();
        $params = [
            'data' => $data['data'],
            'topico' => $data['topico'],
            'unidade' => $data['unidade'] ?? null,
            'atividade' => $data['atividade'],
            'responsavel' => $data['responsavel'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'status' => $data['status'] ?? 'Planejado',
            'id' => $id,
        ];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceu');
        $stmt = $this->db->prepare("UPDATE cronograma_eventos ce JOIN cronogramas cr ON cr.id = ce.id_cronograma SET ce.data = :data, ce.topico = :topico, ce.unidade = :unidade, ce.atividade = :atividade, ce.responsavel = :responsavel, ce.modelo = :modelo, ce.status = :status WHERE ce.id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $this->ensureTables();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ced');
        $stmt = $this->db->prepare("DELETE ce FROM cronograma_eventos ce JOIN cronogramas cr ON cr.id = ce.id_cronograma WHERE ce.id = :id AND $scope");
        return $stmt->execute($params);
    }
}
