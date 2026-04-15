<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\AvaliacaoQuestionario;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\AvaliacaoModel;
use App\Models\AvaliacaoPublicaModel;
use App\Models\ClienteModel;
use App\Services\AvaliacaoPdfService;

class AvaliacoesController extends BaseController
{
    private AvaliacaoModel $model;
    private AvaliacaoPublicaModel $publicModel;

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
        $this->publicModel = new AvaliacaoPublicaModel();
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
        $faturamentoMedioAnual = $this->positiveInt($_POST['faturamento_medio_anual'] ?? null);
        $tomadorDecisao = $this->booleanFromInput($_POST['tomador_decisao'] ?? null);
        $whatsapp = preg_replace('/\D+/', '', $whatsappRaw ?? '') ?: '';
        $fin = $_POST['financeiro'] ?? [];
        $mer = $_POST['mercado'] ?? [];
        $pes = $_POST['pessoas'] ?? [];
        $pro = $_POST['processo'] ?? [];
        $notaFin = isset($_POST['nota_financeiro']) ? (int)$_POST['nota_financeiro'] : (is_array($fin) ? count($fin) : 0);
        $notaMer = isset($_POST['nota_mercado']) ? (int)$_POST['nota_mercado'] : (is_array($mer) ? count($mer) : 0);
        $notaPes = isset($_POST['nota_pessoas']) ? (int)$_POST['nota_pessoas'] : (is_array($pes) ? count($pes) : 0);
        $notaPro = isset($_POST['nota_processo']) ? (int)$_POST['nota_processo'] : (is_array($pro) ? count($pro) : 0);
        $realFin = isset($_POST['realidade_financeiro']) ? (int)$_POST['realidade_financeiro'] : null;
        $realMer = isset($_POST['realidade_mercado']) ? (int)$_POST['realidade_mercado'] : null;
        $realPes = isset($_POST['realidade_pessoas']) ? (int)$_POST['realidade_pessoas'] : null;
        $realPro = isset($_POST['realidade_processo']) ? (int)$_POST['realidade_processo'] : null;
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
        if ($faturamentoMedioAnual === null) {
            $errors['faturamento_medio_anual'] = 'Faturamento médio anual deve ser um inteiro positivo.';
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
            'tomador_decisao' => $tomadorDecisao,
            'origem_cadastro' => $clienteId > 0 ? 'cliente_existente' : 'potencial_cliente',
            'contato' => $nome,
            'respostas_json' => json_encode(['financeiro' => $fin, 'mercado' => $mer, 'pessoas' => $pes, 'processo' => $pro]),
            'nota_financeiro' => $notaFin,
            'nota_mercado' => $notaMer,
            'nota_pessoas' => $notaPes,
            'nota_processo' => $notaPro,
            'realidade_financeiro' => $realFin,
            'realidade_mercado' => $realMer,
            'realidade_pessoas' => $realPes,
            'realidade_processo' => $realPro,
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
        $publicLinkData = null;
        $publicLinkUrl = '';
        if ($item && (int)($item['cliente_id'] ?? 0) <= 0) {
            $clientesAssociacao = (new ClienteModel())->all();
        }
        if ($item) {
            $publicLinkData = $this->publicModel->findByAvaliacaoId((int)$item['id']);
        if (!empty($publicLinkData['slug']) || !empty($publicLinkData['token'])) {
            $publicLinkUrl = $this->buildPublicLink((string)($publicLinkData['slug'] ?: $publicLinkData['token']));
            }
        }
        $this->render('avaliacoes/show', compact('item', 'clientesAssociacao', 'publicLinkData', 'publicLinkUrl'));
    }

    public function planoacao(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        if (!$item) {
            http_response_code(404);
            echo 'Avaliação não encontrada.';
            return;
        }
        $respostas = json_decode($item['respostas_json'] ?? '{}', true) ?: [];
        $this->render('avaliacoes/planoacao', compact('item', 'respostas'));
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
            $publico = $this->publicModel->createStandaloneLink();
        } catch (\Throwable $e) {
            AuditLogger::log('avaliacao_publica_generate_error', 'avaliacao_publica', 0, [
                'message' => $e->getMessage(),
                'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
                'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            ]);
            $_SESSION['flash_error'] = 'Falha ao gerar o novo link público.';
            $this->redirect('index.php?route=avaliacoes/index');
            return;
        }
        if (empty($publico['slug']) && empty($publico['token'])) {
            AuditLogger::log('avaliacao_publica_generate_failed', 'avaliacao_publica', 0, []);
            $_SESSION['flash_error'] = 'Não foi possível gerar o link público.';
            $this->redirect('index.php?route=avaliacoes/index');
            return;
        }
        AuditLogger::log('avaliacao_publica_generate_success', 'avaliacao_publica', (int)($publico['id'] ?? 0), [
            'token' => $publico['token'] ?? null,
            'expiracao' => $publico['expiracao'] ?? null,
            'permanent' => empty($publico['expiracao']),
            'standalone' => true,
            'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        ]);
        $_SESSION['generated_public_link'] = [
            'avaliacao_id' => 0,
            'public_id' => (int)($publico['id'] ?? 0),
            'empresa' => '',
            'url' => $this->buildPublicLink((string)($publico['slug'] ?? $publico['token'] ?? '')),
            'token' => (string)($publico['token'] ?? ''),
            'slug' => (string)($publico['slug'] ?? ''),
            'expiracao' => (string)($publico['expiracao'] ?? ''),
            'permanent' => empty($publico['expiracao']),
        ];
        $_SESSION['flash_success'] = 'Link público permanente gerado com sucesso.';
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
            $publico = $this->publicModel->createStandaloneLink();
            AuditLogger::log('avaliacao_publica_api_generate', 'avaliacao_publica', (int)($publico['id'] ?? 0), [
                'token' => $publico['token'] ?? null,
                'permanent' => empty($publico['expiracao']),
                'standalone' => true,
                'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
                'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            ]);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => [
                    'avaliacao_id' => 0,
                    'public_id' => (int)($publico['id'] ?? 0),
                    'token' => $publico['token'] ?? null,
                    'slug' => $publico['slug'] ?? null,
                    'public_url' => $this->buildPublicLink((string)($publico['slug'] ?? $publico['token'] ?? '')),
                    'permanent' => empty($publico['expiracao']),
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            AuditLogger::log('avaliacao_publica_api_generate_error', 'avaliacao_publica', 0, [
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
            echo 'Falha ao gerar PDF.';
        }
    }

    private function buildPublicLink(string $identifier): string
    {
        $configured = trim((string)(getenv('PUBLIC_EVALUATION_BASE_URL') ?: ''));
        if ($configured !== '') {
            if (str_contains($configured, '{identifier}')) {
                return str_replace('{identifier}', rawurlencode($identifier), $configured);
            }
            if (str_contains($configured, '?')) {
                return rtrim($configured, '&') . rawurlencode($identifier);
            }
            return rtrim($configured, '/') . '/' . rawurlencode($identifier);
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        if (PHP_SAPI === 'cli' || strpos($scriptName, '/app/tests/') !== false) {
            $scriptName = '/index.php';
        }
        $base = rtrim(dirname($scriptName), '/');
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        if ($base !== '' && strpos($base, '/') !== 0) {
            $base = '/' . ltrim($base, '/');
        }
        return $scheme . '://' . $host . $base . '/index.php?route=avaliacao-publica/open&slug=' . rawurlencode($identifier);
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
