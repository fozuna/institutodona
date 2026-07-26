<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\DateHelper;
use App\Core\ValueFormatter;
use App\Database\Database;
use PDO;

final class AgendaEventService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getConnection();
    }

    public static function normalizeTypeFilter(?string $type): string
    {
        $type = strtolower(trim((string)$type));
        return in_array($type, ['all', 'planoacao', 'auditoria', 'treinamento', 'cronograma', 'indicador'], true) ? $type : 'all';
    }

    public static function buildMonthContext(int $year, int $month): array
    {
        $year = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));
        $firstDay = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $weekday = (int)$firstDay->format('w');
        $start = $firstDay->modify("-{$weekday} days");
        $end = $start->modify('+41 days');
        $prev = $firstDay->modify('-1 month');
        $next = $firstDay->modify('+1 month');

        $days = [];
        for ($i = 0; $i < 42; $i++) {
            $day = $start->modify("+{$i} days");
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'day' => (int)$day->format('j'),
                'is_current_month' => (int)$day->format('n') === $month,
                'is_today' => $day->format('Y-m-d') === date('Y-m-d'),
            ];
        }

        $monthsFull = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
        $monthsShort = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        return [
            'year' => $year,
            'month' => $month,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'title' => ($monthsFull[$month] ?? $firstDay->format('F')) . ' ' . $year,
            'prev' => [
                'year' => (int)$prev->format('Y'),
                'month' => (int)$prev->format('n'),
                'label' => $monthsShort[(int)$prev->format('n')] ?? $prev->format('m'),
            ],
            'next' => [
                'year' => (int)$next->format('Y'),
                'month' => (int)$next->format('n'),
                'label' => $monthsShort[(int)$next->format('n')] ?? $next->format('m'),
            ],
            'days' => $days,
        ];
    }

    public static function groupByDate(array $events): array
    {
        $grouped = [];
        foreach ($events as $event) {
            $date = (string)($event['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $grouped[$date][] = $event;
        }
        ksort($grouped);
        return $grouped;
    }

    public function eventsForRange(string $startDate, string $endDate, string $type = 'all', ?int $clienteId = null): array
    {
        $type = self::normalizeTypeFilter($type);
        $clienteId = $clienteId !== null && $clienteId > 0 ? $clienteId : null;
        $events = [];
        if ($type === 'all' || $type === 'planoacao') {
            $events = array_merge($events, $this->fetchPlanoTaskEvents($startDate, $endDate, $clienteId));
            $events = array_merge($events, $this->fetchPlanoActionEvents($startDate, $endDate, $clienteId));
        }
        if ($type === 'all' || $type === 'auditoria') {
            $events = array_merge($events, $this->fetchAuditoriaEvents($startDate, $endDate, $clienteId));
        }
        if ($type === 'all' || $type === 'treinamento') {
            $events = array_merge($events, $this->fetchTreinamentoEvents($startDate, $endDate, $clienteId));
        }
        if ($type === 'all' || $type === 'cronograma') {
            $events = array_merge($events, $this->fetchCronogramaEvents($startDate, $endDate, $clienteId));
        }
        if ($type === 'all' || $type === 'indicador') {
            $events = array_merge($events, $this->fetchIndicadorEvents($startDate, $endDate, $clienteId));
        }

        usort($events, static function (array $a, array $b): int {
            $ak = ($a['date'] ?? '') . '|' . ($a['time_sort'] ?? '23:59') . '|' . ($a['type'] ?? '') . '|' . ($a['title'] ?? '');
            $bk = ($b['date'] ?? '') . '|' . ($b['time_sort'] ?? '23:59') . '|' . ($b['type'] ?? '') . '|' . ($b['title'] ?? '');
            return $ak <=> $bk;
        });

        return $events;
    }

    private function fetchPlanoTaskEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('pdca_tasks')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = ['t.prazo IS NOT NULL', 't.prazo BETWEEN :start AND :end'];
        $scope = $this->tenantCondition('t.id_cliente', $params, 'agt');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('t.id_cliente', $clienteId, $params, 'agt_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    t.id,
                    t.id_cliente,
                    cli.nome_empresa AS cliente_nome,
                    t.titulo,
                    t.descricao,
                    t.meta_valor,
                    t.meta_unidade,
                    t.prazo,
                    t.responsavel,
                    t.fase,
                    t.status,
                    t.progresso,
                    t.created_at,
                    COALESCE((
                        SELECT MAX(h.created_at)
                        FROM planoacao_history h
                        WHERE h.item_type = 'task' AND h.item_id = t.id
                    ), t.created_at) AS updated_at
                FROM pdca_tasks t
                JOIN clientes cli ON cli.id = t.id_cliente
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(function (array $row): array {
            $descriptionParts = array_filter([
                trim((string)($row['descricao'] ?? '')),
                !empty($row['responsavel']) ? 'Responsavel: ' . trim((string)$row['responsavel']) : '',
                !empty($row['meta_valor']) || !empty($row['meta_unidade']) ? 'Meta: ' . trim((string)($row['meta_valor'] ?? '')) . ' ' . trim((string)($row['meta_unidade'] ?? '')) : '',
                !empty($row['cliente_nome']) ? 'Cliente: ' . trim((string)$row['cliente_nome']) : '',
            ]);

            return [
                'id' => 'plano-task-' . (int)$row['id'],
                'source_id' => (int)$row['id'],
                'type' => 'planoacao',
                'subtype' => 'task',
                'type_label' => 'Plano de Acao',
                'date' => (string)$row['prazo'],
                'time' => 'Dia todo',
                'time_sort' => '08:00',
                'title' => (string)($row['titulo'] ?? 'Plano de Acao'),
                'status' => (string)($row['status'] ?? ''),
                'description' => implode(' | ', $descriptionParts),
                'client' => (string)($row['cliente_nome'] ?? ''),
                'link' => 'index.php?route=planoacao/show&id=' . (int)$row['id'],
                'color' => '#2563eb',
                'badge_class' => 'bg-blue-100 text-blue-700 border-blue-200',
                'meta' => [
                    'fase' => (string)($row['fase'] ?? ''),
                    'progresso' => (string)($row['progresso'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'updated_at' => (string)($row['updated_at'] ?? ''),
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function fetchPlanoActionEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('pdca_actions')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = ['a.due_date IS NOT NULL', 'a.due_date BETWEEN :start AND :end'];
        $scope = $this->tenantCondition('t.id_cliente', $params, 'aga');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('t.id_cliente', $clienteId, $params, 'aga_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    a.id,
                    a.task_id,
                    a.titulo,
                    a.owner,
                    a.status,
                    a.due_date,
                    t.id_cliente,
                    t.titulo AS task_title,
                    cli.nome_empresa AS cliente_nome
                FROM pdca_actions a
                JOIN pdca_tasks t ON t.id = a.task_id
                JOIN clientes cli ON cli.id = t.id_cliente
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(function (array $row): array {
            $descriptionParts = array_filter([
                !empty($row['task_title']) ? 'Plano: ' . trim((string)$row['task_title']) : '',
                !empty($row['owner']) ? 'Responsavel: ' . trim((string)$row['owner']) : '',
                !empty($row['cliente_nome']) ? 'Cliente: ' . trim((string)$row['cliente_nome']) : '',
            ]);

            return [
                'id' => 'plano-action-' . (int)$row['id'],
                'source_id' => (int)$row['task_id'],
                'type' => 'planoacao',
                'subtype' => 'action',
                'type_label' => 'Plano de Acao',
                'date' => (string)$row['due_date'],
                'time' => 'Dia todo',
                'time_sort' => '09:00',
                'title' => '[Acao] ' . (string)($row['titulo'] ?? 'Acao'),
                'status' => (string)($row['status'] ?? ''),
                'description' => implode(' | ', $descriptionParts),
                'client' => (string)($row['cliente_nome'] ?? ''),
                'link' => 'index.php?route=planoacao/show&id=' . (int)$row['task_id'],
                'color' => '#1d4ed8',
                'badge_class' => 'bg-blue-100 text-blue-700 border-blue-200',
                'meta' => [
                    'task_id' => (string)($row['task_id'] ?? ''),
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function fetchAuditoriaEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('auditorias')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = [
            'a.deleted_at IS NULL',
            'a.data_auditoria BETWEEN :start AND :end',
        ];
        $scope = $this->tenantCondition('a.cliente_id', $params, 'agu');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('a.cliente_id', $clienteId, $params, 'agu_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    a.id,
                    a.cliente_id,
                    a.nome_auditoria,
                    a.data_auditoria,
                    a.status,
                    a.objetivo,
                    a.pergunta,
                    a.obs,
                    a.created_at,
                    a.updated_at,
                    a.realizada_at,
                    c.nome_empresa AS cliente_nome,
                    s.nome AS setor_nome
                FROM auditorias a
                JOIN clientes c ON c.id = a.cliente_id
                JOIN setores s ON s.id = a.setor_id
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(function (array $row): array {
            $descriptionParts = array_filter([
                !empty($row['setor_nome']) ? 'Setor: ' . trim((string)$row['setor_nome']) : '',
                trim((string)($row['objetivo'] ?? '')),
                trim((string)($row['obs'] ?? '')),
                !empty($row['cliente_nome']) ? 'Cliente: ' . trim((string)$row['cliente_nome']) : '',
            ]);
            $time = !empty($row['realizada_at']) ? DateHelper::formatDateTime((string)$row['realizada_at']) : 'Dia todo';

            return [
                'id' => 'auditoria-' . (int)$row['id'],
                'source_id' => (int)$row['id'],
                'type' => 'auditoria',
                'subtype' => 'auditoria',
                'type_label' => 'Auditoria',
                'date' => (string)$row['data_auditoria'],
                'time' => $time === '' ? 'Dia todo' : $time,
                'time_sort' => !empty($row['realizada_at']) ? substr((string)$row['realizada_at'], 11, 5) : '10:00',
                'title' => (string)($row['nome_auditoria'] ?? 'Auditoria'),
                'status' => (string)($row['status'] ?? ''),
                'description' => implode(' | ', $descriptionParts),
                'client' => (string)($row['cliente_nome'] ?? ''),
                'link' => 'index.php?route=auditorias/show&id=' . (int)$row['id'],
                'color' => '#d97706',
                'badge_class' => 'bg-amber-100 text-amber-700 border-amber-200',
                'meta' => [
                    'setor' => (string)($row['setor_nome'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'updated_at' => (string)($row['updated_at'] ?? ''),
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function fetchTreinamentoEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('treinamentos_agenda') || !Database::tableExists('treinamentos')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = [
            'DATE(ta.data) BETWEEN :start AND :end',
        ];
        $scope = $this->tenantCondition('ta.unidade_id', $params, 'agttr');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('ta.unidade_id', $clienteId, $params, 'agttr_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    ta.id,
                    ta.treinamento_id,
                    ta.data,
                    ta.instrutor,
                    ta.local,
                    ta.observacoes,
                    ta.created_at,
                    t.nome AS treinamento_nome,
                    t.publico,
                    t.periodicidade,
                    t.fornecedor,
                    c.nome_empresa AS unidade_nome,
                    u.nome AS responsavel_nome,
                    (
                        SELECT COUNT(*)
                        FROM treinamento_participantes tp
                        WHERE tp.agenda_id = ta.id
                    ) AS total_participantes,
                    (
                        SELECT COUNT(*)
                        FROM treinamento_participantes tp
                        WHERE tp.agenda_id = ta.id AND tp.presenca = 1
                    ) AS total_presentes
                FROM treinamentos_agenda ta
                JOIN treinamentos t ON t.id = ta.treinamento_id
                JOIN clientes c ON c.id = ta.unidade_id
                LEFT JOIN usuarios u ON u.id = ta.responsavel_id
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $dateTime = (string)($row['data'] ?? '');
            $timeSort = strlen($dateTime) >= 16 ? substr($dateTime, 11, 5) : '11:00';
            $hasExplicitTime = $timeSort !== '00:00' && $timeSort !== '';
            $descriptionParts = array_filter([
                !empty($row['publico']) ? 'Publico: ' . trim((string)$row['publico']) : '',
                !empty($row['instrutor']) ? 'Instrutor: ' . trim((string)$row['instrutor']) : '',
                empty($row['instrutor']) && !empty($row['responsavel_nome']) ? 'Responsavel: ' . trim((string)$row['responsavel_nome']) : '',
                !empty($row['local']) ? 'Local: ' . trim((string)$row['local']) : '',
                !empty($row['observacoes']) ? trim((string)$row['observacoes']) : '',
                !empty($row['unidade_nome']) ? 'Unidade: ' . trim((string)$row['unidade_nome']) : '',
            ]);

            return [
                'id' => 'treinamento-agenda-' . (int)$row['id'],
                'source_id' => (int)$row['id'],
                'type' => 'treinamento',
                'subtype' => 'agenda',
                'type_label' => 'Treinamento',
                'date' => substr($dateTime, 0, 10),
                'time' => $hasExplicitTime ? DateHelper::formatDateTime($dateTime, 'd/m/Y H:i') : 'Dia todo',
                'time_sort' => $hasExplicitTime ? $timeSort : '11:00',
                'title' => (string)($row['treinamento_nome'] ?? 'Treinamento'),
                'status' => sprintf('%d/%d presentes', (int)($row['total_presentes'] ?? 0), (int)($row['total_participantes'] ?? 0)),
                'description' => implode(' | ', $descriptionParts),
                'client' => (string)($row['unidade_nome'] ?? ''),
                'link' => 'index.php?route=treinamentos/presenca&agenda_id=' . (int)$row['id'],
                'color' => '#7c3aed',
                'badge_class' => 'bg-violet-100 text-violet-700 border-violet-200',
                'meta' => [
                    'treinamento_id' => (string)($row['treinamento_id'] ?? ''),
                    'periodicidade' => (string)($row['periodicidade'] ?? ''),
                    'fornecedor' => (string)($row['fornecedor'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? ''),
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function fetchCronogramaEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('cronograma_eventos') || !Database::tableExists('cronogramas')) {
            return [];
        }
        if (!Database::columnExists('cronograma_eventos', 'periodicidade') || !Database::columnExists('cronograma_eventos', 'evento_pai_id')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = ['ce.data BETWEEN :start AND :end'];
        $scope = $this->tenantCondition('cr.id_cliente', $params, 'agcr');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('cr.id_cliente', $clienteId, $params, 'agcr_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    ce.id,
                    ce.id_cronograma,
                    ce.evento_pai_id,
                    ce.data,
                    ce.periodicidade,
                    ce.topico,
                    ce.unidade,
                    ce.atividade,
                    ce.responsavel,
                    ce.modelo,
                    ce.status,
                    cr.nome AS cronograma_nome,
                    cr.ano,
                    c.nome_empresa AS cliente_nome
                FROM cronograma_eventos ce
                JOIN cronogramas cr ON cr.id = ce.id_cronograma
                JOIN clientes c ON c.id = cr.id_cliente
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $descriptionParts = array_filter([
                !empty($row['topico']) ? 'Pilar: ' . trim((string)$row['topico']) : '',
                !empty($row['unidade']) ? 'Departamento: ' . trim((string)$row['unidade']) : '',
                !empty($row['responsavel']) ? 'Responsavel: ' . trim((string)$row['responsavel']) : '',
                !empty($row['periodicidade']) ? 'Periodicidade: ' . trim((string)$row['periodicidade']) : '',
                !empty($row['cronograma_nome']) ? 'Cronograma: ' . trim((string)$row['cronograma_nome']) : '',
            ]);

            return [
                'id' => 'cronograma-evento-' . (int)$row['id'],
                'source_id' => (int)$row['id'],
                'type' => 'cronograma',
                'subtype' => 'evento',
                'type_label' => 'Evento de Cronograma',
                'date' => (string)$row['data'],
                'time' => 'Dia todo',
                'time_sort' => '07:30',
                'title' => (string)($row['atividade'] ?? 'Evento de Cronograma'),
                'status' => (string)($row['status'] ?? 'Planejado'),
                'description' => implode(' | ', $descriptionParts),
                'client' => (string)($row['cliente_nome'] ?? ''),
                'link' => 'index.php?route=cronograma/show&id=' . (int)$row['id_cronograma'],
                'color' => '#0f766e',
                'badge_class' => 'bg-teal-100 text-teal-700 border-teal-200',
                'meta' => [
                    'cronograma_id' => (string)($row['id_cronograma'] ?? ''),
                    'serie_id' => (string)($row['evento_pai_id'] ?: $row['id']),
                    'ano' => (string)($row['ano'] ?? ''),
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function fetchIndicadorEvents(string $startDate, string $endDate, ?int $clienteId = null): array
    {
        if (!Database::tableExists('indicador_eventos') || !Database::tableExists('indicadores')) {
            return [];
        }

        $params = ['start' => $startDate, 'end' => $endDate];
        $where = ['ie.data_evento BETWEEN :start AND :end', 'ie.deleted_at IS NULL', 'i.deleted_at IS NULL'];
        $scope = $this->tenantCondition('ie.cliente_id', $params, 'agind');
        if ($scope !== null) {
            $where[] = $scope;
        }
        $clienteFilter = $this->clienteFilterCondition('ie.cliente_id', $clienteId, $params, 'agind_cli');
        if ($clienteFilter !== null) {
            $where[] = $clienteFilter;
        }

        $sql = "SELECT
                    ie.id,
                    ie.indicador_id,
                    ie.data_evento,
                    ie.periodo_inicio,
                    ie.periodo_fim,
                    ie.valor_meta,
                    ie.valor_atingido,
                    ie.percentual_cumprimento,
                    ie.status_meta,
                    i.indicador,
                    i.periodicidade_tipo,
                    um.simbolo AS unidade_simbolo,
                    um.tipo AS unidade_tipo,
                    c.nome_empresa AS cliente_nome
                FROM indicador_eventos ie
                JOIN indicadores i ON i.id = ie.indicador_id
                JOIN clientes c ON c.id = ie.cliente_id
                LEFT JOIN unidades_medida um ON um.id = i.unidade_medida_id
                WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $metaFmt = ValueFormatter::byUnit((float)($row['valor_meta'] ?? 0), [
                'simbolo' => $row['unidade_simbolo'] ?? '',
                'tipo' => $row['unidade_tipo'] ?? '',
            ]);
            $atingidoFmt = $row['valor_atingido'] !== null ? ValueFormatter::byUnit((float)$row['valor_atingido'], [
                'simbolo' => $row['unidade_simbolo'] ?? '',
                'tipo' => $row['unidade_tipo'] ?? '',
            ]) : '—';
            $status = (string)($row['status_meta'] ?? 'pendente');
            $badgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
            if ($status === 'atingida') {
                $badgeClass = 'bg-green-100 text-green-700 border-green-200';
            } elseif ($status === 'parcial') {
                $badgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
            } elseif ($status === 'nao_atingida') {
                $badgeClass = 'bg-red-100 text-red-700 border-red-200';
            }

            return [
                'id' => 'indicador-evento-' . (int)$row['id'],
                'source_id' => (int)$row['id'],
                'type' => 'indicador',
                'subtype' => 'meta',
                'type_label' => 'Indicador',
                'date' => (string)$row['data_evento'],
                'time' => 'Dia todo',
                'time_sort' => '10:30',
                'title' => (string)($row['indicador'] ?? 'Indicador'),
                'status' => ucfirst(str_replace('_', ' ', $status)),
                'description' => 'Meta: ' . $metaFmt . ' | Atingido: ' . $atingidoFmt . ' | Período: ' . (string)$row['periodo_inicio'] . ' até ' . (string)$row['periodo_fim'],
                'client' => (string)($row['cliente_nome'] ?? ''),
                'link' => 'index.php?route=indicadores/evento&id=' . (int)$row['id'],
                'color' => '#be123c',
                'badge_class' => $badgeClass,
                'meta' => [
                    'indicador_id' => (string)($row['indicador_id'] ?? ''),
                    'periodicidade' => (string)($row['periodicidade_tipo'] ?? ''),
                    'cumprimento' => $row['percentual_cumprimento'] !== null ? ValueFormatter::percent((float)$row['percentual_cumprimento']) : '',
                ],
            ];
        }, $stmt->fetchAll() ?: []);
    }

    private function tenantCondition(string $column, array &$params, string $prefix): ?string
    {
        if (!Auth::isLoggedIn() || Auth::isInstituto()) {
            return null;
        }
        $ids = Auth::allowedClientIds();
        if (empty($ids)) {
            return '1=0';
        }
        $holders = [];
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $holders[] = ':' . $key;
            $params[$key] = (int)$id;
        }
        return $column . ' IN (' . implode(',', $holders) . ')';
    }

    private function clienteFilterCondition(string $column, ?int $clienteId, array &$params, string $key): ?string
    {
        if ($clienteId === null || $clienteId <= 0) {
            return null;
        }
        $params[$key] = $clienteId;
        return $column . ' = :' . $key;
    }
}
