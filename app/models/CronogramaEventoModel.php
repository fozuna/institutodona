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

    private const STATUS_VALUES = [
        'Planejado',
        'Pendente',
        'Andamento',
        'Adiado',
        'Finalizado',
    ];

    private const LEGACY_STATUS_MAP = [
        'Realizado' => 'Finalizado',
        'Não Realizado' => 'Pendente',
    ];

    private const EVENT_TYPES = [
        'Tarefa',
        'Reunião',
        'Indicador',
        'Pessoas',
        'Processos',
        'Treinamento',
        'Auditoria',
        'Coaching',
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
              tipo_evento VARCHAR(30) NOT NULL DEFAULT 'Tarefa',
              topico VARCHAR(120) NOT NULL,
              unidade VARCHAR(120) NULL,
              atividade VARCHAR(255) NOT NULL,
              responsavel VARCHAR(255) NULL,
              modelo ENUM('Online','Presencial') NULL,
              status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
              ata_path VARCHAR(255) NULL,
              ata_original_name VARCHAR(255) NULL,
              ata_mime VARCHAR(100) NULL,
              ata_size INT UNSIGNED NULL,
              ata_sha256 CHAR(64) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!Database::columnExists('cronograma_eventos', 'evento_pai_id')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN evento_pai_id INT NULL AFTER id_cronograma");
            }
            if (!Database::columnExists('cronograma_eventos', 'periodicidade')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN periodicidade VARCHAR(20) NOT NULL DEFAULT 'unico' AFTER data");
            }
            if (!Database::columnExists('cronograma_eventos', 'tipo_evento')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN tipo_evento VARCHAR(30) NOT NULL DEFAULT 'Tarefa' AFTER periodicidade");
            }
            if (!Database::columnExists('cronograma_eventos', 'ata_path')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN ata_path VARCHAR(255) NULL AFTER status");
            }
            if (!Database::columnExists('cronograma_eventos', 'ata_original_name')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN ata_original_name VARCHAR(255) NULL AFTER ata_path");
            }
            if (!Database::columnExists('cronograma_eventos', 'ata_mime')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN ata_mime VARCHAR(100) NULL AFTER ata_original_name");
            }
            if (!Database::columnExists('cronograma_eventos', 'ata_size')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN ata_size INT UNSIGNED NULL AFTER ata_mime");
            }
            if (!Database::columnExists('cronograma_eventos', 'ata_sha256')) {
                $this->db->exec("ALTER TABLE cronograma_eventos ADD COLUMN ata_sha256 CHAR(64) NULL AFTER ata_size");
            }
            if (Database::columnExists('cronograma_eventos', 'status')) {
                $type = Database::columnType('cronograma_eventos', 'status');
                if (is_string($type) && stripos($type, 'enum(') === 0) {
                    $this->db->exec("ALTER TABLE cronograma_eventos MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Planejado'");
                }
            }
        } catch (\PDOException $e) {
            // ignore
        }
    }

    public static function eventTypeOptions(): array
    {
        return self::EVENT_TYPES;
    }

    public static function normalizeEventType(?string $value): string
    {
        $value = trim((string)$value);
        return in_array($value, self::EVENT_TYPES, true) ? $value : 'Tarefa';
    }

    public static function validateAtaUpload(string $clientFilename, int $sizeBytes, string $detectedMime, int $maxBytes = 52428800): array
    {
        $validated = ManualModel::validateUpload($clientFilename, $sizeBytes, $detectedMime, $maxBytes);
        if (empty($validated['ok'])) {
            return $validated;
        }
        $ext = (string)($validated['ext'] ?? '');
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'], true)) {
            return ['ok' => false, 'error' => 'ext_invalid', 'message' => 'Tipo de arquivo inválido para ata.'];
        }
        return $validated;
    }

    public function byCronograma(int $idCronograma): array
    {
        $this->ensureTables();
        $params = ['id' => $idCronograma];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceb');
        $select = [
            'ce.id',
            'ce.id_cronograma',
            'ce.evento_pai_id',
            'COALESCE(ce.evento_pai_id, ce.id) AS serie_id',
            'ce.data',
            'ce.periodicidade',
            'ce.topico',
            'ce.unidade',
            'ce.atividade',
            'ce.responsavel',
            'ce.modelo',
            'ce.status',
        ];
        if (Database::columnExists('cronograma_eventos', 'tipo_evento')) {
            $select[] = 'ce.tipo_evento';
        }
        if (Database::columnExists('cronograma_eventos', 'ata_path')) {
            $select[] = 'ce.ata_path';
            $select[] = 'ce.ata_original_name';
            $select[] = 'ce.ata_mime';
            $select[] = 'ce.ata_size';
            $select[] = 'ce.ata_sha256';
        }
        $sql = "SELECT " . implode(",\n                ", $select) . "
            FROM cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            WHERE ce.id_cronograma = :id AND $scope
            ORDER BY COALESCE(ce.evento_pai_id, ce.id), ce.data, ce.id";
        $stmt = $this->db->prepare($sql);
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

    public function deleteByCronograma(int $idCronograma): int
    {
        $this->ensureTables();
        $cronograma = $this->fetchCronograma($idCronograma);
        if (!$cronograma) {
            return 0;
        }
        $params = ['id' => $idCronograma];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'cedelcr');
        $stmt = $this->db->prepare("DELETE ce
            FROM cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            WHERE ce.id_cronograma = :id AND $scope");
        $stmt->execute($params);
        return (int)$stmt->rowCount();
    }

    public function duplicateForCronograma(int $fromCronogramaId, int $toCronogramaId): array
    {
        $this->ensureTables();
        $from = $this->fetchCronograma($fromCronogramaId);
        $to = $this->fetchCronograma($toCronogramaId);
        if (!$from || !$to) {
            return ['ok' => false, 'created' => 0];
        }

        $events = $this->byCronograma($fromCronogramaId);
        if (empty($events)) {
            return ['ok' => true, 'created' => 0];
        }

        $roots = [];
        $children = [];
        foreach ($events as $ev) {
            if (empty($ev['evento_pai_id'])) {
                $roots[] = $ev;
                continue;
            }
            $children[] = $ev;
        }

        $cols = [
            'id_cronograma',
            'evento_pai_id',
            'data',
            'periodicidade',
            'topico',
            'unidade',
            'atividade',
            'responsavel',
            'modelo',
            'status',
        ];
        if (Database::columnExists('cronograma_eventos', 'tipo_evento')) {
            $cols[] = 'tipo_evento';
        }
        if (Database::columnExists('cronograma_eventos', 'ata_path')) {
            $cols[] = 'ata_path';
            $cols[] = 'ata_original_name';
            $cols[] = 'ata_mime';
            $cols[] = 'ata_size';
            $cols[] = 'ata_sha256';
        }
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $cols);
        $insert = $this->db->prepare("INSERT INTO cronograma_eventos (" . implode(', ', $cols) . ")
            VALUES (" . implode(', ', $placeholders) . ")");

        $created = 0;
        $map = [];
        $this->db->beginTransaction();
        try {
            foreach ($roots as $ev) {
                $params = [
                    'id_cronograma' => $toCronogramaId,
                    'evento_pai_id' => null,
                    'data' => (string)($ev['data'] ?? ''),
                    'periodicidade' => (string)($ev['periodicidade'] ?? 'unico'),
                    'topico' => (string)($ev['topico'] ?? ''),
                    'unidade' => $ev['unidade'] ?? null,
                    'atividade' => (string)($ev['atividade'] ?? ''),
                    'responsavel' => $ev['responsavel'] ?? null,
                    'modelo' => $ev['modelo'] ?? null,
                    'status' => (string)($ev['status'] ?? 'Planejado'),
                ];
                if (in_array('tipo_evento', $cols, true)) {
                    $params['tipo_evento'] = (string)($ev['tipo_evento'] ?? 'Tarefa');
                }
                if (in_array('ata_path', $cols, true)) {
                    $params['ata_path'] = $ev['ata_path'] ?? null;
                    $params['ata_original_name'] = $ev['ata_original_name'] ?? null;
                    $params['ata_mime'] = $ev['ata_mime'] ?? null;
                    $params['ata_size'] = isset($ev['ata_size']) ? (int)$ev['ata_size'] : null;
                    $params['ata_sha256'] = $ev['ata_sha256'] ?? null;
                }
                $insert->execute($params);
                $newId = (int)$this->db->lastInsertId();
                $map[(int)$ev['id']] = $newId;
                $created++;
            }

            foreach ($children as $ev) {
                $oldParent = (int)($ev['evento_pai_id'] ?? 0);
                $newParent = $oldParent > 0 ? ($map[$oldParent] ?? null) : null;
                $params = [
                    'id_cronograma' => $toCronogramaId,
                    'evento_pai_id' => $newParent,
                    'data' => (string)($ev['data'] ?? ''),
                    'periodicidade' => (string)($ev['periodicidade'] ?? 'unico'),
                    'topico' => (string)($ev['topico'] ?? ''),
                    'unidade' => $ev['unidade'] ?? null,
                    'atividade' => (string)($ev['atividade'] ?? ''),
                    'responsavel' => $ev['responsavel'] ?? null,
                    'modelo' => $ev['modelo'] ?? null,
                    'status' => (string)($ev['status'] ?? 'Planejado'),
                ];
                if (in_array('tipo_evento', $cols, true)) {
                    $params['tipo_evento'] = (string)($ev['tipo_evento'] ?? 'Tarefa');
                }
                if (in_array('ata_path', $cols, true)) {
                    $params['ata_path'] = $ev['ata_path'] ?? null;
                    $params['ata_original_name'] = $ev['ata_original_name'] ?? null;
                    $params['ata_mime'] = $ev['ata_mime'] ?? null;
                    $params['ata_size'] = isset($ev['ata_size']) ? (int)$ev['ata_size'] : null;
                    $params['ata_sha256'] = $ev['ata_sha256'] ?? null;
                }
                $insert->execute($params);
                $newId = (int)$this->db->lastInsertId();
                $map[(int)$ev['id']] = $newId;
                $created++;
            }

            $this->db->commit();
            return ['ok' => true, 'created' => $created];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'created' => $created, 'error' => $e->getMessage()];
        }
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
        $status = $this->normalizeStatus($status);
        if (!in_array($status, self::STATUS_VALUES, true)) {
            return false;
        }
        $event = $this->find($id);
        if (!$event) {
            return false;
        }
        if ($status === 'Finalizado') {
            $tipo = self::normalizeEventType($event['tipo_evento'] ?? 'Tarefa');
            $ataPath = Database::columnExists('cronograma_eventos', 'ata_path') ? (string)($event['ata_path'] ?? '') : '';
            if ($tipo === 'Reunião' && ($ataPath === '' || !is_file($ataPath))) {
                throw new RuntimeException('Para finalizar eventos do tipo Reunião, é obrigatório anexar a ata antes de marcar como finalizado.');
            }
        }
        $from = $this->normalizeStatus((string)($event['status'] ?? 'Planejado'));
        if (!$this->canTransition($from, $status)) {
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

    public function setAta(int $id, array $meta): bool
    {
        $this->ensureTables();
        $event = $this->find($id);
        if (!$event) {
            return false;
        }
        if (!Database::columnExists('cronograma_eventos', 'ata_path')) {
            return false;
        }
        $params = [
            'id' => $id,
            'ata_path' => (string)($meta['ata_path'] ?? ''),
            'ata_original_name' => isset($meta['ata_original_name']) ? (string)$meta['ata_original_name'] : null,
            'ata_mime' => isset($meta['ata_mime']) ? (string)$meta['ata_mime'] : null,
            'ata_size' => isset($meta['ata_size']) ? (int)$meta['ata_size'] : null,
            'ata_sha256' => isset($meta['ata_sha256']) ? (string)$meta['ata_sha256'] : null,
        ];
        $scope = $this->tenantInCondition('cr.id_cliente', $params, 'ceata');
        $stmt = $this->db->prepare("UPDATE cronograma_eventos ce
            JOIN cronogramas cr ON cr.id = ce.id_cronograma
            SET ce.ata_path = :ata_path,
                ce.ata_original_name = :ata_original_name,
                ce.ata_mime = :ata_mime,
                ce.ata_size = :ata_size,
                ce.ata_sha256 = :ata_sha256
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
        $incomingStatus = $this->normalizeStatus((string)($data['status'] ?? 'Planejado'));
        $payload = [
            'data' => trim((string)($data['data'] ?? '')),
            'periodicidade' => $this->normalizePeriodicidade($data['periodicidade'] ?? 'unico'),
            'tipo_evento' => self::normalizeEventType($data['tipo_evento'] ?? 'Tarefa'),
            'topico' => trim((string)($data['topico'] ?? '')),
            'unidade' => trim((string)($data['unidade'] ?? '')),
            'atividade' => trim((string)($data['atividade'] ?? '')),
            'responsavel' => trim((string)($data['responsavel'] ?? '')),
            'modelo' => in_array(($data['modelo'] ?? ''), ['Online', 'Presencial'], true) ? $data['modelo'] : null,
            'status' => in_array($incomingStatus, self::STATUS_VALUES, true) ? $incomingStatus : 'Planejado',
            'ata_path' => isset($data['ata_path']) ? (string)$data['ata_path'] : null,
            'ata_original_name' => isset($data['ata_original_name']) ? (string)$data['ata_original_name'] : null,
            'ata_mime' => isset($data['ata_mime']) ? (string)$data['ata_mime'] : null,
            'ata_size' => isset($data['ata_size']) ? (int)$data['ata_size'] : null,
            'ata_sha256' => isset($data['ata_sha256']) ? (string)$data['ata_sha256'] : null,
        ];
        if ($payload['data'] === '' || $payload['topico'] === '' || $payload['atividade'] === '') {
            throw new RuntimeException('Preencha data, pilar e atividade para salvar o evento.');
        }
        $date = new DateTimeImmutable($payload['data']);
        if ((int)$date->format('Y') !== $ano) {
            throw new RuntimeException('A data base deve pertencer ao ano do cronograma.');
        }
        if ($payload['status'] === 'Finalizado' && $payload['tipo_evento'] === 'Reunião' && ($payload['ata_path'] === null || $payload['ata_path'] === '')) {
            throw new RuntimeException('Para finalizar eventos do tipo Reunião, é obrigatório anexar a ata.');
        }
        return $payload;
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);
        return self::LEGACY_STATUS_MAP[$status] ?? $status;
    }

    private function canTransition(string $from, string $to): bool
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);
        if ($from === $to) {
            return true;
        }
        $allowed = [
            'Planejado' => ['Pendente', 'Andamento', 'Adiado', 'Finalizado'],
            'Pendente' => ['Andamento', 'Adiado', 'Finalizado', 'Planejado'],
            'Andamento' => ['Finalizado', 'Adiado', 'Pendente'],
            'Adiado' => ['Planejado', 'Andamento', 'Finalizado', 'Pendente'],
            'Finalizado' => ['Pendente', 'Planejado'],
        ];
        return in_array($to, $allowed[$from] ?? [], true);
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
        $cols = [
            'id_cronograma' => 'id_cronograma',
            'evento_pai_id' => 'evento_pai_id',
            'data' => 'data',
            'periodicidade' => 'periodicidade',
            'topico' => 'topico',
            'unidade' => 'unidade',
            'atividade' => 'atividade',
            'responsavel' => 'responsavel',
            'modelo' => 'modelo',
            'status' => 'status',
        ];
        if (Database::columnExists('cronograma_eventos', 'tipo_evento')) {
            $cols['tipo_evento'] = 'tipo_evento';
        }
        if (Database::columnExists('cronograma_eventos', 'ata_path')) {
            $cols['ata_path'] = 'ata_path';
            $cols['ata_original_name'] = 'ata_original_name';
            $cols['ata_mime'] = 'ata_mime';
            $cols['ata_size'] = 'ata_size';
            $cols['ata_sha256'] = 'ata_sha256';
        }
        $sqlCols = implode(', ', array_keys($cols));
        $sqlVals = implode(', ', array_map(static fn(string $p): string => ':' . $p, array_values($cols)));
        $stmt = $this->db->prepare('INSERT INTO cronograma_eventos (' . $sqlCols . ') VALUES (' . $sqlVals . ')');
        $stmt->execute([
            'id_cronograma' => $idCronograma,
            'evento_pai_id' => $eventoPaiId,
            'data' => $date,
            'periodicidade' => $payload['periodicidade'],
            'tipo_evento' => $payload['tipo_evento'] ?? 'Tarefa',
            'topico' => $payload['topico'],
            'unidade' => $payload['unidade'] !== '' ? $payload['unidade'] : null,
            'atividade' => $payload['atividade'],
            'responsavel' => $payload['responsavel'] !== '' ? $payload['responsavel'] : null,
            'modelo' => $payload['modelo'],
            'status' => $payload['status'],
            'ata_path' => $payload['ata_path'] ?? null,
            'ata_original_name' => $payload['ata_original_name'] ?? null,
            'ata_mime' => $payload['ata_mime'] ?? null,
            'ata_size' => $payload['ata_size'] ?? null,
            'ata_sha256' => $payload['ata_sha256'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function assertNoDuplicateOccurrences(int $idCronograma, array $payload, array $dates, array $ignoreIds = []): void
    {
        $ignoreIds = array_values(array_unique(array_filter(array_map('intval', $ignoreIds))));
        foreach ($dates as $date) {
            $collation = 'utf8mb4_uca1400_ai_ci';
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
                  AND topico COLLATE $collation = CONVERT(:topico USING utf8mb4) COLLATE $collation
                  AND COALESCE(unidade, '') COLLATE $collation = COALESCE(CONVERT(:unidade USING utf8mb4), '') COLLATE $collation
                  AND atividade COLLATE $collation = CONVERT(:atividade USING utf8mb4) COLLATE $collation
                  AND COALESCE(responsavel, '') COLLATE $collation = COALESCE(CONVERT(:responsavel USING utf8mb4), '') COLLATE $collation
                  AND COALESCE(modelo, '') COLLATE $collation = COALESCE(CONVERT(:modelo USING utf8mb4), '') COLLATE $collation";
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
        $data['tipo_evento'] = $data['tipo_evento'] ?? ($event['tipo_evento'] ?? 'Tarefa');
        $data['ata_path'] = $data['ata_path'] ?? ($event['ata_path'] ?? null);
        $data['ata_original_name'] = $data['ata_original_name'] ?? ($event['ata_original_name'] ?? null);
        $data['ata_mime'] = $data['ata_mime'] ?? ($event['ata_mime'] ?? null);
        $data['ata_size'] = $data['ata_size'] ?? ($event['ata_size'] ?? null);
        $data['ata_sha256'] = $data['ata_sha256'] ?? ($event['ata_sha256'] ?? null);
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
        $data['tipo_evento'] = $data['tipo_evento'] ?? ($root['tipo_evento'] ?? 'Tarefa');
        $data['ata_path'] = $data['ata_path'] ?? ($root['ata_path'] ?? null);
        $data['ata_original_name'] = $data['ata_original_name'] ?? ($root['ata_original_name'] ?? null);
        $data['ata_mime'] = $data['ata_mime'] ?? ($root['ata_mime'] ?? null);
        $data['ata_size'] = $data['ata_size'] ?? ($root['ata_size'] ?? null);
        $data['ata_sha256'] = $data['ata_sha256'] ?? ($root['ata_sha256'] ?? null);
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
