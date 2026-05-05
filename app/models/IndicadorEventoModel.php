<?php
namespace App\Models;

use App\Core\I18n;
use DateInterval;
use DateTimeImmutable;

class IndicadorEventoModel extends BaseModel
{
    private UnidadeMedidaModel $unidades;

    public function __construct()
    {
        parent::__construct();
        $this->unidades = new UnidadeMedidaModel();
    }

    public function byCliente(int $clienteId, ?int $ano = null): array
    {
        if ($clienteId <= 0) {
            return [];
        }
        $params = ['cid' => $clienteId];
        $where = ['ie.cliente_id = :cid', 'ie.deleted_at IS NULL'];
        if ($ano !== null && $ano > 0) {
            $params['start_year'] = sprintf('%04d-01-01', $ano);
            $params['end_year'] = sprintf('%04d-12-31', $ano);
            $where[] = 'ie.data_evento BETWEEN :start_year AND :end_year';
        }
        $where[] = $this->tenantInCondition('ie.cliente_id', $params, 'iebc');
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ie.data_evento DESC, i.indicador ASC');
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->decorate($row), $stmt->fetchAll() ?: []);
    }

    public function byIndicador(int $indicadorId): array
    {
        if ($indicadorId <= 0) {
            return [];
        }
        $params = ['iid' => $indicadorId];
        $where = ['ie.indicador_id = :iid', 'ie.deleted_at IS NULL'];
        $where[] = $this->tenantInCondition('ie.cliente_id', $params, 'iebi');
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ie.data_evento ASC');
        $stmt->execute($params);
        return array_map(fn(array $row): array => $this->decorate($row), $stmt->fetchAll() ?: []);
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $params = ['id' => $id];
        $where = ['ie.id = :id', 'ie.deleted_at IS NULL'];
        $where[] = $this->tenantInCondition('ie.cliente_id', $params, 'ief');
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE ' . implode(' AND ', $where) . '
            LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? $this->decorate($row) : null;
    }

    public function syncForIndicator(array $indicator, int $userId = 0): void
    {
        $indicatorId = (int)($indicator['id'] ?? 0);
        $clienteId = (int)($indicator['cliente_id'] ?? 0);
        if ($indicatorId <= 0 || $clienteId <= 0) {
            return;
        }

        $dates = $this->buildOccurrences(
            (string)($indicator['data_inicial'] ?? ''),
            (string)($indicator['data_final'] ?? ''),
            (string)($indicator['periodicidade_tipo'] ?? 'mensal')
        );
        $existing = $this->existingMap($indicatorId);
        $desired = [];

        foreach ($dates as $idx => $date) {
            $desired[$date] = true;
            $periodStart = $date;
            $periodEnd = isset($dates[$idx + 1])
                ? (new DateTimeImmutable($dates[$idx + 1]))->modify('-1 day')->format('Y-m-d')
                : (string)$indicator['data_final'];

            $payload = [
                'indicador_id' => $indicatorId,
                'cliente_id' => $clienteId,
                'data_evento' => $date,
                'periodo_inicio' => $periodStart,
                'periodo_fim' => $periodEnd >= $periodStart ? $periodEnd : $periodStart,
                'valor_meta' => (float)($indicator['valor'] ?? 0),
                'updated_by' => $userId > 0 ? $userId : null,
            ];

            if (isset($existing[$date])) {
                $row = $existing[$date];
                $performance = self::performance((float)$payload['valor_meta'], $row['valor_atingido'] !== null ? (float)$row['valor_atingido'] : null);
                $stmt = $this->db->prepare(
                    'UPDATE indicador_eventos
                     SET periodo_inicio = :periodo_inicio,
                         periodo_fim = :periodo_fim,
                         valor_meta = :valor_meta,
                         percentual_cumprimento = :percentual_cumprimento,
                         status_meta = :status_meta,
                         deleted_at = NULL,
                         deleted_by = NULL,
                         updated_at = NOW(),
                         updated_by = :updated_by
                     WHERE id = :id'
                );
                $stmt->execute([
                    'periodo_inicio' => $payload['periodo_inicio'],
                    'periodo_fim' => $payload['periodo_fim'],
                    'valor_meta' => $payload['valor_meta'],
                    'percentual_cumprimento' => $performance['percentual'],
                    'status_meta' => $performance['status'],
                    'updated_by' => $payload['updated_by'],
                    'id' => (int)$row['id'],
                ]);
                continue;
            }

            $performance = self::performance((float)$payload['valor_meta'], null);
            $stmt = $this->db->prepare(
                'INSERT INTO indicador_eventos (
                    indicador_id, cliente_id, data_evento, periodo_inicio, periodo_fim,
                    valor_meta, valor_atingido, percentual_cumprimento, status_meta,
                    created_at, updated_at, created_by, updated_by
                 ) VALUES (
                    :indicador_id, :cliente_id, :data_evento, :periodo_inicio, :periodo_fim,
                    :valor_meta, NULL, :percentual_cumprimento, :status_meta,
                    NOW(), NOW(), :created_by, :updated_by
                 )'
            );
            $stmt->execute([
                'indicador_id' => $indicatorId,
                'cliente_id' => $clienteId,
                'data_evento' => $payload['data_evento'],
                'periodo_inicio' => $payload['periodo_inicio'],
                'periodo_fim' => $payload['periodo_fim'],
                'valor_meta' => $payload['valor_meta'],
                'percentual_cumprimento' => $performance['percentual'],
                'status_meta' => $performance['status'],
                'created_by' => $userId > 0 ? $userId : null,
                'updated_by' => $userId > 0 ? $userId : null,
            ]);
        }

        foreach ($existing as $date => $row) {
            if (isset($desired[$date])) {
                continue;
            }
            $stmt = $this->db->prepare(
                'UPDATE indicador_eventos
                 SET deleted_at = NOW(), deleted_by = :deleted_by, updated_at = NOW(), updated_by = :updated_by
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'deleted_by' => $userId > 0 ? $userId : null,
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => (int)$row['id'],
            ]);
        }
    }

    public function softDeleteByIndicator(int $indicadorId, int $userId = 0): void
    {
        if ($indicadorId <= 0) {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE indicador_eventos
             SET deleted_at = NOW(), deleted_by = :deleted_by, updated_at = NOW(), updated_by = :updated_by
             WHERE indicador_id = :indicador_id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'deleted_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
            'indicador_id' => $indicadorId,
        ]);
    }

    public function updateAchievedValue(int $id, $value, int $userId, ?string $observacao = null): bool
    {
        $event = $this->find($id);
        if (!$event) {
            return false;
        }

        $normalized = $this->normalizeDecimal($value);
        if ($normalized === null) {
            return false;
        }

        $unit = $this->unidades->findActive((int)$event['unidade_medida_id']);
        if ($unit) {
            $type = (string)($unit['tipo'] ?? '');
            if ($type === 'inteiro' && floor((float)$normalized) !== (float)$normalized) {
                return false;
            }
            if ($type === 'percentual' && ((float)$normalized < 0 || (float)$normalized > 100)) {
                return false;
            }
        }

        $performance = self::performance((float)$event['valor_meta'], (float)$normalized);
        $params = [
            'id' => $id,
            'valor_atingido' => $normalized,
            'percentual_cumprimento' => $performance['percentual'],
            'status_meta' => $performance['status'],
            'observacao' => $observacao !== null ? trim($observacao) : trim((string)($event['observacao'] ?? '')),
            'updated_by' => $userId > 0 ? $userId : null,
        ];
        $whereScope = $this->tenantInCondition('cliente_id', $params, 'ieup');
        $stmt = $this->db->prepare(
            'UPDATE indicador_eventos
             SET valor_atingido = :valor_atingido,
                 percentual_cumprimento = :percentual_cumprimento,
                 status_meta = :status_meta,
                 observacao = :observacao,
                 lancado_em = NOW(),
                 updated_at = NOW(),
                 updated_by = :updated_by
             WHERE id = :id AND deleted_at IS NULL AND ' . $whereScope
        );
        return $stmt->execute($params) && $stmt->rowCount() > 0;
    }

    public function indicatorHistorySummary(int $indicadorId): array
    {
        $items = $this->byIndicador($indicadorId);
        $done = array_values(array_filter($items, static fn(array $item): bool => $item['valor_atingido'] !== null));
        $avg = 0.0;
        if (!empty($done)) {
            $avg = array_sum(array_map(static fn(array $item): float => (float)($item['percentual_cumprimento'] ?? 0), $done)) / count($done);
        }
        $trend = 'estavel';
        if (count($done) >= 2) {
            $first = (float)$done[0]['valor_atingido'];
            $last = (float)$done[count($done) - 1]['valor_atingido'];
            if ($last > $first) {
                $trend = 'alta';
            } elseif ($last < $first) {
                $trend = 'queda';
            }
        }
        return [
            'total' => count($items),
            'lancados' => count($done),
            'media_cumprimento' => round($avg, 2),
            'trend' => $trend,
        ];
    }

    public static function performance(float $meta, ?float $atingido): array
    {
        if ($atingido === null) {
            return [
                'percentual' => null,
                'status' => 'pendente',
                'label' => I18n::t('indicadores.meta.pending'),
                'class' => 'bg-gray-100 text-gray-700',
                'icon' => 'clock',
            ];
        }

        $percentual = $meta > 0 ? round(($atingido / $meta) * 100, 2) : null;
        if ($percentual !== null && $percentual >= 100) {
            return [
                'percentual' => $percentual,
                'status' => 'atingida',
                'label' => I18n::t('indicadores.meta.hit'),
                'class' => 'bg-green-100 text-green-700',
                'icon' => 'check-circle',
            ];
        }
        if ($percentual !== null && $percentual >= 80) {
            return [
                'percentual' => $percentual,
                'status' => 'parcial',
                'label' => I18n::t('indicadores.meta.partial'),
                'class' => 'bg-amber-100 text-amber-700',
                'icon' => 'alert-circle',
            ];
        }
        return [
            'percentual' => $percentual,
            'status' => 'nao_atingida',
            'label' => I18n::t('indicadores.meta.missed'),
            'class' => 'bg-red-100 text-red-700',
            'icon' => 'x-circle',
        ];
    }

    private function baseSelect(): string
    {
        return "SELECT
                ie.*,
                i.indicador,
                i.periodicidade_tipo,
                i.unidade_medida_id,
                um.nome AS unidade_nome,
                um.simbolo AS unidade_simbolo,
                um.tipo AS unidade_tipo,
                c.nome_empresa AS cliente_nome,
                d.nome AS departamento_nome,
                s.nome AS setor_nome
            FROM indicador_eventos ie
            JOIN indicadores i ON i.id = ie.indicador_id
            JOIN clientes c ON c.id = ie.cliente_id
            LEFT JOIN departamentos d ON d.id = i.departamento_id
            LEFT JOIN setores s ON s.id = i.setor_id
            LEFT JOIN unidades_medida um ON um.id = i.unidade_medida_id";
    }

    private function decorate(array $row): array
    {
        $performance = self::performance((float)($row['valor_meta'] ?? 0), $row['valor_atingido'] !== null ? (float)$row['valor_atingido'] : null);
        $row['meta_status_key'] = $performance['status'];
        $row['meta_status_label'] = $performance['label'];
        $row['meta_status_class'] = $performance['class'];
        $row['meta_status_icon'] = $performance['icon'];
        $row['percentual_cumprimento'] = $performance['percentual'];
        return $row;
    }

    private function existingMap(int $indicadorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, data_evento, valor_atingido
             FROM indicador_eventos
             WHERE indicador_id = :indicador_id'
        );
        $stmt->execute(['indicador_id' => $indicadorId]);
        $out = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $out[(string)$row['data_evento']] = $row;
        }
        return $out;
    }

    private function buildOccurrences(string $startDate, string $endDate, string $periodicidade): array
    {
        if ($startDate === '' || $endDate === '') {
            return [];
        }
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        if ($start > $end) {
            return [];
        }

        if ($periodicidade === 'diaria') {
            return $this->buildDayStep($start, $end, 1);
        }
        if ($periodicidade === 'semanal') {
            return $this->buildDayStep($start, $end, 7);
        }

        $monthsByType = [
            'mensal' => 1,
            'bimestral' => 2,
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
        ];
        $step = $monthsByType[$periodicidade] ?? 1;
        $dates = [];
        $anchorDay = (int)$start->format('d');
        for ($offset = 0; ; $offset += $step) {
            $candidate = $this->dateForOffset($start, $offset, $anchorDay);
            if ($candidate > $end) {
                break;
            }
            $dates[] = $candidate->format('Y-m-d');
        }
        return $dates;
    }

    private function buildDayStep(DateTimeImmutable $start, DateTimeImmutable $end, int $stepDays): array
    {
        $dates = [];
        for ($current = $start; $current <= $end; $current = $current->add(new DateInterval('P' . $stepDays . 'D'))) {
            $dates[] = $current->format('Y-m-d');
        }
        return $dates;
    }

    private function dateForOffset(DateTimeImmutable $base, int $monthsOffset, int $anchorDay): DateTimeImmutable
    {
        $target = $base->modify('first day of this month')->modify('+' . $monthsOffset . ' months');
        $lastDay = (int)$target->format('t');
        return $target->setDate(
            (int)$target->format('Y'),
            (int)$target->format('m'),
            min($anchorDay, $lastDay)
        );
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace(['R$', ' '], '', $value);
            if (substr_count($value, ',') === 1 && substr_count($value, '.') >= 1) {
                $value = str_replace('.', '', $value);
            }
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value)) {
            return null;
        }
        return round((float)$value, 4);
    }
}
