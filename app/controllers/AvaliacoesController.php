<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\AvaliacaoQuestionario;
use App\Core\BaseController;
use App\Core\FaturamentoFaixas;
use App\Core\Security;
use App\Models\AvaliacaoModel;
use App\Models\ClienteModel;
use App\Services\AvaliacaoPdfService;

class AvaliacoesController extends BaseController
{
    private const PILLAR_SLOT_MAP = [
        'eu' => 'financeiro',
        'lideranca' => 'mercado',
        'processo' => 'pessoas',
        'gestao' => 'processo',
    ];

    private AvaliacaoModel $model;

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $items = $this->model->all();
        $clientes = (new ClienteModel())->all();
        $this->render('avaliacoes/index', compact('items', 'clientes'));
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = (new ClienteModel())->all();
        $values = $this->defaultValues();
        if ($cliente > 0) {
            $values['modo_cadastro'] = 'existente';
            foreach ($clientes as $cl) {
                if ((int)($cl['id'] ?? 0) === $cliente) {
                    $values['empresa_nome'] = (string)($cl['nome_empresa'] ?? '');
                    break;
                }
            }
        }
        $errors = [];
        $this->render('avaliacoes/create', compact('cliente', 'clientes', 'values', 'errors'));
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $clientes = (new ClienteModel())->all();
        $clientesById = [];
        foreach ($clientes as $cl) {
            $cid = (int)($cl['id'] ?? 0);
            if ($cid > 0) {
                $clientesById[$cid] = (string)($cl['nome_empresa'] ?? '');
            }
        }
        $clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        $modoCadastro = (string)($_POST['modo_cadastro'] ?? ($clienteId > 0 ? 'existente' : 'potencial'));
        if ($modoCadastro !== 'existente') {
            $clienteId = 0;
        }
        $empresaNome = trim($_POST['empresa_nome'] ?? '');
        if ($empresaNome === '' && $clienteId > 0 && isset($clientesById[$clienteId])) {
            $empresaNome = trim($clientesById[$clienteId]);
        }
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsappRaw = (string)($_POST['whatsapp'] ?? '');
        $numeroFuncionarios = $this->positiveInt($_POST['numero_funcionarios'] ?? null);
        $numeroLideres = $this->positiveInt($_POST['numero_lideres'] ?? null);
        $faturamentoFaixaId = FaturamentoFaixas::normalizeId($_POST['faturamento_faixa_id'] ?? null);
        $faturamentoMedioAnual = $faturamentoFaixaId !== null
            ? FaturamentoFaixas::representativeAmountForId($faturamentoFaixaId)
            : $this->positiveInt($_POST['faturamento_medio_anual'] ?? null);
        $tomadorDecisao = $this->booleanFromInput($_POST['tomador_decisao'] ?? null);
        $whatsapp = preg_replace('/\D+/', '', $whatsappRaw ?? '') ?: '';
        $responses = [];
        $notes = [];
        $realities = [];
        foreach (self::PILLAR_SLOT_MAP as $pillar => $slot) {
            $selected = $_POST[$pillar] ?? $_POST[$slot] ?? [];
            $responses[$pillar] = is_array($selected) ? array_map('intval', $selected) : [];
            $notes[$slot] = isset($_POST['nota_' . $pillar]) ? (int)$_POST['nota_' . $pillar] : (isset($_POST['nota_' . $slot]) ? (int)$_POST['nota_' . $slot] : count($responses[$pillar]));
            $realities[$slot] = isset($_POST['realidade_' . $pillar]) ? (int)$_POST['realidade_' . $pillar] : (isset($_POST['realidade_' . $slot]) ? (int)$_POST['realidade_' . $slot] : null);
        }
        $errors = [];
        if ($modoCadastro === 'existente' && ($clienteId <= 0 || !isset($clientesById[$clienteId]))) {
            $errors['cliente_id'] = 'Selecione um cliente válido.';
        }
        if ($empresaNome === '') {
            $errors['empresa_nome'] = 'Empresa é obrigatória.';
        }
        if ($nome === '') {
            $errors['nome'] = 'Nome é obrigatório.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        if (!$this->isValidWhatsapp($whatsapp)) {
            $errors['whatsapp'] = 'Informe um WhatsApp válido apenas com números.';
        }
        if ($numeroFuncionarios === null) {
            $errors['numero_funcionarios'] = 'Número de funcionários deve ser um inteiro positivo.';
        }
        if ($numeroLideres === null) {
            $errors['numero_lideres'] = 'Número de líderes deve ser um inteiro positivo.';
        }
        if ($faturamentoFaixaId === null && $faturamentoMedioAnual === null) {
            $errors['faturamento_faixa_id'] = 'Selecione uma faixa de faturamento.';
        }
        if ($tomadorDecisao === null) {
            $errors['tomador_decisao'] = 'Selecione se é tomador de decisão.';
        }
        $values = [
            'modo_cadastro' => $modoCadastro,
            'cliente_id' => $clienteId,
            'empresa_nome' => $empresaNome,
            'nome' => $nome,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'numero_funcionarios' => (string)($_POST['numero_funcionarios'] ?? ''),
            'numero_lideres' => (string)($_POST['numero_lideres'] ?? ''),
            'faturamento_faixa_id' => (string)($_POST['faturamento_faixa_id'] ?? ''),
            'faturamento_medio_anual' => (string)($_POST['faturamento_medio_anual'] ?? ''),
            'tomador_decisao' => (string)($_POST['tomador_decisao'] ?? ''),
        ];
        if (!empty($errors)) {
            $cliente = $clienteId;
            $this->render('avaliacoes/create', compact('cliente', 'clientes', 'values', 'errors'));
            return;
        }
        $payload = [
            'cliente_id' => $clienteId ?: null,
            'empresa_nome' => $empresaNome ?: null,
            'nome' => $nome,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'numero_funcionarios' => $numeroFuncionarios,
            'numero_lideres' => $numeroLideres,
            'faturamento_medio_anual' => $faturamentoMedioAnual,
            'faturamento_faixa_id' => $faturamentoFaixaId ?? FaturamentoFaixas::inferIdFromAmount($faturamentoMedioAnual),
            'tomador_decisao' => $tomadorDecisao,
            'origem_cadastro' => $clienteId > 0 ? 'cliente_existente' : 'potencial_cliente',
            'contato' => $nome,
            'respostas_json' => json_encode($responses),
            'nota_financeiro' => $notes['financeiro'],
            'nota_mercado' => $notes['mercado'],
            'nota_pessoas' => $notes['pessoas'],
            'nota_processo' => $notes['processo'],
            'realidade_financeiro' => $realities['financeiro'],
            'realidade_mercado' => $realities['mercado'],
            'realidade_pessoas' => $realities['pessoas'],
            'realidade_processo' => $realities['processo'],
        ];
        $id = $this->model->create($payload);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível salvar a avaliação.';
            $cliente = $clienteId;
            $errors = [];
            $this->render('avaliacoes/create', compact('cliente', 'clientes', 'values', 'errors'));
            return;
        }
        $_SESSION['flash_success'] = 'Avaliação salva com sucesso.';
        \App\Core\AuditLogger::log('create', 'avaliacao', $id, $payload);
        if ($clienteId) {
            $this->redirect('index.php?route=avaliacoes/show&id=' . $id . '&cliente=' . $clienteId);
        } else {
            $this->redirect('index.php?route=avaliacoes/show&id=' . $id);
        }
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        $clientesAssociacao = [];
        if ($item && (int)($item['cliente_id'] ?? 0) <= 0) {
            $clientesAssociacao = (new ClienteModel())->all();
        }
        $this->render('avaliacoes/show', compact('item', 'clientesAssociacao'));
    }

    public function associarCliente(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $avaliacaoId = (int)($_POST['avaliacao_id'] ?? 0);
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        if ($avaliacaoId <= 0 || $clienteId <= 0) {
            $_SESSION['flash_error'] = 'Selecione uma avaliação e um cliente válidos.';
            $this->redirect('index.php?route=avaliacoes/show&id=' . $avaliacaoId);
            return;
        }
        $ok = $this->model->associateCliente($avaliacaoId, $clienteId);
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível associar a avaliação ao cliente selecionado.';
            $this->redirect('index.php?route=avaliacoes/show&id=' . $avaliacaoId);
            return;
        }
        AuditLogger::log('associate_cliente', 'avaliacao', $avaliacaoId, [
            'avaliacao_id' => $avaliacaoId,
            'cliente_id' => $clienteId,
        ]);
        $_SESSION['flash_success'] = 'Cliente associado à avaliação com sucesso.';
        $this->redirect('index.php?route=avaliacoes/show&id=' . $avaliacaoId . '&cliente=' . $clienteId);
    }

    public function gerarLinkCliente(): void
    {
        $this->requireLogin();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        try {
            $_SESSION['generated_public_link'] = [
                'avaliacao_id' => 0,
                'public_id' => 0,
                'empresa' => '',
                'url' => $this->buildPublicLink(),
                'static' => true,
                'permanent' => true,
            ];
            AuditLogger::log('avaliacao_publica_static_link_access', 'avaliacao_publica_fixa', 0, [
            'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        ]);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Não foi possível disponibilizar o formulário público.';
            $this->redirect('index.php?route=avaliacoes/index');
            return;
        }
        $_SESSION['flash_success'] = 'Formulário público fixo disponibilizado com sucesso.';
        $this->redirect('index.php?route=avaliacoes/index');
    }

    public function apiGeneratePublicLink(): void
    {
        $this->requireLogin();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.', 'error' => 'invalid_request'], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            AuditLogger::log('avaliacao_publica_api_static_link', 'avaliacao_publica_fixa', 0, [
                'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
                'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            ]);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => [
                    'avaliacao_id' => 0,
                    'public_id' => 0,
                    'token' => null,
                    'slug' => null,
                    'public_url' => $this->buildPublicLink(),
                    'permanent' => true,
                    'static' => true,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            AuditLogger::log('avaliacao_publica_api_static_link_error', 'avaliacao_publica_fixa', 0, [
                'message' => $e->getMessage(),
                'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
                'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            ]);
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falha ao gerar link público.', 'error' => 'public_link_generation_failed'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function logLinkShare(): void
    {
        $this->requireLogin();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Requisição inválida.']);
            return;
        }
        $avaliacaoId = (int)($_POST['avaliacao_id'] ?? 0);
        $publicId = (int)($_POST['public_id'] ?? 0);
        $channel = trim((string)($_POST['channel'] ?? ''));
        $url = trim((string)($_POST['url'] ?? ''));
        $success = trim((string)($_POST['success'] ?? ''));
        if (($avaliacaoId <= 0 && $publicId <= 0) || $channel === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Dados inválidos.']);
            return;
        }
        AuditLogger::log('avaliacao_publica_share', 'avaliacao_publica', $publicId > 0 ? $publicId : $avaliacaoId, [
            'avaliacao_id' => $avaliacaoId,
            'public_id' => $publicId,
            'channel' => $channel,
            'url' => $url,
            'success' => $success,
        ]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function relatorioPdf(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        if (!$item) {
            http_response_code(404);
            echo 'Avaliação não encontrada.';
            return;
        }
        if (!\App\Core\PdfSupport::isDompdfAvailable()) {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'avaliacao', $id, [
                'error_id' => $errorId,
                'service' => 'AvaliacaoPdfService',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            @error_log('[pdf_unavailable] id=' . $errorId . ' route=avaliacoes/relatorio_pdf avaliacao_id=' . $id);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        $service = new AvaliacaoPdfService();
        if (!empty($_GET['preview'])) {
            $html = $service->renderHtml($id);
            if ($html === null) {
                http_response_code(404);
                echo 'Prévia não disponível.';
                return;
            }
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            return;
        }
        AuditLogger::log('avaliacao_pdf_export', 'avaliacao', $id, [
            'avaliacao_id' => $id,
            'download' => !empty($_GET['download']),
        ]);
        if (!$service->outputToBrowser($id, !empty($_GET['download']))) {
            http_response_code(500);
            echo 'Falha ao gerar PDF: ' . ($service->getLastError() ?: 'erro desconhecido');
        }
    }

    public function deleteAjax(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=UTF-8');

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Avaliação inválida.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $item = $this->model->find($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Avaliação não encontrada.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $ok = $this->model->delete($id);
        if (!$ok) {
            $err = $this->model->getLastError() ?: 'unknown';
            $ctx = $this->model->getLastErrorContext();
            AuditLogger::log('delete_failed', 'avaliacao', $id, [
                'error' => $err,
                'context' => $ctx,
            ]);
            if ($err === 'constraint') {
                http_response_code(409);
                echo json_encode(['ok' => false, 'message' => 'Não foi possível excluir a avaliação pois existem dados vinculados.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Não foi possível excluir a avaliação. Tente novamente.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdfPath = (new AvaliacaoPdfService())->pdfPath($id);
        if ($pdfPath !== '' && is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        AuditLogger::log('delete', 'avaliacao', $id, [
            'via' => 'ajax',
            'cliente_id' => (int)($item['cliente_id'] ?? 0),
        ]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function buildPublicLink(string $identifier = ''): string
    {
        $configured = trim((string)(getenv('PUBLIC_AVALIACOES_STATIC_URL') ?: getenv('PUBLIC_EVALUATION_BASE_URL') ?: ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        if (PHP_SAPI === 'cli' || strpos($scriptName, '/app/tests/') !== false) {
            $scriptName = '';
        }
        $base = rtrim(dirname($scriptName), '/');
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        if ($base !== '' && strpos($base, '/') !== 0) {
            $base = '/' . ltrim($base, '/');
        }
        return $scheme . '://' . $host . $base . '/public/avaliacoes.php';
    }


    private function defaultValues(): array
    {
        return [
            'modo_cadastro' => 'potencial',
            'cliente_id' => '',
            'empresa_nome' => '',
            'nome' => '',
            'email' => '',
            'whatsapp' => '',
            'numero_funcionarios' => '',
            'numero_lideres' => '',
            'faturamento_faixa_id' => '',
            'faturamento_medio_anual' => '',
            'tomador_decisao' => '',
        ];
    }

    private function positiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $raw = trim((string)$value);
        if (!preg_match('/^[1-9]\d*$/', $raw)) {
            return null;
        }
        return (int)$raw;
    }

    private function booleanFromInput($value): ?int
    {
        if ($value === '1' || $value === 1 || $value === true || $value === 'sim') {
            return 1;
        }
        if ($value === '0' || $value === 0 || $value === false || $value === 'nao' || $value === 'não') {
            return 0;
        }
        return null;
    }

    private function isValidWhatsapp(string $digits): bool
    {
        if (!preg_match('/^\d+$/', $digits)) {
            return false;
        }
        if (strlen($digits) === 12 || strlen($digits) === 13) {
            if (strpos($digits, '55') !== 0) {
                return false;
            }
            $digits = substr($digits, 2);
        }
        return (bool)preg_match('/^\d{10,11}$/', $digits);
    }
}
