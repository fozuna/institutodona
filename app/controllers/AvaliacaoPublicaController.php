<?php
namespace App\Controllers;

use App\Core\AvaliacaoQuestionario;
use App\Core\AuditLogger;
use App\Core\PublicRateLimiter;
use App\Models\AvaliacaoModel;
use App\Models\AvaliacaoPublicaModel;

class AvaliacaoPublicaController
{
    private AvaliacaoPublicaModel $publicas;
    private PublicRateLimiter $rateLimiter;

    public function __construct()
    {
        $this->publicas = new AvaliacaoPublicaModel();
        $this->rateLimiter = new PublicRateLimiter();
    }

    public function handle(): void
    {
        $this->applyPublicSecurityHeaders();
        if ((string)($_GET['resource'] ?? '') === 'validate') {
            $this->validateApi();
            return;
        }
        $identifier = $this->resolveIdentifierFromRequest();
        if (!$this->allowRequest($identifier)) {
            AuditLogger::log('avaliacao_publica_rate_limited', 'avaliacao_publica', 0, [
                'identifier' => $identifier,
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                'method' => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            ]);
            http_response_code(429);
            $this->render([
                'rateLimited' => true,
                'invalidToken' => false,
                'expiredToken' => false,
                'alreadyDone' => false,
                'record' => null,
                'step' => 1,
                'values' => $this->defaultValues(),
                'errors' => [],
                'selectedMap' => [],
                'qs' => AvaliacaoQuestionario::pilares(),
                'publicUrl' => '',
                'formAction' => $this->publicUrlByIdentifier($identifier),
                'submitted' => false,
            ]);
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'start') {
                $this->start($identifier);
                return;
            }
            if ($action === 'finish') {
                $this->finish($identifier);
                return;
            }
        }
        $this->show($identifier);
    }

    private function show(string $identifier): void
    {
        $record = $this->findPublicRecord($identifier);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_access_invalid', 'avaliacao_publica', 0, ['identifier' => $identifier]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        AuditLogger::log('avaliacao_publica_access', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $record['token'] ?? null,
            'slug' => $record['slug'] ?? null,
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        ]);
        $this->render([
            'rateLimited' => false,
            'invalidToken' => false,
            'expiredToken' => false,
            'alreadyDone' => false,
            'record' => $record,
            'step' => 1,
            'values' => $this->defaultValues(),
            'errors' => [],
            'selectedMap' => [],
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => $this->publicUrlByRecord($record),
            'formAction' => $this->publicUrlByRecord($record),
            'submitted' => !empty($_GET['submitted']),
        ]);
    }

    private function start(string $identifier): void
    {
        $record = $this->findPublicRecord($identifier);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_start_not_found', 'avaliacao_publica', 0, ['identifier' => $identifier]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        $values = [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'empresa' => trim((string)($_POST['empresa'] ?? '')),
            'whatsapp' => preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?: '',
            'email' => trim((string)($_POST['email'] ?? '')),
            'numero_funcionarios' => trim((string)($_POST['numero_funcionarios'] ?? '')),
            'numero_lideres' => trim((string)($_POST['numero_lideres'] ?? '')),
            'faturamento_anual' => trim((string)($_POST['faturamento_anual'] ?? '')),
            'tomador_decisao' => (string)($_POST['tomador_decisao'] ?? ''),
        ];
        $errors = $this->validateStep1($values);
        if (!empty($errors)) {
            AuditLogger::log('avaliacao_publica_start_validation_error', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
                'token' => $record['token'] ?? null,
                'slug' => $record['slug'] ?? null,
                'errors' => array_keys($errors),
            ]);
            http_response_code(422);
            $this->render([
                'rateLimited' => false,
                'invalidToken' => false,
                'expiredToken' => false,
                'alreadyDone' => false,
                'record' => $record,
                'step' => 1,
                'values' => $values,
                'errors' => $errors,
                'selectedMap' => [],
                'qs' => AvaliacaoQuestionario::pilares(),
                'publicUrl' => $this->publicUrlByRecord($record),
                'formAction' => $this->publicUrlByRecord($record),
                'submitted' => false,
            ]);
            return;
        }
        AuditLogger::log('avaliacao_publica_started', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $record['token'] ?? null,
            'slug' => $record['slug'] ?? null,
            'nome' => $values['nome'],
            'empresa' => $values['empresa'],
        ]);
        $this->render([
            'rateLimited' => false,
            'invalidToken' => false,
            'expiredToken' => false,
            'alreadyDone' => false,
            'record' => $record,
            'step' => 2,
            'values' => $values,
            'errors' => [],
            'selectedMap' => [],
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => $this->publicUrlByRecord($record),
            'formAction' => $this->publicUrlByRecord($record),
            'submitted' => false,
        ]);
    }

    private function finish(string $identifier): void
    {
        $record = $this->findPublicRecord($identifier);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_finish_not_found', 'avaliacao_publica', 0, ['identifier' => $identifier]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        $values = [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'empresa' => trim((string)($_POST['empresa'] ?? '')),
            'whatsapp' => preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?: '',
            'email' => trim((string)($_POST['email'] ?? '')),
            'numero_funcionarios' => trim((string)($_POST['numero_funcionarios'] ?? '')),
            'numero_lideres' => trim((string)($_POST['numero_lideres'] ?? '')),
            'faturamento_anual' => trim((string)($_POST['faturamento_anual'] ?? '')),
            'tomador_decisao' => (string)($_POST['tomador_decisao'] ?? ''),
        ];
        $errors = $this->validateStep1($values);
        if (!empty($errors)) {
            http_response_code(422);
            $this->render([
                'rateLimited' => false,
                'invalidToken' => false,
                'expiredToken' => false,
                'alreadyDone' => false,
                'record' => $record,
                'step' => 1,
                'values' => $values,
                'errors' => $errors,
                'selectedMap' => [],
                'qs' => AvaliacaoQuestionario::pilares(),
                'publicUrl' => $this->publicUrlByRecord($record),
                'formAction' => $this->publicUrlByRecord($record),
                'submitted' => false,
            ]);
            return;
        }
        $financeiro = array_map('intval', (array)($_POST['financeiro'] ?? []));
        $mercado = array_map('intval', (array)($_POST['mercado'] ?? []));
        $pessoas = array_map('intval', (array)($_POST['pessoas'] ?? []));
        $processo = array_map('intval', (array)($_POST['processo'] ?? []));
        $totais = AvaliacaoQuestionario::totais();
        $notaFin = count($financeiro);
        $notaMer = count($mercado);
        $notaPes = count($pessoas);
        $notaPro = count($processo);
        $selectedMap = [
            'financeiro' => $financeiro,
            'mercado' => $mercado,
            'pessoas' => $pessoas,
            'processo' => $processo,
        ];
        try {
            $avaliacaoId = $this->createAvaliacaoFromPublicSubmission($record, $values, $selectedMap, $totais);
        } catch (\Throwable $e) {
            $this->renderSubmissionError(2, $record, $values, $selectedMap, 'Falha ao salvar a avaliação.', $e);
            return;
        }
        if ($avaliacaoId <= 0) {
            AuditLogger::log('avaliacao_publica_finish_noop', 'avaliacao_publica', 0, [
                'token' => $record['token'] ?? null,
                'slug' => $record['slug'] ?? null,
            ]);
            $this->renderSubmissionError(2, $record, $values, $selectedMap, 'Não foi possível salvar a avaliação.', null);
            return;
        }
        AuditLogger::log('avaliacao_publica_finished', 'avaliacao_publica', $avaliacaoId, [
            'token' => $record['token'] ?? null,
            'slug' => $record['slug'] ?? null,
            'avaliacao_id' => $avaliacaoId,
            'nota_total' => $notaFin + $notaMer + $notaPes + $notaPro,
        ]);
        $this->redirect($this->publicUrlByRecord($record) . (str_contains($this->publicUrlByRecord($record), '?') ? '&' : '?') . 'submitted=1');
    }

    public function validateApi(): void
    {
        $this->applyPublicSecurityHeaders();
        $identifier = $this->resolveIdentifierFromRequest();
        if (!$this->allowRequest($identifier)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Muitas tentativas.', 'error' => 'rate_limited'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $record = $this->findPublicRecord($identifier);
        $available = (bool)$record;
        AuditLogger::log('avaliacao_publica_validate', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $record['token'] ?? null,
            'slug' => $record['slug'] ?? null,
            'available' => $available,
            'status' => $record ? 'active' : 'invalido',
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        ]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => [
                'token' => $record['token'] ?? null,
                'slug' => $record['slug'] ?? null,
                'valid' => (bool)$record,
                'available' => (bool)$available,
                'status' => $record ? 'active' : 'invalido',
                'permanent' => (bool)$record,
                'expired' => false,
                'public_url' => $record ? $this->publicUrlByRecord($record) : null,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function applyPublicSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    private function renderSubmissionError(int $step, array $record, array $values, array $selectedMap, string $message, ?\Throwable $e): void
    {
        if ($e) {
            AuditLogger::log('avaliacao_publica_submission_error', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
                'token' => $record['token'] ?? null,
                'step' => $step,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
        $debug = getenv('APP_ENV') && strtolower((string)getenv('APP_ENV')) !== 'production' ? ($e ? $e->getMessage() : '') : '';
        http_response_code(409);
        $this->render([
            'rateLimited' => false,
            'invalidToken' => false,
            'expiredToken' => false,
            'alreadyDone' => false,
            'record' => $record,
            'step' => $step,
            'values' => $values,
            'errors' => [],
            'selectedMap' => $selectedMap,
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => $this->publicUrlByRecord($record),
            'formAction' => $this->publicUrlByRecord($record),
            'formError' => $debug !== '' ? ($message . ' [' . $debug . ']') : $message,
            'submitted' => false,
        ]);
    }

    private function render(array $params): void
    {
        extract($params, EXTR_SKIP);
        require __DIR__ . '/../views/avaliacoes/publica.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function defaultValues(): array
    {
        return [
            'nome' => '',
            'empresa' => '',
            'whatsapp' => '',
            'email' => '',
            'numero_funcionarios' => '',
            'numero_lideres' => '',
            'faturamento_anual' => '',
            'tomador_decisao' => '',
        ];
    }

    private function valuesFromRecord(array $record): array
    {
        return [
            'nome' => (string)($record['nome'] ?? ''),
            'empresa' => (string)($record['empresa'] ?? ''),
            'whatsapp' => (string)($record['whatsapp'] ?? ''),
            'email' => (string)($record['email'] ?? ''),
            'numero_funcionarios' => (string)($record['numero_funcionarios'] ?? ''),
            'numero_lideres' => (string)($record['numero_lideres'] ?? ''),
            'faturamento_anual' => (string)($record['faturamento_anual'] ?? ''),
            'tomador_decisao' => isset($record['tomador_decisao']) ? (string)(int)$record['tomador_decisao'] : '',
        ];
    }

    private function selectedMapFromRecord(array $record): array
    {
        $resp = json_decode((string)($record['respostas_json'] ?? ''), true) ?: [];
        return [
            'financeiro' => array_map('intval', $resp['financeiro'] ?? []),
            'mercado' => array_map('intval', $resp['mercado'] ?? []),
            'pessoas' => array_map('intval', $resp['pessoas'] ?? []),
            'processo' => array_map('intval', $resp['processo'] ?? []),
        ];
    }

    private function validateStep1(array $values): array
    {
        $errors = [];
        if ($values['nome'] === '') {
            $errors['nome'] = 'Nome é obrigatório.';
        }
        if ($values['empresa'] === '') {
            $errors['empresa'] = 'Empresa é obrigatória.';
        }
        if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        if (!$this->isValidWhatsapp($values['whatsapp'])) {
            $errors['whatsapp'] = 'Informe um WhatsApp válido apenas com números.';
        }
        foreach (['numero_funcionarios', 'numero_lideres', 'faturamento_anual'] as $field) {
            if (!preg_match('/^[1-9]\d*$/', (string)$values[$field])) {
                $errors[$field] = 'Informe um inteiro positivo.';
            }
        }
        if ($values['tomador_decisao'] !== '0' && $values['tomador_decisao'] !== '1') {
            $errors['tomador_decisao'] = 'Selecione sim ou não.';
        }
        return $errors;
    }

    private function renderInvalidToken(): void
    {
        $this->render([
            'rateLimited' => false,
            'invalidToken' => true,
            'expiredToken' => false,
            'alreadyDone' => false,
            'record' => null,
            'step' => 1,
            'values' => $this->defaultValues(),
            'errors' => [],
            'selectedMap' => [],
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => '',
            'formAction' => '',
            'submitted' => false,
        ]);
    }

    private function resolveIdentifierFromRequest(): string
    {
        $identifier = trim((string)($_GET['slug'] ?? $_POST['slug'] ?? $_GET['token'] ?? $_POST['token'] ?? $_POST['identifier'] ?? ''));
        if ($identifier !== '') {
            return strtolower($identifier);
        }
        $requestPath = str_replace('\\', '/', (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'));
        if (preg_match('#/avaliar/([^/]+)/?$#', $requestPath, $m)) {
            return strtolower((string)$m[1]);
        }
        return '';
    }

    private function findPublicRecord(string $identifier): ?array
    {
        if ($identifier === '') {
            return null;
        }
        $record = $this->publicas->findBySlug($identifier);
        if ($record) {
            return $record;
        }
        return $this->publicas->findByToken($identifier);
    }

    private function publicUrlByRecord(array $record): string
    {
        $slug = trim((string)($record['slug'] ?? ''));
        $fallbackToken = trim((string)($record['token'] ?? ''));
        $identifier = $slug !== '' ? $slug : $fallbackToken;
        return $this->publicUrlByIdentifier($identifier);
    }

    private function publicUrlByIdentifier(string $identifier): string
    {
        $configured = trim((string)(getenv('PUBLIC_EVALUATION_BASE_URL') ?: ''));
        if ($configured !== '') {
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
        return $scheme . '://' . $host . $base . '/avaliar/' . rawurlencode($identifier);
    }

    private function createAvaliacaoFromPublicSubmission(array $record, array $values, array $selectedMap, array $totais): int
    {
        $avaliacoes = new AvaliacaoModel();
        return $avaliacoes->create([
            'cliente_id' => null,
            'empresa_nome' => $values['empresa'],
            'nome' => $values['nome'],
            'whatsapp' => $values['whatsapp'],
            'email' => $values['email'],
            'numero_funcionarios' => (int)$values['numero_funcionarios'],
            'numero_lideres' => (int)$values['numero_lideres'],
            'faturamento_medio_anual' => (int)$values['faturamento_anual'],
            'tomador_decisao' => (int)$values['tomador_decisao'],
            'origem_cadastro' => 'potencial_cliente',
            'created_by_user_id' => isset($record['created_by_user_id']) ? (int)$record['created_by_user_id'] : null,
            'contato' => $values['nome'],
            'respostas_json' => json_encode($selectedMap),
            'nota_financeiro' => count($selectedMap['financeiro'] ?? []),
            'nota_mercado' => count($selectedMap['mercado'] ?? []),
            'nota_pessoas' => count($selectedMap['pessoas'] ?? []),
            'nota_processo' => count($selectedMap['processo'] ?? []),
            'realidade_financeiro' => $this->toPercent(count($selectedMap['financeiro'] ?? []), (int)($totais['financeiro'] ?? 1)),
            'realidade_mercado' => $this->toPercent(count($selectedMap['mercado'] ?? []), (int)($totais['mercado'] ?? 1)),
            'realidade_pessoas' => $this->toPercent(count($selectedMap['pessoas'] ?? []), (int)($totais['pessoas'] ?? 1)),
            'realidade_processo' => $this->toPercent(count($selectedMap['processo'] ?? []), (int)($totais['processo'] ?? 1)),
        ]);
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

    private function toPercent(int $nota, int $total): int
    {
        return (int)round(($nota / max(1, $total)) * 100);
    }

    private function allowRequest(string $identifier): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $key = 'avaliacao_publica|' . $ip . '|' . $method . '|' . $identifier;
        $limit = $method === 'POST' ? 20 : 60;
        return $this->rateLimiter->allow($key, $limit, 300);
    }
}
