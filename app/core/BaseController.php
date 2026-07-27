<?php
namespace App\Core;

class BaseController
{
    private const WRITE_ACTIONS = [
        'store', 'update', 'delete', 'attach', 'upload', 'set_status', 'finalizar',
        'upsertmetric', 'addcheck', 'createaction', 'updatetask',
        'updateaction', 'importrun', 'delete_update', 'storefilial',
        'updateaplicacao', 'deleteaplicacao', 'auditar', 'gerar-link-cliente', 'api_public_link_generate', 'log-link-share', 'associar-cliente',
        'api_store', 'api_update', 'api_delete', 'api_auditar',
        'delete-ajax',
        'import',
        'updatevalorajax',
        'deleteajax',
        'chartspdf',
        'export_selecionados',
        'rh_sync',
        'duplicate',
        'toggleativo',
    ];

    protected function render(string $view, array $params = []): void
    {
        $params['pageTitle'] = $params['pageTitle'] ?? $this->autoTitle($view);
        extract($params, EXTR_SKIP);
        ob_start();
        $defaultViewFile = __DIR__ . '/../views/' . $view . '.php';
        $pwaViewFile = __DIR__ . '/../views/pwa/' . $view . '.php';
        if (is_file($pwaViewFile) && $this->isPwaRequest()) {
            require $pwaViewFile;
        } else {
            require $defaultViewFile;
        }
        $content = ob_get_clean();
        if ($this->isPwaRequest() && is_file(__DIR__ . '/../views/layouts/pwa.php')) {
            require __DIR__ . '/../views/layouts/pwa.php';
            return;
        }
        require __DIR__ . '/../views/layouts/main.php';
    }

    protected function renderPartial(string $view, array $params = []): void
    {
        $params['pageTitle'] = $params['pageTitle'] ?? $this->autoTitle($view);
        extract($params, EXTR_SKIP);
        $defaultViewFile = __DIR__ . '/../views/' . $view . '.php';
        $pwaViewFile = __DIR__ . '/../views/pwa/' . $view . '.php';
        if (is_file($pwaViewFile) && $this->isPwaRequest()) {
            require $pwaViewFile;
            return;
        }
        require $defaultViewFile;
    }

    protected function isPwaRequest(): bool
    {
        $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        if ($path !== '' && strpos($path, '/pwa') === 0) {
            return true;
        }
        $route = (string)($_GET['route'] ?? '');
        return strpos($route, 'pwa/') === 0;
    }

    protected function isAjaxRequest(): bool
    {
        if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            return true;
        }
        return isset($_GET['ajax']) && (string)$_GET['ajax'] === '1';
    }

    /**
     * Rotas que nunca devem ser guardadas como "última página válida" para o
     * botão de retorno da 404: login/logout, downloads/exports/PDFs e
     * endpoints de API/AJAX não representam uma página HTML navegável.
     */
    private const LAST_ROUTE_EXCLUDED_KEYWORDS = [
        'ajax', 'api_', 'download', 'export', 'pdf', 'logout', 'dologin',
    ];

    private function trackLastValidRoute(string $route): void
    {
        if ($route === '') {
            return;
        }
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET') {
            return;
        }
        if ($this->isAjaxRequest() || $this->isPwaRequest()) {
            return;
        }
        if ($route === 'auth/login') {
            return;
        }
        $routeLower = strtolower($route);
        foreach (self::LAST_ROUTE_EXCLUDED_KEYWORDS as $keyword) {
            if (strpos($routeLower, $keyword) !== false) {
                return;
            }
        }
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($uri === '' || strpos($uri, '://') !== false) {
            return;
        }
        $_SESSION['last_valid_route'] = $uri;
    }

    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
            if ($uri !== '' && strpos($uri, '://') === false) {
                if (!isset($_SESSION['redirect_after_login'])) {
                    $_SESSION['redirect_after_login'] = $uri;
                }
            }
            header('Location: index.php?route=auth/login');
            exit;
        }
        Auth::refreshScope();
        $route = (string)($_GET['route'] ?? '');
        $this->authorizeRoute($route);
        $this->trackLastValidRoute($route);
        $routeForAudit = $route ?: null;
        $clienteCandidate = null;
        if (isset($_GET['cliente'])) {
            $clienteCandidate = (int)$_GET['cliente'];
        } elseif (isset($_GET['id']) && strpos((string)$route, 'clientes/') === 0) {
            $clienteCandidate = (int)$_GET['id'];
        } elseif (isset($_POST['cliente_id'])) {
            $clienteCandidate = (int)$_POST['cliente_id'];
        } elseif (!empty($_POST['id_clientes']) && is_array($_POST['id_clientes'])) {
            $clienteCandidate = (int)($_POST['id_clientes'][0] ?? 0);
        }
        $clienteScoped = $this->resolveScopedClienteId($clienteCandidate);
        AuditLogger::log('menu_access', 'route', null, [
            'route' => $routeForAudit,
            'cliente_id' => $clienteScoped,
            'scoped_clientes' => Auth::allowedClientIds(),
            'resultado' => 'permitido',
            'platform' => $this->isPwaRequest() ? 'PWA' : 'WEB',
        ]);
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            $this->denyAccess('Você não possui permissão para acessar este recurso.', (string)($_GET['route'] ?? ''), $this->routeClienteCandidate(), $this->isAjaxRequest());
        }
    }

    protected function requireClienteAdminAccess(bool $json = false): void
    {
        $this->requireLogin();
        $actual = (string)($_SESSION['user']['tipo_acesso'] ?? '');
        if ($actual === 'instituto' || $actual === 'cliente_admin') {
            return;
        }
        $this->denyAccess(
            AccessControl::clientAdminOnlyMessage(),
            (string)($_GET['route'] ?? ''),
            $this->routeClienteCandidate(),
            $json || $this->isAjaxRequest()
        );
    }

    private function hasRole(string $role): bool
    {
        $actual = (string)($_SESSION['user']['tipo_acesso'] ?? '');
        if ($actual === $role) {
            return true;
        }
        if ($role === 'cliente_admin') {
            return $actual === 'cliente_admin';
        }
        if ($role === 'instituto') {
            return Auth::isInstituto();
        }
        return false;
    }

    protected function authorizeRoute(?string $route = null, bool $json = false): void
    {
        $route = (string)($route ?? ($_GET['route'] ?? ''));
        if ($route === '' || Auth::isInstituto()) {
            return;
        }
        $json = $json || $this->isAjaxRequest();
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $clienteCandidate = $this->routeClienteCandidate();
        if (!AccessControl::canAccessRoute($route, $method)) {
            $this->denyAccess(AccessControl::denyMessage($route, $clienteCandidate), $route, $clienteCandidate, $json);
        }
        if ($clienteCandidate !== null && $clienteCandidate > 0 && !Auth::canAccessCliente($clienteCandidate)) {
            $this->denyAccess(AccessControl::denyMessage($route, $clienteCandidate), $route, $clienteCandidate, $json);
        }
    }

    protected function scopedClienteIds(): array
    {
        return Auth::allowedClientIds();
    }

    protected function canAccessCliente(int $clienteId): bool
    {
        return Auth::canAccessCliente($clienteId);
    }

    protected function resolveScopedClienteId(?int $requestedId): ?int
    {
        if (Auth::isInstituto()) {
            return $requestedId;
        }
        $ids = $this->scopedClienteIds();
        if (empty($ids)) {
            return null;
        }
        if ($requestedId !== null && $requestedId > 0 && in_array($requestedId, $ids, true)) {
            return $requestedId;
        }
        return (int)$ids[0];
    }

    /**
     * Nega acesso (RBAC/tenant) SEM revelar ao usuário que o recurso existe:
     * responde com a mesma página/contrato de "não encontrado" (404). O motivo
     * real ($message) é registrado apenas no log interno de auditoria, nunca
     * exibido - conforme a regra de negócio de ocultação de recursos.
     */
    protected function denyAccess(string $message = 'Acesso negado.', ?string $route = null, ?int $clienteId = null, bool $json = false): void
    {
        $errorId = bin2hex(random_bytes(5));
        AuditLogger::log('access_denied', 'auth', null, [
            'route' => $route ?? ($_GET['route'] ?? null),
            'cliente_id' => $clienteId,
            'scoped_clientes' => Auth::allowedClientIds(),
            'perfil' => AccessControl::roleLabel((string)($_SESSION['user']['tipo_acesso'] ?? '')),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'resultado' => 'negado',
            'mensagem' => $message,
            'hidden_as' => '404',
            'error_id' => $errorId,
            'platform' => $this->isPwaRequest() ? 'PWA' : 'WEB',
        ]);
        $this->respondNotFound($json);
    }

    /**
     * Responde "não encontrado" para um recurso que genuinamente não existe
     * (Cenário 1: rota/registro inexistente) - mesmo contrato de denyAccess(),
     * sem gerar log de negação de acesso (não é uma tentativa de acesso indevido).
     */
    protected function renderNotFound(bool $json = false): void
    {
        $this->respondNotFound($json);
    }

    private function respondNotFound(bool $json): void
    {
        http_response_code(404);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Recurso não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->render('errors/404', [
            'pageTitle' => 'Conteúdo não encontrado',
            'backUrl' => $this->resolveSafeBackUrl(),
        ]);
        exit;
    }

    /**
     * Resolve a URL segura para o botão "Voltar" da página 404, seguindo a
     * ordem: (1) última rota interna válida registrada em sessão, (2) Referer
     * interno e seguro, (3) Dashboard (autenticado) ou Login (anônimo).
     * Nunca confia em URLs absolutas/externas - previne open redirect.
     */
    protected function resolveSafeBackUrl(): string
    {
        $user = $_SESSION['user'] ?? null;
        $fallback = $user ? 'index.php?route=dashboard/index' : 'index.php?route=auth/login';
        $currentUri = (string)($_SERVER['REQUEST_URI'] ?? '');

        $candidates = [];
        $lastValid = $_SESSION['last_valid_route'] ?? '';
        if (is_string($lastValid) && $lastValid !== '') {
            $candidates[] = $lastValid;
        }
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $safeReferer = $this->extractSafeInternalUri($referer);
            if ($safeReferer !== null) {
                $candidates[] = $safeReferer;
            }
        }

        foreach ($candidates as $uri) {
            if ($this->isUsableReturnUri($uri, $currentUri, $user)) {
                return $uri;
            }
        }
        return $fallback;
    }

    /**
     * Extrai um caminho relativo seguro de uma URL de Referer, somente se ela
     * apontar para o mesmo host desta requisição. Rejeita qualquer URL externa,
     * "//", "javascript:", "data:" ou outro esquema/host diferente.
     */
    private function extractSafeInternalUri(string $referer): ?string
    {
        if ($referer === '' || strpos($referer, '//') === 0) {
            return null;
        }
        $scheme = strtolower((string)(parse_url($referer, PHP_URL_SCHEME) ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
            return null;
        }
        $refererHost = (string)(parse_url($referer, PHP_URL_HOST) ?? '');
        $currentHost = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($refererHost === '' || $currentHost === '' || strcasecmp($refererHost, $currentHost) !== 0) {
            return null;
        }
        $path = (string)(parse_url($referer, PHP_URL_PATH) ?? '');
        $query = parse_url($referer, PHP_URL_QUERY);
        if ($path === '') {
            return null;
        }
        return $path . ($query ? '?' . $query : '');
    }

    /**
     * Valida se uma URI candidata pode ser oferecida como retorno: precisa ser
     * diferente da URL atual, apontar para uma rota reconhecida, não ser uma
     * página de erro/login/logout, e o usuário atual precisa continuar
     * autorizado a acessá-la agora (revalidação de RBAC/módulo).
     */
    private function isUsableReturnUri(string $uri, string $currentUri, ?array $user): bool
    {
        if ($uri === '' || strpos($uri, '://') !== false || strpos($uri, '//') === 0) {
            return false;
        }
        $lower = strtolower($uri);
        if (strpos($lower, 'javascript:') === 0 || strpos($lower, 'data:') === 0) {
            return false;
        }
        if ($currentUri !== '' && $uri === $currentUri) {
            return false;
        }
        $query = parse_url($uri, PHP_URL_QUERY);
        if (!$query) {
            return false;
        }
        parse_str($query, $params);
        $route = strtolower((string)($params['route'] ?? ''));
        if ($route === '' || in_array($route, ['auth/login', 'auth/logout', 'auth/dologin'], true)) {
            return false;
        }
        if (strpos($route, 'ajax') !== false || strpos($route, 'api_') !== false) {
            return false;
        }
        if (!AccessControl::canAccessRoute($route, 'GET', $user)) {
            return false;
        }
        return true;
    }

    private function routeClienteCandidate(): ?int
    {
        foreach (['cliente', 'cliente_id', 'id_cliente', 'empresa_id'] as $key) {
            if (isset($_GET[$key]) && (int)$_GET[$key] > 0) {
                return (int)$_GET[$key];
            }
            if (isset($_POST[$key]) && (int)$_POST[$key] > 0) {
                return (int)$_POST[$key];
            }
        }
        if (!empty($_POST['id_clientes']) && is_array($_POST['id_clientes'])) {
            $first = (int)($_POST['id_clientes'][0] ?? 0);
            return $first > 0 ? $first : null;
        }
        return null;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function autoTitle(string $view): string
    {
        $parts = explode('/', $view);
        $mod = $parts[0] ?? '';
        $act = $parts[1] ?? 'index';
        $sing = [
            'clientes' => 'Cliente',
            'pilares' => 'Pilar',
            'metodologias' => 'Metodologia',
            'agenda' => 'Agenda',
            'consultores' => 'Consultor',
            'avaliacao' => 'Avaliação',
            'dashboard' => 'Dashboard',
            'auth' => 'Entrar',
        ];
        $plur = [
            'clientes' => 'Clientes',
            'pilares' => 'Pilares',
            'metodologias' => 'Metodologias',
            'departamentos' => 'Departamentos',
            'agenda' => 'Agenda',
            'consultores' => 'Consultores',
            'avaliacao' => 'Avaliação',
            'dashboard' => 'Dashboard',
            'auth' => 'Entrar',
        ];
        $s = $sing[$mod] ?? ucfirst($mod);
        $p = $plur[$mod] ?? ucfirst($mod);
        if ($act === 'index') return $p;
        if ($act === 'create') return 'Novo ' . $s;
        if ($act === 'edit') return 'Editar ' . $s;
        if ($act === 'show') return $s;
        return $p;
    }
}
