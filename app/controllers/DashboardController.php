<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\PdfSupport;
use App\Core\AuditLogger;
use App\Models\ClienteModel;
use App\Models\AplicacaoModel;
use App\Database\Database;
use App\Services\DashboardPdfService;
use DateTimeImmutable;

class DashboardController extends BaseController
{
    private ClienteModel $clientes;
    private AplicacaoModel $aplicacoes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
        $this->aplicacoes = new AplicacaoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $user = $_SESSION['user'];
        $filters = $this->readDashboardFilters();
        $selectedCliente = (is_array($filters['cliente_ids'] ?? null) && count($filters['cliente_ids']) === 1) ? (int)$filters['cliente_ids'][0] : null;

        $clientes = $this->clientes->all();
        $kanbanData = [
            'Planejado' => [],
            'Em Andamento' => [],
            'Concluído' => [],
        ];

        $stats = $this->aplicacoes->statsByPilar($selectedCliente);
        $totalsByStatus = ['Planejado' => 0, 'Em Andamento' => 0, 'Concluído' => 0];
        foreach ($stats as $s) {
            $st = $s['status'];
            $totalsByStatus[$st] = ($totalsByStatus[$st] ?? 0) + (int)$s['total'];
        }

        if ($selectedCliente) {
            \App\Core\AuditLogger::log('dashboard_view_cliente', 'dashboard', null, ['cliente_id' => (int)$selectedCliente]);
            foreach ($this->aplicacoes->byCliente($selectedCliente) as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        } else {
            \App\Core\AuditLogger::log('dashboard_view_global', 'dashboard', null, []);
            foreach ($this->aplicacoes->all() as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        }

        $this->render('dashboard/kanban', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
            'kanbanData' => $kanbanData,
            'stats' => $stats,
            'totalsByStatus' => $totalsByStatus,
            'user' => $user,
            'filters' => $filters,
        ]);
    }

    public function metrics(): void
    {
        $this->requireLogin();
        $filters = $this->readDashboardFilters();
        $payload = $this->computeMetrics($filters);
        if (($payload['ok'] ?? false) !== true) {
            http_response_code(422);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function pdf(): void
    {
        $this->requireLogin();
        $filters = $this->readDashboardFilters();
        $payload = $this->computeMetrics($filters);
        if (($payload['ok'] ?? false) !== true) {
            http_response_code(422);
            echo (string)($payload['message'] ?? 'Filtro inválido.');
            return;
        }
        if (!PdfSupport::isDompdfAvailable()) {
            $errorId = PdfSupport::newErrorId();
            AuditLogger::log('pdf_unavailable', 'dashboard', null, [
                'error_id' => $errorId,
                'service' => 'DashboardPdfService',
                'reason' => 'dompdf_missing',
                'diagnostics' => PdfSupport::dompdfDiagnostics(),
            ]);
            @error_log('[pdf_unavailable] id=' . $errorId . ' route=dashboard/pdf');
            http_response_code(503);
            echo PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        AuditLogger::log('dashboard_pdf_export', 'dashboard', null, [
            'cliente_ids_count' => is_array($filters['cliente_ids'] ?? null) ? count($filters['cliente_ids']) : 0,
            'month_start' => (string)($filters['month_start'] ?? ''),
            'month_end' => (string)($filters['month_end'] ?? ''),
        ]);
        $all = $this->clientes->all();
        $map = [];
        foreach ($all as $c) {
            $id = (int)($c['id'] ?? 0);
            if ($id > 0) {
                $map[$id] = (string)($c['nome_empresa'] ?? '');
            }
        }
        $names = [];
        foreach (($filters['cliente_ids'] ?? []) as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $names[] = $map[$id] ?? ('Empresa #' . $id);
            }
        }
        $payload['pdf'] = [
            'cliente_label' => count($names) === 1 ? $names[0] : (count($names) . ' empresa(s)'),
            'cliente_nomes' => $names,
        ];
        $service = new DashboardPdfService();
        $ok = $service->outputToBrowser($payload, !empty($_GET['download']));
        if (!$ok) {
            http_response_code(500);
            echo 'Falha ao gerar PDF: ' . ($service->getLastError() ?: 'erro desconhecido');
        }
    }

    private function computeMetrics(array $filters): array
    {
        if (empty($filters['period_ok'])) {
            return ['ok' => false, 'message' => (string)($filters['period_error'] ?? 'Período inválido.')];
        }
        $clienteIds = $filters['cliente_ids'];
        $range = $this->rangeFromMonths($filters['month_start'], $filters['month_end']);
        $startDate = $range['start_date'];
        $endDate = $range['end_date'];
        $startDt = $range['start_dt'];
        $endDt = $range['end_dt'];
        $today = new DateTimeImmutable('today');
        $endEffective = $endDate;
        try {
            $endObj = new DateTimeImmutable($endDate);
            if ($endObj > $today) {
                $endEffective = $today->format('Y-m-d');
            }
        } catch (\Throwable $e) {
        }

        $pdo = Database::getConnection();
        $payload = [
            'ok' => true,
            'filters' => [
                'month_start' => $filters['month_start'],
                'month_end' => $filters['month_end'],
                'cliente_ids' => $clienteIds,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'end_effective' => $endEffective,
            ],
            'cronograma' => [
                'total' => 0,
                'finalizados' => 0,
                'pct' => 0.0,
                'by_status' => [],
            ],
            'biblioteca' => [
                'total_itens' => 0,
            ],
            'planoacao' => [
                'by_status' => [],
                'total' => 0,
            ],
            'auditorias' => [
                'total_realizadas' => 0,
                'media_conformidade_pct' => null,
            ],
            'indicadores' => [
                'total_eventos' => 0,
                'media_atingimento_pct' => null,
            ],
            'treinamentos' => [
                'planejados' => 0,
                'realizados' => 0,
                'total_sessoes' => 0,
                'total_inscritos' => 0,
                'total_presentes' => 0,
                'participacao_pct' => 0.0,
                'por_treinamento' => [],
            ],
        ];

        if (empty($clienteIds)) {
            return $payload;
        }

        $inParams = [];
        $inSql = $this->inClause($clienteIds, 'cid', $inParams);

        if (Database::tableExists('cronograma_eventos') && Database::tableExists('cronogramas')) {
            $params = array_merge($inParams, ['dini' => $startDate, 'dfim' => $endEffective]);
            $stmt = $pdo->prepare("SELECT ce.status, COUNT(*) AS total
                FROM cronograma_eventos ce
                JOIN cronogramas cr ON cr.id = ce.id_cronograma
                WHERE cr.id_cliente {$inSql}
                  AND ce.data >= :dini AND ce.data <= :dfim
                GROUP BY ce.status");
            $stmt->execute($params);
            $by = [];
            $total = 0;
            $done = 0;
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $st = (string)($row['status'] ?? '');
                $ct = (int)($row['total'] ?? 0);
                if ($st === '') {
                    continue;
                }
                $by[$st] = $ct;
                $total += $ct;
                if ($st === 'Finalizado') {
                    $done += $ct;
                }
            }
            $payload['cronograma']['by_status'] = $by;
            $payload['cronograma']['total'] = $total;
            $payload['cronograma']['finalizados'] = $done;
            $payload['cronograma']['pct'] = $total > 0 ? round(($done / $total) * 100, 2) : 0.0;
        }

        if (Database::tableExists('manuais')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM manuais m
                WHERE m.empresa_id {$inSql}
                  AND m.created_at >= :dini AND m.created_at <= :dfim");
            $stmt->execute($params);
            $payload['biblioteca']['total_itens'] = (int)$stmt->fetchColumn();
        }

        if (Database::tableExists('pdca_tasks')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT t.status, COUNT(*) AS total
                FROM pdca_tasks t
                WHERE t.id_cliente {$inSql}
                  AND t.created_at >= :dini AND t.created_at <= :dfim
                GROUP BY t.status");
            $stmt->execute($params);
            $rows = [];
            $sum = 0;
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $rows[] = ['status' => (string)($row['status'] ?? ''), 'total' => (int)($row['total'] ?? 0)];
                $sum += (int)($row['total'] ?? 0);
            }
            $payload['planoacao']['by_status'] = $rows;
            $payload['planoacao']['total'] = $sum;
        }

        if (Database::tableExists('auditorias')) {
            $params = array_merge($inParams, ['dini' => $startDate, 'dfim' => $endDate]);
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total, AVG(a.conformidade_pct) AS media
                FROM auditorias a
                WHERE a.deleted_at IS NULL
                  AND a.status = 'Realizada'
                  AND a.conformidade_pct IS NOT NULL
                  AND a.cliente_id {$inSql}
                  AND a.data_auditoria >= :dini AND a.data_auditoria <= :dfim");
            $stmt->execute($params);
            $row = $stmt->fetch() ?: null;
            $payload['auditorias']['total_realizadas'] = (int)($row['total'] ?? 0);
            $payload['auditorias']['media_conformidade_pct'] = $row && $row['media'] !== null ? round((float)$row['media'], 2) : null;
        }

        if (Database::tableExists('indicador_eventos')) {
            $params = array_merge($inParams, ['dini' => $startDate, 'dfim' => $endDate]);
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total, AVG(ie.percentual_cumprimento) AS media
                FROM indicador_eventos ie
                WHERE ie.deleted_at IS NULL
                  AND ie.cliente_id {$inSql}
                  AND ie.periodo_inicio <= :dfim
                  AND ie.periodo_fim >= :dini
                  AND ie.percentual_cumprimento IS NOT NULL");
            $stmt->execute($params);
            $row = $stmt->fetch() ?: null;
            $payload['indicadores']['total_eventos'] = (int)($row['total'] ?? 0);
            $payload['indicadores']['media_atingimento_pct'] = $row && $row['media'] !== null ? round((float)$row['media'], 2) : null;
        }

        if (Database::tableExists('treinamentos_agenda') && Database::tableExists('treinamentos') && Database::tableExists('treinamento_participantes')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT
                    SUM(CASE WHEN ta.data > NOW() THEN 1 ELSE 0 END) AS planejados,
                    SUM(CASE WHEN ta.data <= NOW() THEN 1 ELSE 0 END) AS realizados,
                    COUNT(*) AS total_sessoes
                FROM treinamentos_agenda ta
                WHERE ta.unidade_id {$inSql}
                  AND ta.data >= :dini AND ta.data <= :dfim");
            $stmt->execute($params);
            $row = $stmt->fetch() ?: null;
            $payload['treinamentos']['planejados'] = (int)($row['planejados'] ?? 0);
            $payload['treinamentos']['realizados'] = (int)($row['realizados'] ?? 0);
            $payload['treinamentos']['total_sessoes'] = (int)($row['total_sessoes'] ?? 0);

            $stmt = $pdo->prepare("SELECT
                    t.id AS treinamento_id,
                    t.nome AS treinamento_nome,
                    COUNT(tp.id) AS total_inscritos,
                    SUM(CASE WHEN tp.presenca = 1 THEN 1 ELSE 0 END) AS total_presentes
                FROM treinamentos_agenda ta
                JOIN treinamentos t ON t.id = ta.treinamento_id
                LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                WHERE ta.unidade_id {$inSql}
                  AND ta.data >= :dini AND ta.data <= :dfim
                GROUP BY t.id, t.nome
                ORDER BY total_inscritos DESC, t.nome
                LIMIT 10");
            $stmt->execute($params);
            $rows = [];
            $ins = 0;
            $pres = 0;
            foreach ($stmt->fetchAll() ?: [] as $r) {
                $ti = (int)($r['total_inscritos'] ?? 0);
                $tp = (int)($r['total_presentes'] ?? 0);
                $rows[] = [
                    'treinamento_id' => (int)($r['treinamento_id'] ?? 0),
                    'treinamento_nome' => (string)($r['treinamento_nome'] ?? ''),
                    'total_inscritos' => $ti,
                    'total_presentes' => $tp,
                    'participacao_pct' => $ti > 0 ? round(($tp / $ti) * 100, 2) : 0.0,
                ];
                $ins += $ti;
                $pres += $tp;
            }
            $payload['treinamentos']['por_treinamento'] = $rows;
            $payload['treinamentos']['total_inscritos'] = $ins;
            $payload['treinamentos']['total_presentes'] = $pres;
            $payload['treinamentos']['participacao_pct'] = $ins > 0 ? round(($pres / $ins) * 100, 2) : 0.0;
        }

        return $payload;
    }

    private function readDashboardFilters(): array
    {
        $key = '__dashboard_filters';
        $stored = $_SESSION[$key] ?? [];

        $monthStart = trim((string)($_GET['month_start'] ?? ($stored['month_start'] ?? '')));
        $monthEnd = trim((string)($_GET['month_end'] ?? ($stored['month_end'] ?? '')));
        if ($monthStart === '' || $monthEnd === '') {
            $now = new DateTimeImmutable('first day of this month');
            $monthStart = $now->format('Y-m');
            $monthEnd = $now->format('Y-m');
        }

        $clienteIds = [];
        if (isset($_GET['clientes']) && is_array($_GET['clientes'])) {
            $clienteIds = array_values(array_filter(array_map('intval', $_GET['clientes'])));
        } elseif (isset($_GET['cliente'])) {
            $cid = (int)$_GET['cliente'];
            if ($cid > 0) {
                $clienteIds = [$cid];
            }
        } elseif (!empty($stored['cliente_ids']) && is_array($stored['cliente_ids'])) {
            $clienteIds = array_values(array_filter(array_map('intval', $stored['cliente_ids'])));
        }

        if (!Auth::isInstituto()) {
            $allowed = Auth::allowedClientIds();
            $clienteIds = array_values(array_intersect($clienteIds, $allowed));
            if (empty($clienteIds) && !empty($allowed)) {
                $clienteIds = [(int)$allowed[0]];
            }
        } else {
            $all = array_values(array_filter(array_map('intval', array_column($this->clientes->all(), 'id'))));
            if (!empty($clienteIds)) {
                $clienteIds = array_values(array_intersect($clienteIds, $all));
            } else {
                $clienteIds = $all;
            }
        }

        $period = $this->validateMonthRange($monthStart, $monthEnd);
        $_SESSION[$key] = [
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'cliente_ids' => $clienteIds,
        ];

        return [
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'cliente_ids' => $clienteIds,
            'period_ok' => $period['ok'],
            'period_error' => $period['error'],
        ];
    }

    private function validateMonthRange(string $start, string $end): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}$/', $end)) {
            return ['ok' => false, 'error' => 'Período mensal inválido.'];
        }
        $s = DateTimeImmutable::createFromFormat('Y-m', $start) ?: null;
        $e = DateTimeImmutable::createFromFormat('Y-m', $end) ?: null;
        if (!$s || !$e) {
            return ['ok' => false, 'error' => 'Período mensal inválido.'];
        }
        if ($e < $s) {
            return ['ok' => false, 'error' => 'O mês final não pode ser anterior ao mês inicial.'];
        }
        return ['ok' => true, 'error' => ''];
    }

    private function rangeFromMonths(string $monthStart, string $monthEnd): array
    {
        $s = DateTimeImmutable::createFromFormat('Y-m', $monthStart) ?: new DateTimeImmutable('first day of this month');
        $e = DateTimeImmutable::createFromFormat('Y-m', $monthEnd) ?: $s;
        $start = $s->modify('first day of this month');
        $end = $e->modify('last day of this month');
        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'start_dt' => $start->format('Y-m-d 00:00:00'),
            'end_dt' => $end->format('Y-m-d 23:59:59'),
        ];
    }

    private function inClause(array $ids, string $prefix, array &$params): string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return 'IN (NULL)';
        }
        $holders = [];
        foreach ($ids as $i => $id) {
            $k = $prefix . $i;
            $holders[] = ':' . $k;
            $params[$k] = (int)$id;
        }
        return 'IN (' . implode(',', $holders) . ')';
    }
}
