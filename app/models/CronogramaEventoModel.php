<?php
namespace App\Models;

use App\Core\AuditLogger;
use App\Database\Database;
use DateTimeImmutable;
use RuntimeException;

class CronogramaEventoModel extends BaseModel
{
    private const PERIODICIDADES = [
        'unico' => 0,
        'mensal' => 1,
        'bimestral' => 2,
        'trimestral' => 3,
        'semestral' => 6,
        'anual' => 12,
    ];

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
              evento_pai_id INT NULL,
              data DATE NOT NULL,
              periodicidade VARCHAR(20) NOT NULL DEFAULT 'unico',
              topico VARCHAR(120) NOT NULL,
              unidade VARCHAR(120) NULL,
              atividade VARCHAR(255) NOT NULL,
              responsavel VARCHAR(255) NULL,
              modelo ENUM('Online','Presencial') NULL,
              status ENUM('Planejado','Realizado','Não Realizado') NOT NULL DEFAULT 'Planejado'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('cronograma_eventos', 'evento_pai_id')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN evento_pai_id INT NULL AFTER id_cronograma");
            }
            if (!Database::columnExists('cronograma_eventos', 'periodicidade')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN periodicidade VARCHAR(20) NOT NULL DEFAULT 'unico' AFTER data");
            }
        } catch (\PDOException $e) {
            // ignore
        }
    }

    public function byCronograma(int $idCronograma): array
    {
        $this->ensureTables();
        $params = ['id' => $idCronograma];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceb');
        $stmt = $this->db->prepare("SELECT
                ce.id,
                ce.id_cronograma,
                ce.evento_pai_id,
                COALESCE(ce.evento_pai_id, ce.id) AS serie_id,
                ce.data,
                ce.periodicidade,
                ce.topico,
                ce.unidade,
                ce.atividade,
                ce.responsavel,
                ce.modelo,
                ce.status
            FROM cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            WHERE ce.id_cronograma = :id AND $scope
            ORDER BY COALESCE(ce.evento_pai_id, ce.id), ce.data, ce.id");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensureTables();
        $params = ['id' => $id];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'cef');
        $stmt = $this->db->prepare("SELECT
                ce.*,
                COALESCE(ce.evento_pai_id, ce.id) AS serie_id,
                cr.ano,
                cr.id_cliente
            FROM cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            WHERE ce.id = :id AND $scope
            LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $idCronograma, array $data): int
    {
        $this->ensureTables();
        $cronograma = $this->fetchCronograma($idCronograma);
        if (!$cronograma) {
            return 0;
        }

        $payload = $this->normalizePayload($data, (int)$cronograma['ano']);
        $dates = $this->buildOccurrenceDates($payload['data'], $payload['periodicidade'], (int)$cronograma['ano']);
        $this->assertNoDuplicateOccurrences($idCronograma, $payload, $dates);

        try {
            $this->db->beginTransaction();
            $rootId = $this->insertOccurrence($idCronograma, null, $payload, $dates[0]);
            for ($i = 1; $i < count($dates); $i++) {
                $this->insertOccurrence($idCronograma, $rootId, $payload, $dates[$i]);
            }
            $this->db->commit();
            AuditLogger::log('cronograma_evento_created', 'cronograma_evento', $rootId, [
                'id_cronograma' => $idCronograma,
                'periodicidade' => $payload['periodicidade'],
                'ocorrencias' => count($dates),
            ]);
            return $rootId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            AuditLogger::log('cronograma_evento_create_error', 'cronograma_evento', null, [
                'id_cronograma' => $idCronograma,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function update(int $id, array $data, string $scope = 'evento'): bool
    {
        $this->ensureTables();
        $event = $this->find($id);
        if (!$event) {
            return false;
        }
        $scope = $scope === 'serie' ? 'serie' : 'evento';
        return $scope === 'serie'
            ? $this->updateSeries($event, $data)
            : $this->updateSingleOccurrence($event, $data);
    }

    public function delete(int $id, string $scope = 'evento'): bool
    {
        $this->ensureTables();
        $event = $this->find($id);
        if (!$event) {
            return false;
        }
        $scope = $scope === 'serie' ? 'serie' : 'evento';
        return $scope === 'serie'
            ? $this->deleteSeries((int)$event['serie_id'])
            : $this->deleteSingleOccurrence($event);
    }

    public function setStatus(int $id, string $status): bool
    {
        $this->ensureTables();
        if (!in_array($status, ['Planejado', 'Realizado', 'Não Realizado'], true)) {
            return false;
        }
        $params = [
            'id' => $id,
            'status' => $status,
        ];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'cests');
        $stmt = $this->db->prepare("UPDATE cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            SET ce.status = :status
            WHERE ce.id = :id AND $scope");
        return $stmt->execute($params);
    }

    public function seriesMembers(int $rootId): array
    {
        $this->ensureTables();
        $params = ['root_id' => $rootId, 'parent_root_id' => $rootId];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ces');
        $stmt = $this->db->prepare("SELECT
                ce.*,
                COALESCE(ce.evento_pai_id, ce.id) AS serie_id
            FROM cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            WHERE (ce.id = :root_id OR ce.evento_pai_id = :parent_root_id) AND $scope
            ORDER BY ce.data, ce.id");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function fetchCronograma(int $idCronograma): ?array
    {
        $params = ['id' => $idCronograma];
        $scope = $this->tenantInCondition('id_cliente', $params, 'cecr');
        $stmt = $this->db->prepare("SELECT * FROM cronogramas WHERE id = :id AND $scope LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function normalizePayload(array $data, int $ano): array
    {
        $payload = [
            'data' => trim((string)($data['data'] ?? '')),
            'periodicidade' => $this->normalizePeriodicidade($data['periodicidade'] ?? 'unico'),
            'topico' => trim((string)($data['topico'] ?? '')),
            'unidade' => trim((string)($data['unidade'] ?? '')),
            'atividade' => trim((string)($data['atividade'] ?? '')),
            'responsavel' => trim((string)($data['responsavel'] ?? '')),
            'modelo' => in_array(($data['modelo'] ?? ''), ['Online', 'Presencial'], true) ? $data['modelo'] : null,
            'status' => in_array(($data['status'] ?? ''), ['Planejado', 'Realizado', 'Não Realizado'], true) ? $data['status'] : 'Planejado',
        ];
        if ($payload['data'] === '' || $payload['topico'] === '' || $payload['atividade'] === '') {
            throw new RuntimeException('Preencha data, pilar e atividade para salvar o evento.');
        }
        $date = new DateTimeImmutable($payload['data']);
        if ((int)$date->format('Y') !== $ano) {
            throw new RuntimeException('A data base deve pertencer ao ano do cronograma.');
        }
        return $payload;
    }

    private function normalizePeriodicidade($value): string
    {
        $value = strtolower(trim((string)$value));
        return array_key_exists($value, self::PERIODICIDADES) ? $value : 'unico';
    }

    private function buildOccurrenceDates(string $baseDate, string $periodicidade, int $ano): array
    {
        $base = new DateTimeImmutable($baseDate);
        $anchorDay = (int)$base->format('d');
        $step = self::PERIODICIDADES[$periodicidade] ?? 0;
        $dates = [];
        $offset = 0;
        while (true) {
            $candidate = $this->dateForOffset($base, $offset, $anchorDay);
            $candidateYear = (int)$candidate->format('Y');
            if ($candidateYear > $ano) {
                break;
            }
            if ($candidateYear === $ano) {
                $dates[] = $candidate->format('Y-m-d');
            }
            if ($step <= 0) {
                break;
            }
            $offset += $step;
        }
        $dates = array_values(array_unique($dates));
        if (empty($dates)) {
            throw new RuntimeException('Nenhuma ocorrência válida foi gerada para o ano do cronograma.');
        }
        return $dates;
    }

    private function dateForOffset(DateTimeImmutable $base, int $monthsOffset, int $anchorDay): DateTimeImmutable
    {
        $baseYear = (int)$base->format('Y');
        $baseMonth = (int)$base->format('n');
        $absoluteMonth = ($baseYear * 12 + ($baseMonth - 1)) + $monthsOffset;
        $year = intdiv($absoluteMonth, 12);
        $month = ($absoluteMonth % 12) + 1;
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $day = min($anchorDay, $lastDay);
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    private function insertOccurrence(int $idCronograma, ?int $eventoPaiId, array $payload, string $date): int
    {
        $stmt = $this->db->prepare('INSERT INTO cronograma_eventos (id_cronograma, evento_pai_id, data, periodicidade, topico, unidade, atividade, responsavel, modelo, status) VALUES (:id_cronograma, :evento_pai_id, :data, :periodicidade, :topico, :unidade, :atividade, :responsavel, :modelo, :status)');
        $stmt->execute([
            'id_cronograma' => $idCronograma,
            'evento_pai_id' => $eventoPaiId,
            'data' => $date,
            'periodicidade' => $payload['periodicidade'],
            'topico' => $payload['topico'],
            'unidade' => $payload['unidade'] !== '' ? $payload['unidade'] : null,
            'atividade' => $payload['atividade'],
            'responsavel' => $payload['responsavel'] !== '' ? $payload['responsavel'] : null,
            'modelo' => $payload['modelo'],
            'status' => $payload['status'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function assertNoDuplicateOccurrences(int $idCronograma, array $payload, array $dates, array $ignoreIds = []): void
    {
        $ignoreIds = array_values(array_unique(array_filter(array_map('intval', $ignoreIds))));
        foreach ($dates as $date) {
            $params = [
                'id_cronograma' => $idCronograma,
                'data' => $date,
                'topico' => $payload['topico'],
                'unidade' => $payload['unidade'] !== '' ? $payload['unidade'] : null,
                'atividade' => $payload['atividade'],
                'responsavel' => $payload['responsavel'] !== '' ? $payload['responsavel'] : null,
                'modelo' => $payload['modelo'],
            ];
            $sql = "SELECT COUNT(*) FROM cronograma_eventos
                WHERE id_cronograma = :id_cronograma
                  AND data = :data
                  AND topico = :topico
                  AND IFNULL(unidade, '') = IFNULL(:unidade, '')
                  AND atividade = :atividade
                  AND IFNULL(responsavel, '') = IFNULL(:responsavel, '')
                  AND IFNULL(modelo, '') = IFNULL(:modelo, '')";
            if (!empty($ignoreIds)) {
                $holders = [];
                foreach ($ignoreIds as $i => $ignoreId) {
                    $key = 'ignore_' . $i;
                    $holders[] = ':' . $key;
                    $params[$key] = $ignoreId;
                }
                $sql .= ' AND id NOT IN (' . implode(',', $holders) . ')';
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Ja existe um evento igual nesta data para o cronograma selecionado.');
            }
        }
    }

    private function updateSingleOccurrence(array $event, array $data): bool
    {
        $payload = $this->normalizePayload($data, (int)$event['ano']);
        $payload['periodicidade'] = (string)($event['periodicidade'] ?? 'unico');
        $this->assertNoDuplicateOccurrences((int)$event['id_cronograma'], $payload, [$payload['data']], [(int)$event['id']]);
        $params = [
            'data' => $payload['data'],
            'periodicidade' => $payload['periodicidade'],
            'topico' => $payload['topico'],
            'unidade' => $payload['unidade'] !== '' ? $payload['unidade'] : null,
            'atividade' => $payload['atividade'],
            'responsavel' => $payload['responsavel'] !== '' ? $payload['responsavel'] : null,
            'modelo' => $payload['modelo'],
            'status' => $payload['status'],
            'id' => (int)$event['id'],
        ];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceu');
        $stmt = $this->db->prepare("UPDATE cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            SET ce.data = :data,
                ce.periodicidade = :periodicidade,
                ce.topico = :topico,
                ce.unidade = :unidade,
                ce.atividade = :atividade,
                ce.responsavel = :responsavel,
                ce.modelo = :modelo,
                ce.status = :status
            WHERE ce.id = :id AND $scope");
        return $stmt->execute($params);
    }

    private function updateSeries(array $event, array $data): bool
    {
        $rootId = (int)$event['serie_id'];
        $root = $this->find($rootId);
        if (!$root) {
            return false;
        }
        $series = $this->seriesMembers($rootId);
        $ignoreIds = array_map(static fn(array $row): int => (int)$row['id'], $series);
        $payload = $this->normalizePayload($data, (int)$root['ano']);
        $dates = $this->buildOccurrenceDates($payload['data'], $payload['periodicidade'], (int)$root['ano']);
        $this->assertNoDuplicateOccurrences((int)$root['id_cronograma'], $payload, $dates, $ignoreIds);

        try {
            $this->db->beginTransaction();
            $updateRoot = $this->db->prepare("UPDATE cronograma_eventos
                SET data = :data,
                    periodicidade = :periodicidade,
                    topico = :topico,
                    unidade = :unidade,
                    atividade = :atividade,
                    responsavel = :responsavel,
                    modelo = :modelo,
                    status = :status,
                    evento_pai_id = NULL
                WHERE id = :id");
            $updateRoot->execute([
                'data' => $dates[0],
                'periodicidade' => $payload['periodicidade'],
                'topico' => $payload['topico'],
                'unidade' => $payload['unidade'] !== '' ? $payload['unidade'] : null,
                'atividade' => $payload['atividade'],
                'responsavel' => $payload['responsavel'] !== '' ? $payload['responsavel'] : null,
                'modelo' => $payload['modelo'],
                'status' => $payload['status'],
                'id' => $rootId,
            ]);
            $deleteChildren = $this->db->prepare('DELETE FROM cronograma_eventos WHERE evento_pai_id = :root_id');
            $deleteChildren->execute(['root_id' => $rootId]);
            for ($i = 1; $i < count($dates); $i++) {
                $this->insertOccurrence((int)$root['id_cronograma'], $rootId, $payload, $dates[$i]);
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function deleteSeries(int $rootId): bool
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('DELETE FROM cronograma_eventos WHERE id = :root_id OR evento_pai_id = :parent_root_id');
            $stmt->execute(['root_id' => $rootId, 'parent_root_id' => $rootId]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function deleteSingleOccurrence(array $event): bool
    {
        $id = (int)$event['id'];
        $rootId = (int)$event['serie_id'];
        if ((int)($event['evento_pai_id'] ?? 0) > 0) {
            $stmt = $this->db->prepare('DELETE FROM cronograma_eventos WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        }

        $series = $this->seriesMembers($rootId);
        if (count($series) <= 1) {
            $stmt = $this->db->prepare('DELETE FROM cronograma_eventos WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        }

        $replacement = null;
        foreach ($series as $member) {
            if ((int)$member['id'] !== $id) {
                $replacement = $member;
                break;
            }
        }
        if (!$replacement) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $newRootId = (int)$replacement['id'];
            $promote = $this->db->prepare('UPDATE cronograma_eventos SET evento_pai_id = NULL WHERE id = :id');
            $promote->execute(['id' => $newRootId]);
            $repoint = $this->db->prepare('UPDATE cronograma_eventos SET evento_pai_id = :new_root_set WHERE evento_pai_id = :old_root AND id <> :new_root_filter');
            $repoint->execute([
                'new_root_set' => $newRootId,
                'old_root' => $id,
                'new_root_filter' => $newRootId,
            ]);
            $deleteOld = $this->db->prepare('DELETE FROM cronograma_eventos WHERE id = :id');
            $deleteOld->execute(['id' => $id]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
