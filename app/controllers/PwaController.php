<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Security;
use App\Core\AuditLogger;
use App\Models\UsuarioModel;

class PwaController extends BaseController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function login(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: ' . $this->baseUrl() . '/pwa/dashboard');
            return;
        }
        $this->render('pwa/login', ['pageTitle' => 'Entrar']);
    }

    public function doLogin(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . $this->baseUrl() . '/pwa/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $csrf = $_POST['csrf'] ?? null;

        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            AuditLogger::log('pwa_login_csrf_fail', 'usuario', null, [
                'has_session_csrf' => isset($_SESSION['csrf']),
                'resultado' => 'negado',
            ]);
            $this->render('pwa/login', ['error' => 'Sessão expirada ou CSRF inválido. Tente novamente.']);
            return;
        }

        $user = $this->usuarios->findByEmail($email);
        if ($user && password_verify($senha, $user['senha_hash'])) {
            Auth::login($user);
            Auth::refreshScope();
            if (!Auth::canUsePwa()) {
                AuditLogger::log('auth_login_denied_platform', 'usuario', (int)$user['id'], [
                    'platform' => 'PWA',
                    'platform_access' => Auth::platformAccess(),
                    'tipo_acesso' => $user['tipo_acesso'] ?? null,
                    'resultado' => 'negado',
                ]);
                Auth::logout();
                $this->render('pwa/login', ['error' => 'Você não possui permissão para acessar este recurso.']);
                return;
            }
            AuditLogger::log('auth_login_success', 'usuario', (int)$user['id'], [
                'tipo_acesso' => $user['tipo_acesso'] ?? null,
                'cliente_id' => isset($user['id_cliente']) ? (int)$user['id_cliente'] : null,
                'scope_clientes' => Auth::allowedClientIds(),
                'platform' => 'PWA',
                'resultado' => 'permitido',
            ]);

            $redirect = (string)($_SESSION['redirect_after_login'] ?? '');
            unset($_SESSION['redirect_after_login']);
            if ($redirect !== '' && strpos($redirect, '://') === false && strpos($redirect, $this->baseUrl() . '/pwa') === 0) {
                header('Location: ' . $redirect);
                return;
            }
            header('Location: ' . $this->baseUrl() . '/pwa/dashboard');
            return;
        }

        AuditLogger::log('auth_login_fail', 'usuario', null, ['email' => $email, 'platform' => 'PWA', 'resultado' => 'negado']);
        $this->render('pwa/login', ['error' => 'Credenciais inválidas']);
    }

    public function logout(): void
    {
        $user = Auth::user();
        AuditLogger::log('auth_logout', 'usuario', isset($user['id']) ? (int)$user['id'] : null, [
            'tipo_acesso' => $user['tipo_acesso'] ?? null,
            'cliente_id' => isset($user['id_cliente']) ? (int)$user['id_cliente'] : null,
            'scope_clientes' => Auth::allowedClientIds(),
            'platform' => 'PWA',
            'resultado' => 'permitido',
        ]);
        Auth::logout();
        header('Location: ' . $this->baseUrl() . '/pwa/login');
    }

    public function dashboard(): void
    {
        $this->requirePwaLogin();
        $this->render('pwa/dashboard', ['pageTitle' => 'Dashboard']);
    }

    public function indicadores(): void
    {
        $this->requirePwaLogin();
        $this->proxy('indicadores/index', fn(): void => (new IndicadoresController())->index());
    }

    public function indicadoresHistorico(): void
    {
        $this->requirePwaLogin();
        $this->proxy('indicadores/historico', fn(): void => (new IndicadoresController())->historico());
    }

    public function indicadoresGraficos(): void
    {
        $this->requirePwaLogin();
        $this->proxy('indicadores/charts', fn(): void => (new IndicadoresController())->charts());
    }

    public function agenda(): void
    {
        $this->requirePwaLogin();
        $this->proxy('agenda/index', fn(): void => (new AgendaController())->index());
    }

    public function tarefas(): void
    {
        $this->requirePwaLogin();
        $this->proxy('tarefas/index', fn(): void => (new TarefasController())->index());
    }

    public function cronogramas(): void
    {
        $this->requirePwaLogin();
        $this->proxy('cronograma/index', fn(): void => (new CronogramaController())->index());
    }

    public function planoAcao(): void
    {
        $this->requirePwaLogin();
        $this->proxy('planoacao/index', fn(): void => (new PlanoAcaoController())->index());
    }

    public function auditorias(): void
    {
        $this->requirePwaLogin();
        $this->proxy('auditorias/index', fn(): void => (new AuditoriasController())->index());
    }

    public function avaliacoes(): void
    {
        $this->requirePwaLogin();
        $this->proxy('avaliacoes/index', fn(): void => (new AvaliacoesController())->index());
    }

    public function tratamentos(): void
    {
        $this->requirePwaLogin();
        $this->proxy('processos/index', fn(): void => (new ProcessosController())->index());
    }

    public function balancos(): void
    {
        $this->requirePwaLogin();
        $this->render('pwa/placeholder', ['pageTitle' => 'Balanços', 'title' => 'Balanços']);
    }

    public function oficinas(): void
    {
        $this->requirePwaLogin();
        $this->render('pwa/placeholder', ['pageTitle' => 'Gestão de Oficinas', 'title' => 'Gestão de Oficinas']);
    }

    private function requirePwaLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
            $base = $this->baseUrl();
            if ($uri !== '' && strpos($uri, '://') === false && strpos($uri, $base . '/pwa') === 0) {
                if (!isset($_SESSION['redirect_after_login'])) {
                    $_SESSION['redirect_after_login'] = $uri;
                }
            }
            header('Location: ' . $base . '/pwa/login');
            exit;
        }
        Auth::refreshScope();
        if (!Auth::canUsePwa()) {
            $this->denyAccess('Você não possui permissão para acessar este recurso.', (string)($_GET['route'] ?? ''), null);
        }
    }

    private function proxy(string $targetRoute, callable $run): void
    {
        $original = (string)($_GET['route'] ?? '');
        $_GET['route'] = $targetRoute;
        try {
            $run();
        } finally {
            $_GET['route'] = $original;
        }
    }

    private function baseUrl(): string
    {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') {
            return '';
        }
        return $baseUrl;
    }
}
