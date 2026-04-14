<?php
namespace App\Controllers;

use App\Core\AvaliacaoQuestionario;
use App\Core\AuditLogger;
use App\Core\PublicRateLimiter;
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
        $token = $this->resolveTokenFromRequest();
        if (!$this->allowRequest($token)) {
            AuditLogger::log('avaliacao_publica_rate_limited', 'avaliacao_publica', 0, [
                'token' => $token,
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
                'formAction' => $this->publicUrlByToken($token),
            ]);
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'start') {
                $this->start($token);
                return;
            }
            if ($action === 'finish') {
                $this->finish($token);
                return;
            }
        }
        $this->show($token);
    }

    private function show(string $token): void
    {
        if (!$this->isUuid($token)) {
            AuditLogger::log('avaliacao_publica_access_invalid', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        $record = $this->publicas->findByToken($token);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_access_not_found', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        if ($this->isExpired($record)) {
            AuditLogger::log('avaliacao_publica_access_expired', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), ['token' => $token]);
            http_response_code(410);
            $this->renderExpiredToken($record);
            return;
        }
        if (($record['status'] ?? '') === 'concluida') {
            AuditLogger::log('avaliacao_publica_access_completed', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), ['token' => $token]);
            $this->render([
                'rateLimited' => false,
                'invalidToken' => false,
                'expiredToken' => false,
                'alreadyDone' => true,
                'record' => $record,
                'step' => 2,
                'values' => $this->valuesFromRecord($record),
                'errors' => [],
                'selectedMap' => $this->selectedMapFromRecord($record),
                'qs' => AvaliacaoQuestionario::pilares(),
                'publicUrl' => $this->publicUrlByToken($token),
                'formAction' => $this->publicUrlByToken($token),
            ]);
            return;
        }
        AuditLogger::log('avaliacao_publica_access', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $token,
            'status' => $record['status'] ?? null,
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
            'step' => ($record['status'] ?? 'pendente') === 'pendente' ? 1 : 2,
            'values' => $this->valuesFromRecord($record),
            'errors' => [],
            'selectedMap' => $this->selectedMapFromRecord($record),
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => $this->publicUrlByToken($token),
            'formAction' => $this->publicUrlByToken($token),
        ]);
    }

    private function start(string $token): void
    {
        if (!$this->isUuid($token)) {
            AuditLogger::log('avaliacao_publica_start_invalid', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        $record = $this->publicas->findByToken($token);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_start_not_found', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        if ($this->isExpired($record)) {
            AuditLogger::log('avaliacao_publica_start_expired', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), ['token' => $token]);
            http_response_code(410);
            $this->renderExpiredToken($record);
            return;
        }
        if (($record['status'] ?? '') === 'concluida') {
            $this->redirect($this->publicUrlByToken($token));
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
                'token' => $token,
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
                'publicUrl' => $this->publicUrlByToken($token),
                'formAction' => $this->publicUrlByToken($token),
            ]);
            return;
        }
        try {
            $saved = $this->publicas->startByToken($token, [
                'nome' => $values['nome'],
                'empresa' => $values['empresa'],
                'whatsapp' => $values['whatsapp'],
                'email' => $values['email'],
                'numero_funcionarios' => (int)$values['numero_funcionarios'],
                'numero_lideres' => (int)$values['numero_lideres'],
                'faturamento_anual' => (int)$values['faturamento_anual'],
                'tomador_decisao' => (int)$values['tomador_decisao'],
            ]);
        } catch (\Throwable $e) {
            $this->renderSubmissionError(1, $record, $values, [], 'Falha ao salvar os dados iniciais.', $e);
            return;
        }
        if (!$saved) {
            AuditLogger::log('avaliacao_publica_start_noop', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
                'token' => $token,
                'status' => $record['status'] ?? null,
            ]);
            $this->renderSubmissionError(1, $record, $values, [], 'Não foi possível salvar os dados iniciais. Verifique se o link ainda está válido.', null);
            return;
        }
        AuditLogger::log('avaliacao_publica_started', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $token,
            'nome' => $values['nome'],
            'empresa' => $values['empresa'],
        ]);
        $this->redirect($this->publicUrlByToken($token) . '?step=2');
    }

    private function finish(string $token): void
    {
        if (!$this->isUuid($token)) {
            AuditLogger::log('avaliacao_publica_finish_invalid', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        $record = $this->publicas->findByToken($token);
        if (!$record) {
            AuditLogger::log('avaliacao_publica_finish_not_found', 'avaliacao_publica', 0, ['token' => $token]);
            http_response_code(404);
            $this->renderInvalidToken();
            return;
        }
        if ($this->isExpired($record)) {
            AuditLogger::log('avaliacao_publica_finish_expired', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), ['token' => $token]);
            http_response_code(410);
            $this->renderExpiredToken($record);
            return;
        }
        if (($record['status'] ?? '') === 'concluida') {
            $this->redirect($this->publicUrlByToken($token));
            return;
        }
        if (($record['status'] ?? '') !== 'iniciada') {
            $this->redirect($this->publicUrlByToken($token));
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
            $saved = $this->publicas->concludeByToken($token, [
                'respostas_json' => json_encode($selectedMap),
                'nota_financeiro' => $notaFin,
                'nota_mercado' => $notaMer,
                'nota_pessoas' => $notaPes,
                'nota_processo' => $notaPro,
                'realidade_financeiro' => $this->toPercent($notaFin, (int)($totais['financeiro'] ?? 1)),
                'realidade_mercado' => $this->toPercent($notaMer, (int)($totais['mercado'] ?? 1)),
                'realidade_pessoas' => $this->toPercent($notaPes, (int)($totais['pessoas'] ?? 1)),
                'realidade_processo' => $this->toPercent($notaPro, (int)($totais['processo'] ?? 1)),
            ]);
        } catch (\Throwable $e) {
            $this->renderSubmissionError(2, $record, $this->valuesFromRecord($record), $selectedMap, 'Falha ao salvar a avaliação.', $e);
            return;
        }
        if (!$saved) {
            AuditLogger::log('avaliacao_publica_finish_noop', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
                'token' => $token,
                'status' => $record['status'] ?? null,
            ]);
            $this->renderSubmissionError(2, $record, $this->valuesFromRecord($record), $selectedMap, 'Não foi possível salvar a avaliação. Verifique se o link ainda está válido.', null);
            return;
        }
        $updatedRecord = $this->publicas->findByToken($token) ?: $record;
        if ((int)($updatedRecord['avaliacao_id'] ?? 0) <= 0) {
            $avaliacaoId = $this->publicas->materializeStandaloneToAvaliacao($token);
            $updatedRecord['avaliacao_id'] = $avaliacaoId > 0 ? $avaliacaoId : ($updatedRecord['avaliacao_id'] ?? null);
            if ($avaliacaoId <= 0) {
                AuditLogger::log('avaliacao_publica_materialization_failed', 'avaliacao_publica', 0, [
                    'token' => $token,
                    'status' => $updatedRecord['status'] ?? null,
                ]);
            }
        }
        AuditLogger::log('avaliacao_publica_finished', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $token,
            'nota_total' => $notaFin + $notaMer + $notaPes + $notaPro,
        ]);
        $this->redirect($this->publicUrlByToken($token));
    }

    public function validateApi(): void
    {
        $this->applyPublicSecurityHeaders();
        $token = $this->resolveTokenFromRequest();
        if (!$this->allowRequest($token)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Muitas tentativas.', 'error' => 'rate_limited'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $record = $this->isUuid($token) ? $this->publicas->findByToken($token) : null;
        $expired = $record ? $this->isExpired($record) : false;
        $available = $record && !$expired && ($record['status'] ?? '') !== 'concluida';
        AuditLogger::log('avaliacao_publica_validate', 'avaliacao_publica', (int)($record['avaliacao_id'] ?? 0), [
            'token' => $token,
            'available' => $available,
            'status' => $record['status'] ?? null,
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'script_name' => (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        ]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => [
                'token' => $token,
                'valid' => (bool)$record,
                'available' => (bool)$available,
                'status' => $record['status'] ?? 'invalido',
                'permanent' => !empty($record) && empty($record['expiracao']),
                'expired' => $expired,
                'public_url' => $record ? $this->publicUrlByToken($token) : null,
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
            'publicUrl' => $this->publicUrlByToken((string)($record['token'] ?? '')),
            'formAction' => $this->publicUrlByToken((string)($record['token'] ?? '')),
            'formError' => $debug !== '' ? ($message . ' [' . $debug . ']') : $message,
        ]);
    }

    private function render(array $params): void
    {
        extract($params, EXTR_SKIP);
        require __DIR__ . '/../views/avaliacoes/publica.php';
    }

    private function redirect(string $url): void
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
        ]);
    }

    private function renderExpiredToken(array $record): void
    {
        $this->render([
            'rateLimited' => false,
            'invalidToken' => false,
            'expiredToken' => true,
            'alreadyDone' => false,
            'record' => $record,
            'step' => 1,
            'values' => $this->valuesFromRecord($record),
            'errors' => [],
            'selectedMap' => $this->selectedMapFromRecord($record),
            'qs' => AvaliacaoQuestionario::pilares(),
            'publicUrl' => '',
            'formAction' => '',
        ]);
    }

    private function resolveTokenFromRequest(): string
    {
        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        if ($token !== '') {
            return strtolower($token);
        }
        $requestPath = str_replace('\\', '/', (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'));
        if (preg_match('#/public/avaliacao/([^/]+)/?$#', $requestPath, $m)) {
            return strtolower((string)$m[1]);
        }
        return '';
    }

    private function publicUrlByToken(string $token): string
    {
        $configured = trim((string)(getenv('PUBLIC_EVALUATION_BASE_URL') ?: ''));
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . rawurlencode($token);
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/avaliacao/index.php');
        if (PHP_SAPI === 'cli' || strpos($scriptName, '/app/tests/') !== false) {
            $scriptName = '/public/avaliacao/index.php';
        }
        $base = rtrim(dirname($scriptName), '/');
        if (preg_match('#/public/avaliacao/?$#', $base)) {
            $base = preg_replace('#/public/avaliacao/?$#', '', $base) ?: '';
        }
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        if ($base !== '' && strpos($base, '/') !== 0) {
            $base = '/' . ltrim($base, '/');
        }
        $indexBase = $base !== '' ? $base : '';
        if (str_ends_with($indexBase, '/public/avaliacao')) {
            $indexBase = substr($indexBase, 0, -strlen('/public/avaliacao'));
        }
        return $scheme . '://' . $host . $indexBase . '/index.php?route=avaliacao-publica/open&token=' . rawurlencode($token);
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

    private function isExpired(array $record): bool
    {
        $expiracao = (string)($record['expiracao'] ?? '');
        return $expiracao !== '' && strtotime($expiracao) < time();
    }

    private function isUuid(string $token): bool
    {
        return (bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $token);
    }

    private function allowRequest(string $token): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $key = 'avaliacao_publica|' . $ip . '|' . $method . '|' . $token;
        $limit = $method === 'POST' ? 20 : 60;
        return $this->rateLimiter->allow($key, $limit, 300);
    }
}
