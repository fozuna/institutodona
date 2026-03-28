<?php
namespace App\Models;

class SetorMetricaModel extends BaseModel
{
    private function ensure(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS setor_metricas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setor_id INT NOT NULL,
                ano_mes CHAR(7) NOT NULL,
                total_validas INT NOT NULL DEFAULT 0,
                total_conforme INT NOT NULL DEFAULT 0,
                pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_setor_ano_mes (setor_id, ano_mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\PDOException $e) {}
    }

    public function registrarConclusao(int $setorId, array $stats, ?string $data = null): void
    {
        $this->ensure();
        $anoMes = ($data ? substr($data, 0, 7) : date('Y-m'));
        $stmt = $this->db->prepare("INSERT INTO setor_metricas (setor_id, ano_mes, total_validas, total_conforme, pct)
            VALUES (:sid, :am, :tv, :tc, :pct)
            ON DUPLICATE KEY UPDATE
              total_validas = total_validas + VALUES(total_validas),
              total_conforme = total_conforme + VALUES(total_conforme),
              pct = CASE
                WHEN (total_validas + VALUES(total_validas)) > 0
                THEN ROUND((total_conforme + VALUES(total_conforme)) / (total_validas + VALUES(total_validas)) * 100, 2)
                ELSE 0.00 END");
        $stmt->execute([
            'sid' => $setorId,
            'am' => $anoMes,
            'tv' => (int)($stats['validas'] ?? 0),
            'tc' => (int)($stats['conforme'] ?? 0),
            'pct' => (float)($stats['pct'] ?? 0.0),
        ]);
    }

    public function series(int $setorId, string $inicio, string $fim): array
    {
        $this->ensure();
        $stmt = $this->db->prepare("SELECT ano_mes, pct, total_validas FROM setor_metricas WHERE setor_id = :sid AND ano_mes BETWEEN :i AND :f ORDER BY ano_mes");
        $stmt->execute(['sid' => $setorId, 'i' => $inicio, 'f' => $fim]);
        return $stmt->fetchAll();
    }
}
