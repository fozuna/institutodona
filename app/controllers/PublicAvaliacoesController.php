<?php
namespace App\Controllers;

use App\Core\AvaliacaoQuestionario;
use App\Core\AuditLogger;
use App\Core\PublicRateLimiter;
use App\Models\AvaliacaoModel;
use App\Services\AvaliacaoPdfService;
use App\Services\PublicAvaliacaoContextResolver;

class PublicAvaliacoesController
{
    private PublicRateLimiter $rateLimiter;
    private PublicAvaliacaoContextResolver $contextResolver;
    private AvaliacaoModel $avaliacoes;

    public function __construct()
    {
        $this->rateLimiter = new PublicRateLimiter();
        $this->contextResolver = new PublicAvaliacaoContextResolver();
        $this->avaliacoes = new AvaliacaoModel();
    }

    public function handle(): void
    {
        $this->applyHeaders();
        $context = $this->contextResolver->resolveFromCurrentHost();
        if (!$context) {
            http_response_code(404);
            echo 'Formulario publico indisponivel para este dominio.';
            return;
        }
        if ((string)($_GET['download'] ?? '') === 'pdf') {
            $this->downloadPdf($context);
            return;
        }
        if (!$this->allowRequest()) {
            http_response_code(429);
            $this->render($context, 1, $this->defaultValues($context), [], [], false, '', '');
            return;
        }

        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST') {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'start') {
                $this->start($context);
                return;
            }
            if ($action === 'finish') {
                $this->finish($context);
                return;
            }
        }

        $submitted = !empty($_GET['submitted']);
        $pdfUrl = '';
        $avaliacaoId = (int)($_GET['avaliacao_id'] ?? 0);
        $sig = (string)($_GET['sig'] ?? '');
        if ($submitted && $avaliacaoId > 0 && $this->isValidDownloadSignature($avaliacaoId, $sig)) {
            $pdfUrl = $this->currentUrl() . '?download=pdf&avaliacao_id=' . $avaliacaoId . '&sig=' . rawurlencode($sig);
        }
        $this->render($context, 1, $this->defaultValues($context), [], [], $submitted, '', $pdfUrl);
    }

    private function start(array $context): void
    {
        $values = $this->valuesFromPost($context);
        $errors = $this->validateStep1($values);
        if (!empty($errors)) {
            http_response_code(422);
            $this->render($context, 1, $values, $errors, [], false, '', '');
            return;
        }
        AuditLogger::log('public_avaliacoes_start', 'avaliacao_publica_fixa', 0, [
            'host' => $context['host'] ?? null,
            'cliente_id' => $context['cliente_id'] ?? null,
            'empresa_nome' => $context['empresa_nome'] ?? null,
        ]);
        $this->render($context, 2, $values, [], [], false, '', '');
    }

    private function finish(array $context): void
    {
        $values = $this->valuesFromPost($context);
        $errors = $this->validateStep1($values);
        if (!empty($errors)) {
            http_response_code(422);
            $this->render($context, 1, $values, $errors, [], false, '', '');
            return;
        }
        $selectedMap = [
            'financeiro' => array_map('intval', (array)($_POST['financeiro'] ?? [])),
            'mercado' => array_map('intval', (array)($_POST['mercado'] ?? [])),
            'pessoas' => array_map('intval', (array)($_POST['pessoas'] ?? [])),
            'processo' => array_map('intval', (array)($_POST['processo'] ?? [])),
        ];
        $totais = array_map('count', AvaliacaoQuestionario::pilares());
        $avaliacaoId = $this->avaliacoes->create([
            'cliente_id' => !empty($context['cliente_id']) ? (int)$context['cliente_id'] : null,
            'empresa_nome' => $context['empresa_nome'],
            'nome' => $values['nome'],
            'email' => $values['email'],
            'whatsapp' => $values['whatsapp'],
            'numero_funcionarios' => (int)$values['numero_funcionarios'],
            'numero_lideres' => (int)$values['numero_lideres'],
            'faturamento_medio_anual' => (int)$values['faturamento_anual'],
            'tomador_decisao' => (int)$values['tomador_decisao'],
            'origem_cadastro' => 'formulario_publico_fixo',
            'contato' => $values['nome'],
            'respostas_json' => json_encode($selectedMap),
            'nota_financeiro' => count($selectedMap['financeiro']),
            'nota_mercado' => count($selectedMap['mercado']),
            'nota_pessoas' => count($selectedMap['pessoas']),
            'nota_processo' => count($selectedMap['processo']),
            'realidade_financeiro' => $this->toPercent(count($selectedMap['financeiro']), (int)($totais['financeiro'] ?? 1)),
            'realidade_mercado' => $this->toPercent(count($selectedMap['mercado']), (int)($totais['mercado'] ?? 1)),
            'realidade_pessoas' => $this->toPercent(count($selectedMap['pessoas']), (int)($totais['pessoas'] ?? 1)),
            'realidade_processo' => $this->toPercent(count($selectedMap['processo']), (int)($totais['processo'] ?? 1)),
        ]);
        if ($avaliacaoId <= 0) {
            http_response_code(500);
            $this->render($context, 2, $values, [], $selectedMap, false, 'Nao foi possivel salvar a avaliacao.', '');
            return;
        }
        (new AvaliacaoPdfService())->generateToFile($avaliacaoId, true);
        AuditLogger::log('public_avaliacoes_finish', 'avaliacao_publica_fixa', $avaliacaoId, [
            'host' => $context['host'] ?? null,
            'cliente_id' => $context['cliente_id'] ?? null,
            'empresa_nome' => $context['empresa_nome'] ?? null,
        ]);
        $this->redirect($this->currentUrl() . '?submitted=1&avaliacao_id=' . $avaliacaoId . '&sig=' . $this->downloadSignature($avaliacaoId));
    }

    private function downloadPdf(array $context): void
    {
        $avaliacaoId = (int)($_GET['avaliacao_id'] ?? 0);
        $sig = (string)($_GET['sig'] ?? '');
        if ($avaliacaoId <= 0 || !$this->isValidDownloadSignature($avaliacaoId, $sig)) {
            http_response_code(403);
            echo 'Download nao autorizado.';
            return;
        }
        $item = $this->avaliacoes->find($avaliacaoId);
        if (!$item) {
            http_response_code(404);
            echo 'Avaliacao nao encontrada.';
            return;
        }
        if (!empty($context['cliente_id']) && (int)($item['cliente_id'] ?? 0) !== (int)$context['cliente_id']) {
            http_response_code(403);
            echo 'Download nao autorizado.';
            return;
        }
        if (!$item['cliente_id'] && strtolower((string)($item['empresa_nome'] ?? '')) !== strtolower((string)($context['empresa_nome'] ?? ''))) {
            http_response_code(403);
            echo 'Download nao autorizado.';
            return;
        }
        $ok = (new AvaliacaoPdfService())->outputToBrowser($avaliacaoId, true);
        if (!$ok) {
            http_response_code(404);
            echo 'PDF nao disponivel.';
        }
    }

    private function valuesFromPost(array $context): array
    {
        return [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'empresa' => (string)$context['empresa_nome'],
            'whatsapp' => preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?: '',
            'email' => trim((string)($_POST['email'] ?? '')),
            'numero_funcionarios' => trim((string)($_POST['numero_funcionarios'] ?? '')),
            'numero_lideres' => trim((string)($_POST['numero_lideres'] ?? '')),
            'faturamento_anual' => trim((string)($_POST['faturamento_anual'] ?? '')),
            'tomador_decisao' => (string)($_POST['tomador_decisao'] ?? ''),
        ];
    }

    private function defaultValues(array $context): array
    {
        return [
            'nome' => '',
            'empresa' => (string)$context['empresa_nome'],
            'whatsapp' => '',
            'email' => '',
            'numero_funcionarios' => '',
            'numero_lideres' => '',
            'faturamento_anual' => '',
            'tomador_decisao' => '',
        ];
    }

    private function validateStep1(array $values): array
    {
        $errors = [];
        if ($values['nome'] === '') {
            $errors['nome'] = 'Nome e obrigatorio.';
        }
        if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail valido.';
        }
        if (!preg_match('/^\d{10,15}$/', (string)$values['whatsapp'])) {
            $errors['whatsapp'] = 'Informe um WhatsApp valido.';
        }
        foreach (['numero_funcionarios', 'numero_lideres', 'faturamento_anual'] as $field) {
            if (!preg_match('/^\d+$/', (string)$values[$field])) {
                $errors[$field] = 'Informe um numero inteiro positivo.';
            }
        }
        if (!in_array((string)$values['tomador_decisao'], ['0', '1'], true)) {
            $errors['tomador_decisao'] = 'Selecione se e tomador de decisao.';
        }
        return $errors;
    }

    private function render(array $context, int $step, array $values, array $errors, array $selectedMap, bool $submitted, string $formError, string $pdfUrl): void
    {
        $record = [
            'empresa' => $context['empresa_nome'],
        ];
        $qs = AvaliacaoQuestionario::pilares();
        $publicUrl = $this->currentUrl();
        $formAction = $publicUrl;
        $isStaticPublic = true;
        $contextEmpresa = (string)$context['empresa_nome'];
        require __DIR__ . '/../views/avaliacoes/publica.php';
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/public/avaliacoes.php'));
        return $scheme . '://' . $host . $scriptName;
    }

    private function applyHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' data:; img-src 'self' data:;");
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    private function allowRequest(): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 120);
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $action = strtolower((string)($_POST['action'] ?? 'view'));
        $key = 'public_avaliacoes_fixa|' . $ip . '|' . md5($agent) . '|' . $method . '|' . $action;
        $limit = $method === 'POST' ? 60 : 300;
        return $this->rateLimiter->allow($key, $limit, 300);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function downloadSignature(int $avaliacaoId): string
    {
        $secret = (string)(getenv('PUBLIC_PDF_SECRET') ?: getenv('APP_KEY') ?: 'institutodona-public-pdf');
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        return hash_hmac('sha256', $host . '|' . $avaliacaoId, $secret);
    }

    private function isValidDownloadSignature(int $avaliacaoId, string $sig): bool
    {
        return $sig !== '' && hash_equals($this->downloadSignature($avaliacaoId), $sig);
    }

    private function toPercent(int $nota, int $total): int
    {
        return (int)round(($nota / max(1, $total)) * 100);
    }
}
