<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\DateHelper;
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
        return in_array($type, ['all', 'planoacao', 'auditoria', 'treinamento'], true) ? $type : 'all';
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

    public function eventsForRange(string $startDate, string $endDate, string $type = 'all'): array
    {
        $type = self::normalizeTypeFilter($type);
        $events = [];
        if ($type === 'all' || $type === 'planoacao') {
            $events = array_merge($events, $this->fetchPlanoTaskEvents($startDate, $endDate));
            $events = array_merge($events, $this->fetchPlanoActionEvents($startDate, $endDate));
        }
        if ($type === 'all' || $type === 'auditoria') {
            $events = array_merge($events, $this->fetchAuditoriaEvents($startDate, $endDate));
        }
        if ($type === 'all' || $type === 'treinamento') {
            $events = array_merge($events, $this->fetchTreinamentoEvents($startDate, $endDate));
        }

        usort($events, static function (array $a, array $b): int {
            $ak = ($a['date'] ?? '') . '|' . ($a['time_sort'] ?? '23:59') . '|' . ($a['type'] ?? '') . '|' . ($a['title'] ?? '');
            $bk = ($b['date'] ?? '') . '|' . ($b['time_sort'] ?? '23:59') . '|' . ($b['type'] ?? '') . '|' . ($b['title'] ?? '');
            return $ak <=> $bk;
        });

        return $events;
    }

    private function fetchPlanoTaskEvents(string $startDate, string $endDate): array
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

    private function fetchPlanoActionEvents(string $startDate, string $endDate): array
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

    private function fetchAuditoriaEvents(string $startDate, string $endDate): array
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

    private function fetchTreinamentoEvents(string $startDate, string $endDate): array
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
}
