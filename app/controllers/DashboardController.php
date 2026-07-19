<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\DateHelper;
use App\Core\PdfSupport;
use App\Core\AuditLogger;
use App\Core\ReportBranding;
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

    public function resumoMes(): void
    {
        $this->requireLogin();
        $filters = $this->readDashboardFilters();
        $data = $this->resumoMesData($filters);
        $clientes = $this->clientes->all();
        AuditLogger::log('dashboard_resumo_mes', 'dashboard', null, [
            'month_start' => (string)($filters['month_start'] ?? ''),
            'month_end' => (string)($filters['month_end'] ?? ''),
            'cliente_ids_count' => is_array($filters['cliente_ids'] ?? null) ? count($filters['cliente_ids']) : 0,
        ]);
        $this->render('dashboard/resumo_mes', [
            'data' => $data,
            'filters' => $filters,
            'clientes' => $clientes,
        ]);
    }

    public function resumoMesPdf(): void
    {
        $this->requireLogin();
        $filters = $this->readDashboardFilters();
        $data = $this->resumoMesData($filters);
        if (($data['ok'] ?? false) !== true) {
            http_response_code(422);
            echo (string)($data['message'] ?? 'Filtro inválido.');
            return;
        }
        if (!PdfSupport::isDompdfAvailable()) {
            $errorId = PdfSupport::newErrorId();
            AuditLogger::log('pdf_unavailable', 'dashboard', null, [
                'error_id' => $errorId,
                'route' => 'dashboard/resumo_mes_pdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }

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
        $clienteLabel = count($names) === 1 ? $names[0] : (count($names) . ' empresa(s)');

        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Resumo do Mês',
            'header_title' => 'Resumo do Mês',
            'header_subtitle' => $clienteLabel,
            'logo_position' => 'left',
            'logo_width' => 108,
            'margins' => ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12],
            'footer_text' => 'Relatório do sistema',
            'generated_at' => DateHelper::now(),
        ]);

        ob_start();
        require __DIR__ . '/../views/dashboard/resumo_mes_pdf.php';
        $html = (string)ob_get_clean();

        if (!empty($_GET['preview'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            return;
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 120);
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $pdf = (string)$dompdf->output();

        AuditLogger::log('pdf_export', 'dashboard', null, [
            'via' => 'resumo_mes',
            'month_start' => (string)($filters['month_start'] ?? ''),
            'month_end' => (string)($filters['month_end'] ?? ''),
            'cliente_ids_count' => count($filters['cliente_ids'] ?? []),
        ]);

        $filename = 'resumo_mes_' . date('Ymd_His') . '.pdf';
        if (PHP_SAPI !== 'cli') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdf));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
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
                    COUNT(*) AS planejados,
                    SUM(CASE WHEN agenda_stats.executado = 1 THEN 1 ELSE 0 END) AS realizados,
                    COUNT(*) AS total_sessoes
                FROM (
                    SELECT
                        ta.id,
                        CASE
                            WHEN MAX(CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN 1 ELSE 0 END) = 1 THEN 1
                            WHEN COALESCE(ta.data_fim, ta.data) <= NOW() THEN 1
                            ELSE 0
                        END AS executado
                    FROM treinamentos_agenda ta
                    LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                    WHERE ta.unidade_id {$inSql}
                      AND ta.data >= :dini AND ta.data <= :dfim
                    GROUP BY ta.id, ta.data, ta.data_fim
                ) agenda_stats");
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

    /**
     * Consolida as atividades efetivamente realizadas/concluídas/lançadas/incluídas
     * no mês (ou intervalo de meses) filtrado, para o relatório "Resumo do Mês".
     * Diferente de computeMetrics() (que traz apenas contagens agregadas), aqui
     * cada categoria também retorna a lista de itens, para permitir um relatório
     * executivo itemizado.
     */
    private function resumoMesData(array $filters): array
    {
        $limit = 300;
        $empty = [
            'ok' => false,
            'message' => (string)($filters['period_error'] ?? 'Período inválido.'),
        ];
        if (empty($filters['period_ok'])) {
            return $empty;
        }
        $clienteIds = $filters['cliente_ids'];
        $range = $this->rangeFromMonths($filters['month_start'], $filters['month_end']);
        $startDate = $range['start_date'];
        $endDate = $range['end_date'];
        $startDt = $range['start_dt'];
        $endDt = $range['end_dt'];

        $payload = [
            'ok' => true,
            'filters' => [
                'month_start' => $filters['month_start'],
                'month_end' => $filters['month_end'],
                'cliente_ids' => $clienteIds,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'cronograma' => ['total' => 0, 'items' => []],
            'treinamentos' => ['total' => 0, 'items' => []],
            'biblioteca' => ['total' => 0, 'items' => []],
            'auditorias' => ['total' => 0, 'items' => []],
            'indicadores' => ['total' => 0, 'items' => []],
            'planoacao' => ['total' => 0, 'items' => []],
            'tarefas' => ['total' => 0, 'items' => []],
        ];
        if (empty($clienteIds)) {
            return $payload;
        }

        $pdo = Database::getConnection();
        $inParams = [];
        $inSql = $this->inClause($clienteIds, 'cid', $inParams);

        if (Database::tableExists('cronograma_eventos') && Database::tableExists('cronogramas')) {
            $params = array_merge($inParams, ['dini' => $startDate, 'dfim' => $endDate]);
            $stmt = $pdo->prepare("SELECT ce.id, ce.topico, ce.atividade, ce.data, ce.responsavel,
                        cr.nome AS cronograma_nome, c.nome_empresa AS cliente_nome
                    FROM cronograma_eventos ce
                    JOIN cronogramas cr ON cr.id = ce.id_cronograma
                    JOIN clientes c ON c.id = cr.id_cliente
                    WHERE cr.id_cliente {$inSql}
                      AND ce.status = 'Finalizado'
                      AND ce.data >= :dini AND ce.data <= :dfim
                    ORDER BY ce.data DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['cronograma']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM cronograma_eventos ce
                    JOIN cronogramas cr ON cr.id = ce.id_cronograma
                    WHERE cr.id_cliente {$inSql}
                      AND ce.status = 'Finalizado'
                      AND ce.data >= :dini AND ce.data <= :dfim");
            $countStmt->execute($params);
            $payload['cronograma']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('treinamentos_agenda') && Database::tableExists('treinamentos')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $sql = "SELECT ta.id, t.nome AS treinamento_nome, ta.data, ta.data_fim, c.nome_empresa AS cliente_nome
                    FROM treinamentos_agenda ta
                    JOIN treinamentos t ON t.id = ta.treinamento_id
                    JOIN clientes c ON c.id = ta.unidade_id
                    LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                    WHERE ta.unidade_id {$inSql}
                      AND ta.data >= :dini AND ta.data <= :dfim
                    GROUP BY ta.id, t.nome, ta.data, ta.data_fim, c.nome_empresa
                    HAVING MAX(CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN 1 ELSE 0 END) = 1
                        OR COALESCE(ta.data_fim, ta.data) <= NOW()
                    ORDER BY ta.data DESC
                    LIMIT {$limit}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $payload['treinamentos']['items'] = $stmt->fetchAll() ?: [];
            $countSql = "SELECT COUNT(*) FROM (
                    SELECT ta.id
                    FROM treinamentos_agenda ta
                    LEFT JOIN treinamento_participantes tp ON tp.agenda_id = ta.id
                    WHERE ta.unidade_id {$inSql}
                      AND ta.data >= :dini AND ta.data <= :dfim
                    GROUP BY ta.id, ta.data, ta.data_fim
                    HAVING MAX(CASE WHEN tp.presenca = 1 OR tp.certificado_emitido = 1 THEN 1 ELSE 0 END) = 1
                        OR COALESCE(ta.data_fim, ta.data) <= NOW()
                ) exec_sessions";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $payload['treinamentos']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('manuais')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT m.id, m.nome, m.created_at, c.nome_empresa AS cliente_nome, d.nome AS departamento_nome
                    FROM manuais m
                    JOIN clientes c ON c.id = m.empresa_id
                    JOIN departamentos d ON d.id = m.departamento_id
                    WHERE m.empresa_id {$inSql}
                      AND m.created_at >= :dini AND m.created_at <= :dfim
                    ORDER BY m.created_at DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['biblioteca']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM manuais m
                    WHERE m.empresa_id {$inSql}
                      AND m.created_at >= :dini AND m.created_at <= :dfim");
            $countStmt->execute($params);
            $payload['biblioteca']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('auditorias')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT a.id, a.nome_auditoria, a.realizada_at, a.conformidade_pct,
                        c.nome_empresa AS cliente_nome, s.nome AS setor_nome
                    FROM auditorias a
                    JOIN clientes c ON c.id = a.cliente_id
                    JOIN setores s ON s.id = a.setor_id
                    WHERE a.deleted_at IS NULL AND a.status = 'Realizada'
                      AND a.cliente_id {$inSql}
                      AND a.realizada_at >= :dini AND a.realizada_at <= :dfim
                    ORDER BY a.realizada_at DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['auditorias']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM auditorias a
                    WHERE a.deleted_at IS NULL AND a.status = 'Realizada'
                      AND a.cliente_id {$inSql}
                      AND a.realizada_at >= :dini AND a.realizada_at <= :dfim");
            $countStmt->execute($params);
            $payload['auditorias']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('indicador_eventos')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT ie.id, i.indicador AS indicador_nome, ie.lancado_em, ie.valor_atingido,
                        c.nome_empresa AS cliente_nome
                    FROM indicador_eventos ie
                    JOIN indicadores i ON i.id = ie.indicador_id
                    JOIN clientes c ON c.id = ie.cliente_id
                    WHERE ie.deleted_at IS NULL
                      AND ie.cliente_id {$inSql}
                      AND ie.valor_atingido IS NOT NULL
                      AND ie.lancado_em >= :dini AND ie.lancado_em <= :dfim
                    ORDER BY ie.lancado_em DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['indicadores']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM indicador_eventos ie
                    WHERE ie.deleted_at IS NULL
                      AND ie.cliente_id {$inSql}
                      AND ie.valor_atingido IS NOT NULL
                      AND ie.lancado_em >= :dini AND ie.lancado_em <= :dfim");
            $countStmt->execute($params);
            $payload['indicadores']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('pdca_tasks')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $historySub = Database::tableExists('planoacao_history')
                ? "COALESCE((SELECT MAX(h.created_at) FROM planoacao_history h WHERE h.item_type = 'task' AND h.item_id = t.id), t.created_at)"
                : 't.created_at';
            $stmt = $pdo->prepare("SELECT t.id, t.titulo, {$historySub} AS concluido_em, c.nome_empresa AS cliente_nome
                    FROM pdca_tasks t
                    JOIN clientes c ON c.id = t.id_cliente
                    WHERE t.id_cliente {$inSql} AND t.status = 'Concluído'
                    HAVING concluido_em >= :dini AND concluido_em <= :dfim
                    ORDER BY concluido_em DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['planoacao']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM (
                    SELECT {$historySub} AS concluido_em
                    FROM pdca_tasks t
                    WHERE t.id_cliente {$inSql} AND t.status = 'Concluído'
                    HAVING concluido_em >= :dini AND concluido_em <= :dfim
                ) concluded_tasks");
            $countStmt->execute($params);
            $payload['planoacao']['total'] = (int)$countStmt->fetchColumn();
        }

        if (Database::tableExists('tarefas')) {
            $params = array_merge($inParams, ['dini' => $startDt, 'dfim' => $endDt]);
            $stmt = $pdo->prepare("SELECT t.id, t.titulo, t.finalizado_em, c.nome_empresa AS cliente_nome
                    FROM tarefas t
                    JOIN clientes c ON c.id = t.cliente_id
                    WHERE t.cliente_id {$inSql} AND t.status = 'Finalizado'
                      AND t.finalizado_em >= :dini AND t.finalizado_em <= :dfim
                    ORDER BY t.finalizado_em DESC
                    LIMIT {$limit}");
            $stmt->execute($params);
            $payload['tarefas']['items'] = $stmt->fetchAll() ?: [];
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tarefas t
                    WHERE t.cliente_id {$inSql} AND t.status = 'Finalizado'
                      AND t.finalizado_em >= :dini AND t.finalizado_em <= :dfim");
            $countStmt->execute($params);
            $payload['tarefas']['total'] = (int)$countStmt->fetchColumn();
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
