<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\AuditoriaValidator;
use App\Core\AccessControl;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\DateHelper;
use App\Core\JwtService;
use App\Core\PdfSupport;
use App\Core\ReportBranding;
use App\Core\Security;
use App\Core\SimplePdfReport;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\UsuarioModel;
use App\Models\AuditoriaArquivoModel;
use App\Core\VirusScanner;

class AuditoriasController extends BaseController
{
    private AuditoriaModel $auditorias;
    private ClienteModel $clientes;
    private DepartamentoModel $departamentos;
    private SetorModel $setores;
    private ColaboradorModel $colaboradores;
    private UsuarioModel $usuarios;
    private AuditoriaArquivoModel $arquivos;

    public function __construct()
    {
        $this->auditorias = new AuditoriaModel();
        $this->clientes = new ClienteModel();
        $this->departamentos = new DepartamentoModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
        $this->usuarios = new UsuarioModel();
        $this->arquivos = new AuditoriaArquivoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $canManage = $this->canManageAuditorias();
        $filters = $this->collectFilters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(10, min(50, (int)($_GET['per'] ?? 15)));
        $result = $this->auditorias->list($filters, $page, $per);
        $metrics = $this->auditorias->summarizeMetrics($filters);
        $total = (int)$result['total'];
        $totalPages = max(1, (int)ceil($total / $per));
        $clientes = $this->clientesCached();
        $departamentos = !empty($filters['cliente']) ? $this->departamentosCached((int)$filters['cliente']) : $this->departamentosCached(0);
        $setores = !empty($filters['cliente']) ? $this->setoresCached((int)$filters['cliente']) : [];
        AuditLogger::log('auditoria_metrics_calculated', 'auditoria', null, [
            'filters' => $filters,
            'geral_count' => (int)($metrics['geral']['count'] ?? 0),
            'geral_media' => $metrics['geral']['media'],
            'filtrada_count' => (int)($metrics['filtrada']['count'] ?? 0),
            'filtrada_media' => $metrics['filtrada']['media'],
        ]);
        $this->render('auditorias/index', [
            'items' => $result['items'],
            'filters' => $filters,
            'clientes' => $clientes,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'metrics' => $metrics,
            'page' => $page,
            'per' => $per,
            'total' => $total,
            'totalPages' => $totalPages,
            'canManage' => $canManage,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $clientes = $this->clientesCached();
        AuditLogger::log('auditoria_create_dropdown_init', 'auditoria', null, [
            'total_clientes' => count($clientes),
            'scope_clientes' => Auth::allowedClientIds(),
            'is_instituto' => Auth::isInstituto(),
        ]);
        $this->render('auditorias/create', [
            'clientes' => $clientes,
            'setores' => $cliente > 0 ? $this->setoresCached($cliente) : [],
            'selectedCliente' => $cliente,
            'selectedSetor' => 0,
            'values' => [],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            $this->render('auditorias/create', [
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
                'selectedCliente' => (int)$payload['cliente_id'],
                'selectedSetor' => (int)$payload['setor_id'],
                'values' => $payload,
                'errors' => $errors,
            ]);
            return;
        }
        $id = $this->auditorias->create($payload, (int)($_SESSION['user']['id'] ?? 0));
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível cadastrar a auditoria no escopo selecionado.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_create', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        $_SESSION['flash_success'] = 'Auditoria cadastrada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $item = $this->auditorias->findWithQuestoes((int)($_GET['id'] ?? 0));
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $this->requireManagePermission();
        $clientes = $this->clientesCached();
        AuditLogger::log('auditoria_edit_dropdown_init', 'auditoria', (int)$item['id'], [
            'total_clientes' => count($clientes),
            'scope_clientes' => Auth::allowedClientIds(),
        ]);
        $this->render('auditorias/edit', [
            'item' => $item,
            'clientes' => $clientes,
            'setores' => $this->setoresCached((int)$item['cliente_id']),
            'respostas' => $this->auditorias->respostasByAuditoria((int)$item['id']),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = $this->payloadFromRequest($_POST);
        $current = $this->auditorias->findWithQuestoes($id);
        if (!$current) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $saveMode = (string)($_POST['save_mode'] ?? 'full'); // 'full' ou 'partial'
        $prevUpdatedAt = (string)($_POST['prev_updated_at'] ?? '');
        $prevLockVersionRaw = $_POST['prev_lock_version'] ?? null;
        $prevLockVersion = is_numeric($prevLockVersionRaw) ? (int)$prevLockVersionRaw : null;
        if (($current['status'] ?? '') === 'Realizada') {
            $payload['cliente_id'] = (int)$current['cliente_id'];
            $payload['setor_id'] = (int)$current['setor_id'];
        }
        if ($saveMode === 'partial') {
            $ok = $this->auditorias->updatePartial($id, $payload, (int)($_SESSION['user']['id'] ?? 0), $prevUpdatedAt !== '' ? $prevUpdatedAt : null, $prevLockVersion);
            if (!$ok) {
                $reason = (string)($this->auditorias->getLastError() ?? '');
                $_SESSION['flash_error'] = $reason === 'concurrency_conflict'
                    ? 'Este registro foi alterado por outro usuário. Recarregue antes de salvar.'
                    : 'Não foi possível salvar alterações.';
                $this->redirect('index.php?route=auditorias/index');
                return;
            }
            AuditLogger::log('auditoria_update_partial', 'auditoria', $id, []);
            $_SESSION['flash_success'] = 'Alterações salvas.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            $this->render('auditorias/edit', [
                'item' => $this->mergeItemWithPayload($current, $payload, $id),
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
                'respostas' => $this->auditorias->respostasByAuditoria($id),
                'errors' => $errors,
            ]);
            return;
        }
        if (($current['status'] ?? '') === 'Realizada') {
            $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
            $execErrors = AuditoriaValidator::validateExecucao($avaliacoes, count($current['questoes'] ?? []));
            if (!empty($execErrors)) {
                $this->render('auditorias/edit', [
                    'item' => $this->mergeItemWithPayload($current, $payload, $id),
                    'clientes' => $this->clientesCached(),
                    'setores' => $this->setoresCached((int)$current['cliente_id']),
                    'respostas' => $this->auditorias->respostasByAuditoria($id),
                    'errors' => $execErrors,
                ]);
                return;
            }
            $ok = $this->auditorias->updateRealizadaCompleta(
                $id,
                $payload,
                $avaliacoes,
                (int)($_SESSION['user']['id'] ?? 0),
                $prevUpdatedAt !== '' ? $prevUpdatedAt : null,
                $prevLockVersion
            );
        } else {
            $ok = $this->auditorias->updateAgendada(
                $id,
                $payload,
                (int)($_SESSION['user']['id'] ?? 0),
                $prevUpdatedAt !== '' ? $prevUpdatedAt : null,
                $prevLockVersion
            );
        }
        if (!$ok) {
            $reason = (string)($this->auditorias->getLastError() ?? '');
            $_SESSION['flash_error'] = $reason === 'concurrency_conflict'
                ? 'Este registro foi alterado por outro usuário. Recarregue antes de salvar.'
                : 'Não foi possível atualizar. Verifique se os dados não foram alterados por outro usuário.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_update', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        $_SESSION['flash_success'] = 'Auditoria atualizada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $this->render('auditorias/show', [
            'item' => $item,
            'respostas' => $respostas,
            'canManage' => $this->canManageAuditorias(),
        ]);
    }

    public function auditar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        // Permitir edição/auditoria independentemente do status
        $this->auditorias->iniciarExecucao($id, (int)($_SESSION['user']['id'] ?? 0));
        $item = $this->auditorias->findWithQuestoes($id) ?: $item;
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $this->render('auditorias/auditar', [
            'item' => $item,
            'respostas' => $respostas,
            'errors' => [],
        ]);
    }

    public function finalizar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $errors = AuditoriaValidator::validateExecucao($avaliacoes, count($item['questoes'] ?? []));
        if (!empty($errors)) {
            $respostas = $this->auditorias->respostasByAuditoria($id);
            foreach ($avaliacoes as $a) {
                $respostas[(int)$a['questao_id']] = [
                    'conformidade' => $a['conformidade'],
                    'observacoes' => $a['observacoes'],
                    'auto_saved_at' => null,
                    'finalized_at' => null,
                ];
            }
            $this->render('auditorias/auditar', [
                'item' => $item,
                'respostas' => $respostas,
                'errors' => $errors,
            ]);
            return;
        }
        $ok = $this->auditorias->finalizarAuditoria($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível finalizar a auditoria.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_finalize', 'auditoria', $id, []);
        $_SESSION['flash_success'] = 'Auditoria finalizada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function relatorioPdf(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo 'Auditoria não encontrada.';
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $auditor = null;
        $uid = (int)($item['updated_by'] ?? $item['created_by'] ?? 0);
        if ($uid > 0) {
            $u = $this->usuarios->find($uid);
            $auditor = $u['nome'] ?? $u['name'] ?? null;
        }
        $totConforme = 0;
        $totNaoConforme = 0;
        $totNaoAplica = 0;
        foreach (($item['questoes'] ?? []) as $questao) {
            $conformidade = (string)(($respostas[(int)($questao['id'] ?? 0)]['conformidade'] ?? ''));
            if ($conformidade === 'conforme') {
                $totConforme++;
            } elseif ($conformidade === 'nao_conforme') {
                $totNaoConforme++;
            } elseif ($conformidade === 'nao_aplica') {
                $totNaoAplica++;
            }
        }
        $split = AuditoriaModel::percentSplit($totConforme, $totNaoConforme);
        $pctConforme = number_format((float)$split['conforme'], 2, ',', '.');
        $pctNaoConforme = number_format((float)$split['nao_conforme'], 2, ',', '.');
        $pctGeral = isset($item['conformidade_pct']) && $item['conformidade_pct'] !== null
            ? number_format((float)$item['conformidade_pct'], 2, ',', '.') . '%'
            : '-';
        $resumoGeral = trim((string)($item['avaliacao'] ?? ''));
        $empresaSetor = trim(
            implode(' · ', array_values(array_filter([
                trim((string)($item['cliente_nome'] ?? '')),
                trim((string)($item['setor_nome'] ?? '')),
            ], static fn(string $value): bool => $value !== '')))
        );
        $header = [
            'Auditoria' => (string)($item['nome_auditoria'] ?? ''),
            'Empresa' => (string)($item['cliente_nome'] ?? ''),
            'Setor' => (string)($item['setor_nome'] ?? ''),
            'Status' => (string)($item['status'] ?? ''),
            'Responsáveis' => !empty($item['responsaveis_nomes']) ? (string)$item['responsaveis_nomes'] : '-',
            'Data agendada' => DateHelper::formatDate((string)($item['data_auditoria'] ?? '')),
            'Data de finalização' => !empty($item['realizada_at']) ? DateHelper::formatDateTime((string)$item['realizada_at']) : '-',
            'Auditor responsável' => (string)($auditor ?? '-'),
            'Total de questões' => (string)count($item['questoes'] ?? []),
            'Conforme' => $totConforme . ' (' . $pctConforme . '%)',
            'Não conforme' => $totNaoConforme . ' (' . $pctNaoConforme . '%)',
            'Não se aplica' => (string)$totNaoAplica,
            'Conformidade geral' => $pctGeral,
            'Semáforo' => $this->formatSemaforoLabel((string)($item['semaforo'] ?? '')),
            'Resumo final' => $resumoGeral !== '' ? $resumoGeral : '-',
        ];
        $questions = [];
        foreach (($item['questoes'] ?? []) as $idx => $questao) {
            $resposta = $respostas[(int)$questao['id']] ?? null;
            $anexos = $this->arquivos->listByQuestao($id, (int)$questao['id']);
            $images = [];
            $anexoLabels = [];
            foreach ($anexos as $a) {
                $mime = strtolower((string)($a['mime'] ?? ''));
                $name = trim((string)($a['original_name'] ?? ''));
                $descricao = trim((string)($a['descricao'] ?? ''));
                if ($name !== '' || $descricao !== '') {
                    $anexoLabels[] = $descricao !== '' && $name !== ''
                        ? ($name . ' - ' . $descricao)
                        : ($name !== '' ? $name : $descricao);
                }
                if (preg_match('#^image/(jpeg|png|gif|webp)$#', $mime)) {
                    $imagePath = $this->resolvePdfImagePath($a);
                    if ($imagePath !== null) {
                        $images[] = [
                            'path' => $imagePath,
                            'label' => $name !== '' ? $name : ('Anexo ' . (count($images) + 1)),
                        ];
                    }
                }
            }
            $processos = is_array($questao['processos'] ?? null) ? $questao['processos'] : [];
            $questions[] = [
                'title' => (string)($questao['pergunta'] ?? ('Questão ' . ($idx + 1))),
                'rows' => [
                    'Responsável' => (string)($questao['responsavel_nome'] ?? '-') ?: '-',
                    'Referência esperada' => (string)($questao['referencia_esperada'] ?? '-') ?: '-',
                    'Processos' => !empty($processos) ? implode(', ', array_map('strval', $processos)) : 'Nenhum',
                    'Conformidade' => $this->formatConformidadeLabel((string)($resposta['conformidade'] ?? 'pendente')),
                    'Observações' => trim((string)($resposta['observacoes'] ?? '')) ?: '-',
                    'Anexos cadastrados' => !empty($anexoLabels) ? implode(' | ', $anexoLabels) : 'Nenhum',
                ],
                'images' => $images,
            ];
        }
        $semaforo = trim((string)($item['semaforo'] ?? ''));
        $semaforoLabel = $this->formatSemaforoLabel($semaforo);
        $semaforoBadge = match ($semaforo) {
            'verde' => ['fill' => [220, 252, 231], 'text' => [22, 101, 52]],
            'amarelo' => ['fill' => [254, 249, 195], 'text' => [133, 77, 14]],
            'vermelho' => ['fill' => [254, 226, 226], 'text' => [153, 27, 27]],
            default => ['fill' => [226, 232, 240], 'text' => [71, 85, 105]],
        };
        $pdf = SimplePdfReport::fromAudit([
            'header_title' => 'Relatório de Auditoria',
            'report_title' => (string)($item['nome_auditoria'] ?? 'Auditoria'),
            'header_subtitle' => $empresaSetor !== '' ? $empresaSetor : 'Documento padronizado do sistema',
            'show_logo' => false,
            'margins' => ['top' => 18, 'right' => 12, 'bottom' => 14, 'left' => 12],
            'generated_at' => DateHelper::now(),
            'footer_text' => 'Relatório do sistema',
            'header' => $header,
            'summary_sections' => [
                [
                    'columns' => 4,
                    'items' => [
                        ['label' => 'Data agendada', 'value' => DateHelper::formatDate((string)($item['data_auditoria'] ?? ''))],
                        ['label' => 'Status', 'value' => (string)($item['status'] ?? '-')],
                        ['label' => 'Realizada em', 'value' => !empty($item['realizada_at']) ? DateHelper::formatDateTime((string)$item['realizada_at']) : '-'],
                        ['label' => 'Total de questões', 'value' => (string)count($item['questoes'] ?? [])],
                    ],
                ],
                [
                    'columns' => 4,
                    'items' => [
                        ['label' => 'Conforme', 'value' => $totConforme . ' (' . $pctConforme . '%)'],
                        ['label' => 'Não conforme', 'value' => $totNaoConforme . ' (' . $pctNaoConforme . '%)'],
                        ['label' => 'Não se aplica', 'value' => (string)$totNaoAplica],
                        [
                            'label' => 'Semáforo',
                            'value' => $semaforoLabel,
                            'badge' => strtoupper($semaforoLabel !== '-' ? $semaforoLabel : 'SEM DADO'),
                            'badge_fill' => $semaforoBadge['fill'],
                            'badge_text' => $semaforoBadge['text'],
                        ],
                    ],
                ],
            ],
            'detail_rows' => [
                ['label' => 'Responsáveis', 'value' => !empty($item['responsaveis_nomes']) ? (string)$item['responsaveis_nomes'] : '-'],
                ['label' => 'Auditor responsável', 'value' => (string)($auditor ?? '-')],
                ['label' => 'Conformidade geral', 'value' => $pctGeral],
                ['label' => 'Resumo final', 'value' => $resumoGeral !== '' ? $resumoGeral : '-'],
            ],
            'questions' => $questions,
        ]);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="auditoria-' . $id . '.pdf"');
        echo $pdf;
    }

    public function relatorioExecutivo(): void
    {
        $this->requireLogin();
        $filters = $this->collectFilters();
        $data = $this->auditorias->executiveReportData($filters);
        $resumoExecutivo = $this->buildResumoExecutivo($data, $filters);
        $clientes = $this->clientesCached();
        $departamentos = !empty($filters['cliente']) ? $this->departamentosCached((int)$filters['cliente']) : $this->departamentosCached(0);
        $setores = !empty($filters['cliente']) ? $this->setoresCached((int)$filters['cliente']) : [];
        AuditLogger::log('auditoria_relatorio_executivo', 'auditoria', null, [
            'filters' => $filters,
            'total_auditorias' => $data['total_auditorias'],
        ]);
        $this->render('auditorias/relatorio_executivo', [
            'data' => $data,
            'resumoExecutivo' => $resumoExecutivo,
            'filters' => $filters,
            'clientes' => $clientes,
            'departamentos' => $departamentos,
            'setores' => $setores,
        ]);
    }

    public function relatorioExecutivoPdf(): void
    {
        $this->requireLogin();
        if (!PdfSupport::isDompdfAvailable()) {
            $errorId = PdfSupport::newErrorId();
            AuditLogger::log('pdf_unavailable', 'auditoria', null, [
                'error_id' => $errorId,
                'route' => 'auditorias/relatorioExecutivoPdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }

        $filters = $this->collectFilters();
        $data = $this->auditorias->executiveReportData($filters);
        $resumoExecutivo = $this->buildResumoExecutivo($data, $filters);

        $subtitleParts = array_values(array_filter([
            $filters['inicio'] ? ('De ' . DateHelper::formatDate((string)$filters['inicio'])) : null,
            $filters['fim'] ? ('até ' . DateHelper::formatDate((string)$filters['fim'])) : null,
        ]));
        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Relatório Executivo de Fechamento de Auditorias',
            'header_title' => 'Relatório Executivo de Fechamento de Auditorias',
            'header_subtitle' => !empty($subtitleParts) ? implode(' ', $subtitleParts) : 'Consolidado do período avaliado',
            'logo_position' => 'left',
            'logo_width' => 108,
            'margins' => ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12],
            'footer_text' => 'Relatório do sistema',
            'generated_at' => DateHelper::now(),
        ]);

        ob_start();
        require __DIR__ . '/../views/auditorias/relatorio_executivo_pdf.php';
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

        AuditLogger::log('pdf_export', 'auditoria', null, [
            'via' => 'relatorio_executivo',
            'filters' => $filters,
            'total_auditorias' => $data['total_auditorias'],
        ]);

        $filename = 'relatorio_executivo_auditorias_' . date('Ymd_His') . '.pdf';
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

    private function buildResumoExecutivo(array $data, array $filters): string
    {
        $totalAuditorias = (int)($data['total_auditorias'] ?? 0);
        if ($totalAuditorias === 0) {
            return 'Não foram encontradas auditorias realizadas para o período e os filtros selecionados.';
        }
        $inicio = !empty($filters['inicio']) ? DateHelper::formatDate((string)$filters['inicio']) : null;
        $fim = !empty($filters['fim']) ? DateHelper::formatDate((string)$filters['fim']) : null;
        if ($inicio && $fim) {
            $periodoTexto = 'no período de ' . $inicio . ' a ' . $fim;
        } elseif ($inicio) {
            $periodoTexto = 'a partir de ' . $inicio;
        } elseif ($fim) {
            $periodoTexto = 'até ' . $fim;
        } else {
            $periodoTexto = 'no período avaliado';
        }

        $totalItens = (int)($data['total_itens'] ?? 0);
        $totalConforme = (int)($data['total_conforme'] ?? 0);
        $totalNaoConforme = (int)($data['total_nao_conforme'] ?? 0);
        $pctMedia = $data['conformidade_pct_media'] ?? null;
        $pctTexto = $pctMedia !== null ? number_format((float)$pctMedia, 2, ',', '.') . '%' : 'não apurada';
        $totalAreas = count($data['por_area'] ?? []);

        $texto = 'Segue o fechamento das auditorias realizadas ' . $periodoTexto . '. '
            . 'Foram realizadas ' . $totalAuditorias . ' auditoria(s), abrangendo ' . $totalAreas . ' área(s), '
            . 'totalizando ' . $totalItens . ' item(ns) avaliado(s), dos quais ' . $totalConforme . ' conforme(s) e '
            . $totalNaoConforme . ' não conforme(s), com conformidade média de ' . $pctTexto . '. ';

        if ($totalNaoConforme > 0) {
            $texto .= 'Abaixo, um resumo consolidado por área com as respectivas observações registradas pelos '
                . 'responsáveis, para orientar a geração dos planos de ação.';
        } else {
            $texto .= 'Não foram identificadas não conformidades no período avaliado.';
        }

        return $texto;
    }

    private function resolvePdfImagePath(array $anexo): ?string
    {
        foreach (['compressed_path', 'path', 'thumb_path'] as $field) {
            $path = trim((string)($anexo[$field] ?? ''));
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function formatConformidadeLabel(string $value): string
    {
        return match (trim($value)) {
            'conforme' => 'Conforme',
            'nao_conforme' => 'Não conforme',
            'nao_aplica' => 'Não se aplica',
            default => 'Pendente',
        };
    }

    private function formatSemaforoLabel(string $value): string
    {
        return match (trim($value)) {
            'verde' => 'Verde',
            'amarelo' => 'Amarelo',
            'vermelho' => 'Vermelho',
            default => '-',
        };
    }

    public function duplicate(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $sourceId = (int)($_POST['id'] ?? 0);
        $nome = trim((string)($_POST['nome_auditoria'] ?? ''));
        $data = AuditoriaValidator::normalizeDate((string)($_POST['data_auditoria'] ?? ''));
        if ($sourceId <= 0 || $nome === '') {
            $_SESSION['flash_error'] = 'Parâmetros inválidos para duplicação.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $src = $this->auditorias->findWithQuestoes($sourceId);
        if (!$src) {
            $_SESSION['flash_error'] = 'Auditoria origem não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        // Validações de nome
        $msg = $this->auditorias->validarNomeAuditoria($nome);
        if ($msg !== null) {
            $_SESSION['flash_error'] = $msg;
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        if (!$this->auditorias->isNomeDisponivel($nome)) {
            $_SESSION['flash_error'] = 'Já existe uma auditoria com este nome.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $newId = $this->auditorias->duplicateFrom($src, [
            'nome_auditoria' => $nome,
            'data_auditoria' => $data ?: date('Y-m-d'),
        ], (int)($_SESSION['user']['id'] ?? 0));
        if ($newId <= 0) {
            $detail = trim((string)$this->auditorias->getLastError());
            AuditLogger::log('auditoria_duplicate_error', 'auditoria', null, [
                'source_id' => $sourceId,
                'message' => $detail,
            ]);
            $_SESSION['flash_error'] = 'Não foi possível duplicar auditoria.' . ($detail !== '' ? (' Detalhe: ' . $detail) : '');
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_duplicate', 'auditoria', $newId, ['source_id' => $sourceId]);
        $_SESSION['flash_success'] = 'Auditoria duplicada como rascunho.';
        $this->redirect('index.php?route=auditorias/index');
    }

    // método rename removido (não utilizado)

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['flash_error'] = 'Auditoria inválida.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $vinculos = $this->auditorias->countRelatoriosVinculados($id);
        if ($vinculos > 0) {
            $_SESSION['flash_error'] = 'Não é possível excluir auditoria vinculada a relatórios.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $ok = $this->auditorias->softDelete($id, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível excluir auditoria.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_delete', 'auditoria', $id, []);
        $_SESSION['flash_success'] = 'Auditoria excluída com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function editarRealizada(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item || ($item['status'] ?? '') !== 'Realizada') {
            $_SESSION['flash_error'] = 'Apenas auditorias realizadas podem ser editadas por este fluxo.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $this->render('auditorias/editar_realizada', [
            'item' => $item,
            'respostas' => $respostas,
            'errors' => [],
        ]);
    }

    public function atualizarObservacoes(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item || ($item['status'] ?? '') !== 'Realizada') {
            $_SESSION['flash_error'] = 'Apenas auditorias realizadas podem ser editadas por este fluxo.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $payload = json_decode((string)($_POST['observacoes_json'] ?? '[]'), true) ?: [];
        $prevUpdatedAt = (string)($_POST['prev_updated_at'] ?? '');
        $prevLockVersionRaw = $_POST['prev_lock_version'] ?? null;
        $prevLockVersion = is_numeric($prevLockVersionRaw) ? (int)$prevLockVersionRaw : null;
        $map = [];
        foreach ($payload as $row) {
            $qid = (int)($row['questao_id'] ?? 0);
            $obs = trim((string)($row['observacoes'] ?? ''));
            if ($qid > 0 && mb_strlen($obs) <= 2000) {
                $map[$qid] = $obs;
            }
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $result = $this->auditorias->updateObservacoesRealizada(
            $id,
            $map,
            $userId,
            $prevUpdatedAt !== '' ? $prevUpdatedAt : null,
            $prevLockVersion
        );
        if (!$result['ok']) {
            $reason = (string)($this->auditorias->getLastError() ?? '');
            $_SESSION['flash_error'] = $reason === 'concurrency_conflict'
                ? 'Este registro foi alterado por outro usuário. Recarregue antes de salvar.'
                : 'Não foi possível atualizar as observações.';
            $this->redirect('index.php?route=auditorias/editar_realizada&id=' . $id);
            return;
        }
        $updated = (int)($result['updated'] ?? 0);
        AuditLogger::log('auditoria_edit_realizada', 'auditoria', $id, ['updated' => $updated]);
        if ($updated > 0) {
            $_SESSION['flash_success'] = 'Observações atualizadas com sucesso.';
        } else {
            $_SESSION['flash_error'] = 'Nenhuma alteração foi detectada nas observações.';
        }
        $this->redirect('index.php?route=auditorias/show&id=' . $id);
    }

    public function apiSetores(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $clienteRequested = (int)($_GET['cliente_id'] ?? 0);
        $cliente = (int)($this->resolveScopedClienteId($clienteRequested) ?? 0);
        $force = (int)($_GET['force'] ?? 0) === 1;
        if ($cliente <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $items = $this->setoresCached($cliente, $force);
            AuditLogger::log('auditoria_api_setores', 'auditoria', null, [
                'cliente_id' => $cliente,
                'cliente_id_requested' => $clienteRequested,
                'force' => $force,
                'total' => count($items),
            ]);
            echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            AuditLogger::log('auditoria_api_setores_error', 'auditoria', null, [
                'cliente_id' => $cliente,
                'cliente_id_requested' => $clienteRequested,
                'force' => $force,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar setores'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function apiClientes(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $items = $this->clientesCached(true);
        AuditLogger::log('auditoria_api_clientes', 'auditoria', null, [
            'total_clientes' => count($items),
            'scope_clientes' => Auth::allowedClientIds(),
            'is_instituto' => Auth::isInstituto(),
        ]);
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function apiResponsaveis(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $setor = (int)($_GET['setor_id'] ?? 0);
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($setor <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->responsaveisBySetorCached($setor, $cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiColaboradores(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        if ($cliente <= 0 || mb_strlen($q) < 2) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $items = $this->colaboradores->searchActiveByCliente($cliente, $q, 15);
        AuditLogger::log('auditoria_api_colaboradores', 'auditoria', null, [
            'cliente_id' => $cliente,
            'q_len' => mb_strlen($q),
            'total' => count($items),
        ]);
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function apiUploadAnexo(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $auditoriaId = (int)($_POST['auditoria_id'] ?? 0);
        $questaoId = (int)($_POST['questao_id'] ?? 0);
        if ($auditoriaId <= 0 || $questaoId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $item = $this->auditorias->find($auditoriaId);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!$this->auditorias->questaoPertence($auditoriaId, $questaoId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Questão inválida'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $totalBytes = $this->arquivos->totalBytesByQuestao($auditoriaId, $questaoId);
        $MAX_FILE = 10 * 1024 * 1024;
        $MAX_TOTAL = 50 * 1024 * 1024;
        $saved = [];
        foreach (($_FILES['files']['name'] ?? []) as $i => $name) {
            $size = (int)($_FILES['files']['size'][$i] ?? 0);
            $tmp = $_FILES['files']['tmp_name'][$i] ?? '';
            $err = (int)($_FILES['files']['error'][$i] ?? 0);
            if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                continue;
            }
            if ($size <= 0 || $size > $MAX_FILE) {
                continue;
            }
            if ($totalBytes + $size > $MAX_TOTAL) {
                break;
            }
            $mime = mime_content_type($tmp) ?: null;
            $hash = hash_file('sha256', $tmp);
            $scan = VirusScanner::scan($tmp);
            if (!$scan['safe']) {
                AuditLogger::log('auditoria_upload_blocked_virus', 'anexo', null, [
                    'auditoria_id' => $auditoriaId,
                    'questao_id' => $questaoId,
                    'name' => $name,
                    'output' => $scan['output'],
                ]);
                continue;
            }
            $baseDir = __DIR__ . '/../../storage/auditorias/' . $auditoriaId . '/' . $questaoId . '/';
            if (!is_dir($baseDir)) {
                @mkdir($baseDir, 0775, true);
            }
            $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', basename($name));
            $dest = $baseDir . uniqid('f_', true) . '_' . $safeName;
            if (!@move_uploaded_file($tmp, $dest)) {
                continue;
            }
            $compressed = null;
            $thumb = null;
            if (is_string($mime) && preg_match('#^image/(jpeg|png|webp|gif)$#i', $mime)) {
                $thumb = $baseDir . 'thumb_' . uniqid() . '.jpg';
                try {
                    if (stripos($mime, 'jpeg') !== false) $im = imagecreatefromjpeg($dest);
                    elseif (stripos($mime, 'png') !== false) $im = imagecreatefrompng($dest);
                    elseif (stripos($mime, 'gif') !== false) $im = imagecreatefromgif($dest);
                    else $im = imagecreatefromwebp($dest);
                    if ($im) {
                        $w = imagesx($im); $h = imagesy($im);
                        $nw = 300; $nh = (int)round($h * ($nw / $w));
                        $out = imagecreatetruecolor($nw, $nh);
                        imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        imagejpeg($out, $thumb, 82);
                        imagedestroy($out); imagedestroy($im);
                    } else {
                        $thumb = null;
                    }
                } catch (\Throwable $e) { $thumb = null; }
            }
            $id = $this->arquivos->create([
                'auditoria_id' => $auditoriaId,
                'questao_id' => $questaoId,
                'path' => $dest,
                'compressed_path' => $compressed,
                'original_name' => $safeName,
                'mime' => $mime,
                'size' => $size,
                'sha256' => $hash,
                'thumb_path' => $thumb,
                'created_by' => (int)($_SESSION['user']['id'] ?? null),
            ]);
            $totalBytes += $size;
            $saved[] = ['id' => $id, 'name' => $safeName, 'size' => $size, 'thumb' => $thumb !== null];
        }
        echo json_encode(['success' => true, 'saved' => $saved], JSON_UNESCAPED_UNICODE);
    }

    public function apiListAnexos(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $auditoriaId = (int)($_GET['auditoria_id'] ?? 0);
        $questaoId = (int)($_GET['questao_id'] ?? 0);
        if ($auditoriaId <= 0 || $questaoId <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE); return;
        }
        $auditoria = $this->auditorias->find($auditoriaId);
        if (!$auditoria || !$this->canAccessCliente((int)$auditoria['cliente_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $items = $this->arquivos->listByQuestao($auditoriaId, $questaoId);
        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'id' => (int)$it['id'],
                'name' => (string)($it['original_name'] ?? ''),
                'descricao' => (string)($it['descricao'] ?? ''),
                'size' => (int)($it['size'] ?? 0),
                'mime' => $it['mime'] ?? null,
                'has_thumb' => !empty($it['thumb_path']),
            ];
        }
        echo json_encode(['success' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    }

    public function apiDeleteAnexo(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $file = $this->arquivos->find($id);
        if (!$file) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Anexo não encontrado'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $auditoria = $this->auditorias->find((int)$file['auditoria_id']);
        if (!$auditoria || !$this->canAccessCliente((int)$auditoria['cliente_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->auditorias->saveHistorySnapshot((int)$file['auditoria_id'], (int)($_SESSION['user']['id'] ?? 0));
        $this->arquivos->delete($id);
        foreach (['path', 'compressed_path', 'thumb_path'] as $field) {
            $path = (string)($file[$field] ?? '');
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiUpdateAnexo(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $file = $this->arquivos->find($id);
        if (!$file) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Anexo não encontrado'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $auditoria = $this->auditorias->find((int)$file['auditoria_id']);
        if (!$auditoria || !$this->canAccessCliente((int)$auditoria['cliente_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255 || mb_strlen($descricao) > 500) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Metadados inválidos'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->auditorias->saveHistorySnapshot((int)$file['auditoria_id'], (int)($_SESSION['user']['id'] ?? 0));
        $this->arquivos->updateMetadata($id, $name, $descricao);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function downloadAnexo(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $file = $this->arquivos->find($id);
        if (!$file) { http_response_code(404); echo 'Arquivo não encontrado'; return; }
        $auditoria = $this->auditorias->find((int)$file['auditoria_id']);
        if (!$auditoria) { http_response_code(404); echo 'Auditoria não encontrada'; return; }
        if (!$this->canAccessCliente((int)$auditoria['cliente_id'])) { http_response_code(403); echo 'Sem permissão'; return; }
        $path = $file['path'];
        if (!is_file($path)) { http_response_code(404); echo 'Arquivo indisponível'; return; }
        $name = $file['original_name'] ?? ('arquivo_' . $id);
        $mime = (string)($file['mime'] ?? 'application/octet-stream');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        readfile($path);
    }

    public function viewAnexo(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $file = $this->arquivos->find($id);
        if (!$file) { http_response_code(404); echo 'Arquivo não encontrado'; return; }
        $auditoria = $this->auditorias->find((int)$file['auditoria_id']);
        if (!$auditoria) { http_response_code(404); echo 'Auditoria não encontrada'; return; }
        if (!$this->canAccessCliente((int)$auditoria['cliente_id'])) { http_response_code(403); echo 'Sem permissão'; return; }
        $path = $file['path'];
        if (!is_file($path)) { http_response_code(404); echo 'Arquivo indisponível'; return; }
        $mime = (string)($file['mime'] ?? 'application/octet-stream');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . ($file['original_name'] ?? ('arquivo_' . $id)) . '"');
        readfile($path);
    }

    public function thumbAnexo(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $file = $this->arquivos->find($id);
        if (!$file) { http_response_code(404); echo 'Arquivo não encontrado'; return; }
        $auditoria = $this->auditorias->find((int)$file['auditoria_id']);
        if (!$auditoria) { http_response_code(404); echo 'Auditoria não encontrada'; return; }
        if (!$this->canAccessCliente((int)$auditoria['cliente_id'])) { http_response_code(403); echo 'Sem permissão'; return; }
        $path = $file['thumb_path'] ?? null;
        if (!$path || !is_file($path)) { http_response_code(404); echo 'Thumbnail indisponível'; return; }
        header('Content-Type: image/jpeg');
        readfile($path);
    }

    public function apiList(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $filters = $this->collectFilters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(50, (int)($_GET['per'] ?? 10)));
        $result = $this->auditorias->list($filters, $page, $per);
        echo json_encode([
            'success' => true,
            'items' => $result['items'],
            'total' => (int)$result['total'],
            'page' => $page,
            'per' => $per,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function apiShow(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $item['avaliacoes'] = $this->auditorias->respostasByAuditoria($id);
        echo json_encode(['success' => true, 'item' => $item], JSON_UNESCAPED_UNICODE);
    }

    public function apiStore(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = $this->auditorias->create($payload, (int)($_SESSION['user']['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Escopo inválido para criação.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_create', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }

    public function apiUpdate(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = $this->payloadFromRequest($_POST);
        $prevUpdatedAt = (string)($_POST['prev_updated_at'] ?? '');
        $prevLockVersionRaw = $_POST['prev_lock_version'] ?? null;
        $prevLockVersion = is_numeric($prevLockVersionRaw) ? (int)$prevLockVersionRaw : null;
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->updateAgendada(
            $id,
            $payload,
            (int)($_SESSION['user']['id'] ?? 0),
            $prevUpdatedAt !== '' ? $prevUpdatedAt : null,
            $prevLockVersion
        );
        if (!$ok) {
            $reason = (string)($this->auditorias->getLastError() ?? '');
            $status = $reason === 'concurrency_conflict' ? 409 : 422;
            http_response_code($status);
            echo json_encode([
                'success' => false,
                'reason' => $reason,
                'message' => $reason === 'concurrency_conflict'
                    ? 'Registro desatualizado. Recarregue os dados antes de atualizar.'
                    : 'Auditoria não pode ser atualizada.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_update', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiDelete(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($this->auditorias->countRelatoriosVinculados($id) > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Auditoria vinculada a relatórios.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->softDelete($id, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_delete', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiAutosave(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $ok = $this->auditorias->autosaveAvaliacoes($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Não foi possível salvar rascunho.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_autosave', 'auditoria', $id, ['total' => count($avaliacoes)]);
        echo json_encode(['success' => true, 'saved_at' => date('c')], JSON_UNESCAPED_UNICODE);
    }

    public function apiFinalize(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $errors = AuditoriaValidator::validateExecucao($avaliacoes, count($item['questoes'] ?? []));
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->finalizarAuditoria($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Não foi possível finalizar.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_finalize', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiAuditar(): void
    {
        $this->apiFinalize();
    }

    private function collectFilters(): array
    {
        $inicio = AuditoriaValidator::normalizeDate((string)($_GET['inicio'] ?? ''));
        $fim = AuditoriaValidator::normalizeDate((string)($_GET['fim'] ?? ''));
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente'] ?? 0)) ?? 0);
        return [
            'cliente' => $cliente > 0 ? $cliente : null,
            'departamento' => (int)($_GET['departamento'] ?? 0) ?: null,
            'setor' => (int)($_GET['setor'] ?? 0) ?: null,
            'status' => ($_GET['status'] ?? '') ?: null,
            'farol' => ($_GET['farol'] ?? '') ?: null,
            'inicio' => $inicio,
            'fim' => $fim,
            'sort_col' => ($_GET['sort_col'] ?? 'data'),
            'sort_dir' => ($_GET['sort_dir'] ?? 'desc'),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
    }

    private function payloadFromRequest(array $src): array
    {
        return [
            'cliente_id' => (int)($src['cliente_id'] ?? 0),
            'setor_id' => (int)($src['setor_id'] ?? 0),
            'nome_auditoria' => trim((string)($src['nome_auditoria'] ?? '')),
            'data_auditoria' => AuditoriaValidator::normalizeDate((string)($src['data_auditoria'] ?? '')),
            'questoes' => AuditoriaValidator::normalizeQuestoes($src['questoes_json'] ?? ($src['questoes'] ?? [])),
        ];
    }

    private function appendResponsavelValidationErrors(array $payload, array &$errors): void
    {
        $clienteId = (int)($payload['cliente_id'] ?? 0);
        $questaoIds = [];

        foreach (($payload['questoes'] ?? []) as $index => $questao) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $questao['responsavel_ids'] ?? []))));
            $labels = array_values(array_unique(array_filter(array_map(static function ($value): string {
                return trim((string)$value);
            }, $questao['responsavel_labels'] ?? []))));
            if (empty($labels)) {
                $labels = array_values(array_unique(array_filter(array_map('trim', preg_split('/\s*,\s*/', (string)($questao['responsavel_nome'] ?? '')) ?: []))));
            }
            if (empty($ids) && empty($labels)) {
                $errors['questao_' . ($index + 1) . '_responsavel_nome'] = 'Questão ' . ($index + 1) . ': informe pelo menos 1 responsável.';
            }
            $questaoIds = array_merge($questaoIds, $ids);
        }

        $todosIds = array_values(array_unique($questaoIds));
        if ($clienteId <= 0) {
            return;
        }

        $validos = $this->colaboradores->findByIdsCliente($todosIds, $clienteId);
        $validosMap = [];
        foreach ($validos as $item) {
            $validosMap[(int)$item['id']] = (string)($item['nome'] ?? '');
        }

        $invalidos = array_values(array_diff($todosIds, array_keys($validosMap)));
        if (!empty($invalidos)) {
            $errors['questoes'] = 'Um ou mais responsáveis selecionados nas questões não pertencem à empresa informada.';
        }
    }

    private function mergeItemWithPayload(array $current, array $payload, int $id): array
    {
        $item = $current;
        $item['id'] = $id;
        $item['cliente_id'] = (int)($payload['cliente_id'] ?? ($current['cliente_id'] ?? 0));
        $item['setor_id'] = (int)($payload['setor_id'] ?? ($current['setor_id'] ?? 0));
        $item['nome_auditoria'] = (string)($payload['nome_auditoria'] ?? ($current['nome_auditoria'] ?? ''));
        $item['data_auditoria'] = (string)($payload['data_auditoria'] ?? ($current['data_auditoria'] ?? ''));
        $item['questoes'] = is_array($payload['questoes'] ?? null) ? $payload['questoes'] : ($current['questoes'] ?? []);
        return $item;
    }

    private function isPost(): bool
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }

    private function canManageAuditorias(): bool
    {
        return AccessControl::canAccessRoute((string)($_GET['route'] ?? 'auditorias/store'), 'POST');
    }

    private function requireManagePermission(bool $json = false): void
    {
        if (!$this->canManageAuditorias()) {
            $message = 'Acesso negado: apenas consultores e usuários Instituto podem criar, editar ou excluir auditorias.';
            if ($json) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
                exit;
            }
            http_response_code(403);
            echo $message;
            exit;
        }
    }

    private function requireApiAuth(bool $manage): void
    {
        if ($this->authenticateByBearer()) {
            $this->authorizeRoute((string)($_GET['route'] ?? ''), true);
            if ($manage && !$this->canManageAuditorias()) {
                $this->denyAccess('Seu perfil não permite executar esta ação.', (string)($_GET['route'] ?? ''), null, true);
            }
            return;
        }
        $this->requireLogin();
        if ($manage) {
            $this->requireManagePermission(true);
        }
    }

    private function authenticateByBearer(): bool
    {
        $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '');
        if ($header === '' || stripos($header, 'Bearer ') !== 0) {
            return false;
        }
        $token = trim(substr($header, 7));
        if ($token === '') {
            return false;
        }
        $payload = JwtService::decode($token);
        if (!$payload) {
            return false;
        }
        $userId = (int)($payload['sub'] ?? 0);
        if ($userId <= 0) {
            return false;
        }
        $user = $this->usuarios->find($userId);
        if (!$user) {
            return false;
        }
        Auth::login($user);
        return true;
    }

    private function clientesCached(bool $force = false): array
    {
        $scopeIds = Auth::allowedClientIds();
        $scopeKey = Auth::isInstituto() ? 'instituto' : ('scoped:' . implode(',', $scopeIds));
        $cache = $_SESSION['cache_auditoria_clientes'] ?? null;
        $cacheScope = $_SESSION['cache_auditoria_clientes_scope'] ?? null;
        $cacheTs = (int)($_SESSION['cache_auditoria_clientes_ts'] ?? 0);
        $expired = ($cacheTs <= 0 || (time() - $cacheTs) > 120);
        $needsRefresh = $force || !is_array($cache) || $cacheScope !== $scopeKey || $expired;
        if ($needsRefresh) {
            $cache = $this->clientes->all();
            $_SESSION['cache_auditoria_clientes'] = $cache;
            $_SESSION['cache_auditoria_clientes_scope'] = $scopeKey;
            $_SESSION['cache_auditoria_clientes_ts'] = time();
            AuditLogger::log('auditoria_clientes_cache_refresh', 'auditoria', null, [
                'total_clientes' => count($cache),
                'scope' => $scopeKey,
                'expired' => $expired,
            ]);
        }
        if (empty($cache)) {
            $cache = $this->clientes->all();
            $_SESSION['cache_auditoria_clientes'] = $cache;
            $_SESSION['cache_auditoria_clientes_scope'] = $scopeKey;
            $_SESSION['cache_auditoria_clientes_ts'] = time();
            AuditLogger::log('auditoria_clientes_empty_refetch', 'auditoria', null, [
                'total_clientes' => count($cache),
                'scope' => $scopeKey,
            ]);
        }
        return $cache;
    }

    private function setoresCached(int $clienteId, bool $force = false): array
    {
        if (!isset($_SESSION['cache_auditoria_setores']) || !is_array($_SESSION['cache_auditoria_setores'])) {
            $_SESSION['cache_auditoria_setores'] = [];
        }
        if (!isset($_SESSION['cache_auditoria_setores_ts']) || !is_array($_SESSION['cache_auditoria_setores_ts'])) {
            $_SESSION['cache_auditoria_setores_ts'] = [];
        }
        $cache = $_SESSION['cache_auditoria_setores'][$clienteId] ?? null;
        $ts = (int)($_SESSION['cache_auditoria_setores_ts'][$clienteId] ?? 0);
        $expired = ($ts <= 0 || (time() - $ts) > 60);
        $needsRefresh = $force || !is_array($cache) || $expired;
        if ($needsRefresh) {
            $cache = $this->setores->allByCliente($clienteId);
            $_SESSION['cache_auditoria_setores'][$clienteId] = $cache;
            $_SESSION['cache_auditoria_setores_ts'][$clienteId] = time();
        }
        if (empty($cache)) {
            $cache = $this->setores->allByCliente($clienteId);
            $_SESSION['cache_auditoria_setores'][$clienteId] = $cache;
            $_SESSION['cache_auditoria_setores_ts'][$clienteId] = time();
        }
        return $cache;
    }

    private function departamentosCached(int $clienteId = 0, bool $force = false): array
    {
        if (!isset($_SESSION['cache_auditoria_departamentos']) || !is_array($_SESSION['cache_auditoria_departamentos'])) {
            $_SESSION['cache_auditoria_departamentos'] = [];
        }
        if (!isset($_SESSION['cache_auditoria_departamentos_ts']) || !is_array($_SESSION['cache_auditoria_departamentos_ts'])) {
            $_SESSION['cache_auditoria_departamentos_ts'] = [];
        }
        $key = $clienteId > 0 ? ('cliente:' . $clienteId) : 'all';
        $cache = $_SESSION['cache_auditoria_departamentos'][$key] ?? null;
        $ts = (int)($_SESSION['cache_auditoria_departamentos_ts'][$key] ?? 0);
        $expired = ($ts <= 0 || (time() - $ts) > 60);
        $needsRefresh = $force || !is_array($cache) || $expired;
        if ($needsRefresh) {
            $cache = $clienteId > 0 ? $this->departamentos->allByCliente($clienteId) : $this->departamentos->all();
            $_SESSION['cache_auditoria_departamentos'][$key] = $cache;
            $_SESSION['cache_auditoria_departamentos_ts'][$key] = time();
        }
        if (empty($cache)) {
            $cache = $clienteId > 0 ? $this->departamentos->allByCliente($clienteId) : $this->departamentos->all();
            $_SESSION['cache_auditoria_departamentos'][$key] = $cache;
            $_SESSION['cache_auditoria_departamentos_ts'][$key] = time();
        }
        return $cache;
    }

    private function responsaveisBySetorCached(int $setorId, int $clienteId = 0): array
    {
        if (!isset($_SESSION['cache_auditoria_responsaveis']) || !is_array($_SESSION['cache_auditoria_responsaveis'])) {
            $_SESSION['cache_auditoria_responsaveis'] = [];
        }
        $key = $setorId . ':' . max(0, $clienteId);
        if (!array_key_exists($key, $_SESSION['cache_auditoria_responsaveis'])) {
            $_SESSION['cache_auditoria_responsaveis'][$key] = $this->colaboradores->allBySetor($setorId, $clienteId > 0 ? $clienteId : null);
        }
        return $_SESSION['cache_auditoria_responsaveis'][$key];
    }
}
