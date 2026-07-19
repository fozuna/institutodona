<?php
namespace App\Core;

final class AccessControl
{
    private const PUBLIC_MODULE = 'public';
    private const CADASTROS_MODULE = 'cadastros';
    private const AVALIACOES_MODULE = 'avaliacoes';
    private const OPERACIONAL_MODULE = 'operacional';
    private const CLIENT_ADMIN_MODULE = 'client_admin';
    private const ADMIN_MODULE = 'admin';

    /**
     * Cadastros estruturais do sistema: mesmo estando em modulos que o
     * Cliente Admin acessa (cadastros/client_admin, compartilhados com
     * Colaboradores, Agenda, Cronograma etc.), estas rotas permanecem
     * de uso exclusivo dos perfis administrativos internos.
     */
    private const CLIENT_ADMIN_FORBIDDEN_PREFIXES = [
        'usuarios',
        'consultores',
        'pilares',
        'departamentos',
        'setores',
        'funcoes',
    ];

    private const PUBLIC_ROUTES = [
        'auth/login',
        'auth/dologin',
        'auth/logout',
        'auth/api_token',
        'avaliacao-publica/open',
        'about/index',
    ];

    private const PREFIX_MODULES = [
        'avaliacao-publica' => self::PUBLIC_MODULE,
        'avaliacoes' => self::AVALIACOES_MODULE,
        'departamentos' => self::CADASTROS_MODULE,
        'setores' => self::CADASTROS_MODULE,
        'funcoes' => self::CADASTROS_MODULE,
        'colaboradores' => self::CADASTROS_MODULE,
        'clientes' => self::ADMIN_MODULE,
        'usuarios' => self::CLIENT_ADMIN_MODULE,
        'consultores' => self::ADMIN_MODULE,
        'pilares' => self::ADMIN_MODULE,
        'metodologias' => self::ADMIN_MODULE,
        'aplicacoes' => self::ADMIN_MODULE,
        'dashboard' => self::ADMIN_MODULE,
        'indicadores' => self::OPERACIONAL_MODULE,
        'agenda' => self::CLIENT_ADMIN_MODULE,
        'cronograma' => self::CLIENT_ADMIN_MODULE,
        'planoacao' => self::CLIENT_ADMIN_MODULE,
        'tarefas' => self::CLIENT_ADMIN_MODULE,
        'manuais' => self::CLIENT_ADMIN_MODULE,
        'biblioteca' => self::CLIENT_ADMIN_MODULE,
        'auditorias' => self::OPERACIONAL_MODULE,
        'treinamentos' => self::ADMIN_MODULE,
        'reunioes' => self::CLIENT_ADMIN_MODULE,
        'coaching' => self::OPERACIONAL_MODULE,
        'processos' => self::OPERACIONAL_MODULE,
        'logs' => self::ADMIN_MODULE,
        'auth' => self::PUBLIC_MODULE,
        'about' => self::PUBLIC_MODULE,
    ];

    private const WRITE_ACTIONS = [
        'create',
        'edit',
        'store',
        'update',
        'attach',
        'upload',
        'set_status',
        'finalizar',
        'upsertmetric',
        'addcheck',
        'createaction',
        'updatetask',
        'updateaction',
        'importrun',
        'delete_update',
        'storefilial',
        'updateaplicacao',
        'deleteaplicacao',
        'auditar',
        'gerar-link-cliente',
        'api_public_link_generate',
        'log-link-share',
        'associar-cliente',
        'api_store',
        'api_update',
        'api_auditar',
        'import',
        'updatevalorajax',
        'chartspdf',
        'export_selecionados',
        'rh_sync',
        'duplicate',
        'toggleativo',
        'export_elegiveis',
        'store_agenda',
        'update_agenda',
        'add_colaboradores',
        'add_participantes',
        'save_presence',
        'api_finalize',
        'api_autosave',
    ];

    private const DELETE_ACTIONS = [
        'delete',
        'delete-ajax',
        'deleteajax',
        'api_delete',
        'remove_colaborador',
        'delete_agenda',
        'anexodelete',
    ];

    private const ROLE_CAPABILITIES = [
        'instituto' => [
            'modules' => ['*'],
            'view' => true,
            'write' => true,
            'delete' => true,
        ],
        'consultor' => [
            'modules' => [self::CADASTROS_MODULE, self::AVALIACOES_MODULE, self::PUBLIC_MODULE],
            'view' => true,
            'write' => true,
            'delete' => true,
        ],
        'cliente_admin' => [
            'modules' => [self::CADASTROS_MODULE, self::AVALIACOES_MODULE, self::OPERACIONAL_MODULE, self::CLIENT_ADMIN_MODULE, self::PUBLIC_MODULE],
            'view' => true,
            'write' => true,
            'delete' => true,
        ],
        'cliente' => [
            'modules' => [self::CADASTROS_MODULE, self::AVALIACOES_MODULE, self::OPERACIONAL_MODULE, self::PUBLIC_MODULE],
            'view' => true,
            'write' => true,
            'delete' => false,
        ],
        'reader' => [
            'modules' => [self::CADASTROS_MODULE, self::AVALIACOES_MODULE, self::OPERACIONAL_MODULE, self::PUBLIC_MODULE],
            'view' => true,
            'write' => false,
            'delete' => false,
        ],
        'default' => [
            'modules' => [self::PUBLIC_MODULE],
            'view' => true,
            'write' => false,
            'delete' => false,
        ],
    ];

    public static function currentRole(?array $user = null): string
    {
        $user = $user ?? Auth::user() ?? [];
        return strtolower(trim((string)($user['tipo_acesso'] ?? '')));
    }

    public static function roleLabel(?string $role = null): string
    {
        $role = strtolower(trim((string)($role ?? self::currentRole())));
        return match ($role) {
            'instituto' => 'Instituto',
            'consultor' => 'Consultor',
            'cliente_admin' => 'Cliente Admin',
            'cliente' => 'Cliente Editor',
            'reader' => 'Cliente Leitor',
            default => 'Usuário',
        };
    }

    public static function defaultHomeRoute(?array $user = null): string
    {
        foreach (['dashboard/index', 'avaliacoes/index', 'colaboradores/index', 'departamentos/index', 'about/index'] as $route) {
            if (self::canAccessRoute($route, 'GET', $user)) {
                return $route;
            }
        }
        return 'about/index';
    }

    public static function allowedModulesForUser(?array $user = null): array
    {
        return self::capabilitiesForUser($user)['modules'];
    }

    public static function canAccessModule(string $module, ?array $user = null): bool
    {
        $module = strtolower(trim($module));
        if ($module === '') {
            return false;
        }
        $allowedModules = self::allowedModulesForUser($user);
        return in_array('*', $allowedModules, true) || in_array($module, $allowedModules, true);
    }

    public static function canWriteRoute(string $route, string $method = 'POST', ?array $user = null): bool
    {
        return self::canAccessRoute($route, $method, $user)
            && self::permissionForAction(self::actionForRoute($route, $method), $user) === true
            && self::actionForRoute($route, $method) === 'write';
    }

    public static function canDeleteRoute(string $route, string $method = 'POST', ?array $user = null): bool
    {
        return self::canAccessRoute($route, $method, $user)
            && self::permissionForAction(self::actionForRoute($route, $method), $user) === true
            && self::actionForRoute($route, $method) === 'delete';
    }

    public static function canAccessRoute(string $route, string $method = 'GET', ?array $user = null): bool
    {
        $route = strtolower(trim($route));
        if ($route === '') {
            return true;
        }
        if (in_array($route, self::PUBLIC_ROUTES, true)) {
            return true;
        }
        $role = self::currentRole($user);
        if ($role === 'instituto') {
            return true;
        }
        if ($role === 'cliente_admin' && in_array(self::routePrefix($route), self::CLIENT_ADMIN_FORBIDDEN_PREFIXES, true)) {
            return false;
        }

        $module = self::moduleForRoute($route);
        if ($module === self::PUBLIC_MODULE) {
            return true;
        }
        if (!self::canAccessModule($module, $user)) {
            return false;
        }
        return self::permissionForAction(self::actionForRoute($route, $method), $user);
    }

    private static function routePrefix(string $route): string
    {
        $parts = explode('/', strtolower(trim($route)));
        $prefix = $parts[0] ?? '';
        if ($prefix === 'pwa') {
            return $parts[1] ?? '';
        }
        return $prefix;
    }

    public static function moduleForRoute(string $route): string
    {
        $route = strtolower(trim($route));
        if ($route === '') {
            return self::PUBLIC_MODULE;
        }
        if (in_array($route, self::PUBLIC_ROUTES, true)) {
            return self::PUBLIC_MODULE;
        }
        $parts = explode('/', $route);
        $prefix = $parts[0] ?? '';
        if ($prefix === 'pwa') {
            $sub = $parts[1] ?? '';
            if ($sub === '' || $sub === 'dashboard') {
                return self::PUBLIC_MODULE;
            }
            return self::PREFIX_MODULES[$sub] ?? $sub;
        }
        return self::PREFIX_MODULES[$prefix] ?? $prefix;
    }

    public static function actionForRoute(string $route, string $method = 'GET'): string
    {
        $route = strtolower(trim($route));
        $parts = explode('/', $route);
        $action = ($parts[0] ?? '') === 'pwa' ? ($parts[2] ?? 'index') : ($parts[1] ?? 'index');
        $method = strtoupper(trim($method));
        if (in_array($action, self::DELETE_ACTIONS, true)) {
            return 'delete';
        }
        if (in_array($action, self::WRITE_ACTIONS, true)) {
            return 'write';
        }
        if ($method !== 'GET' && $method !== 'HEAD') {
            return 'write';
        }
        return 'view';
    }

    public static function denyMessage(string $route, ?int $clienteId = null, ?array $user = null): string
    {
        if ($clienteId !== null && $clienteId > 0 && !Auth::canAccessCliente($clienteId)) {
            return 'Este recurso não pertence à sua empresa.';
        }
        $module = self::moduleForRoute($route);
        if ($module === self::CLIENT_ADMIN_MODULE && !self::canAccessModule($module, $user)) {
            return self::clientAdminOnlyMessage();
        }
        if (!self::canAccessModule($module, $user)) {
            return 'Você não possui permissão para acessar este recurso.';
        }
        $action = self::actionForRoute($route);
        if (!self::permissionForAction($action, $user)) {
            return 'Seu perfil não permite executar esta ação.';
        }
        if ($action === 'delete') {
            return 'Seu perfil não permite executar esta ação.';
        }
        if ($action === 'write') {
            return $module === self::CLIENT_ADMIN_MODULE
                ? self::clientAdminOnlyMessage()
                : 'Você não possui permissão para acessar este recurso.';
        }
        return 'Acesso negado.';
    }

    public static function clientAdminOnlyMessage(): string
    {
        return 'Acesso restrito ao perfil Cliente Admin da empresa.';
    }

    public static function frontendMatrix(?array $user = null): array
    {
        $user = $user ?? Auth::user() ?? [];
        $capabilities = self::capabilitiesForUser($user);
        return [
            'role' => self::currentRole($user),
            'roleLabel' => self::roleLabel(self::currentRole($user)),
            'unrestricted' => self::currentRole($user) === 'instituto',
            'allowedModules' => $capabilities['modules'],
            'canView' => $capabilities['view'],
            'canWrite' => $capabilities['write'],
            'canDelete' => $capabilities['delete'],
            'writeActions' => array_values(self::WRITE_ACTIONS),
            'deleteActions' => array_values(self::DELETE_ACTIONS),
            'prefixModules' => self::PREFIX_MODULES,
            'publicRoutes' => array_values(self::PUBLIC_ROUTES),
        ];
    }

    private static function capabilitiesForUser(?array $user = null): array
    {
        $role = self::currentRole($user);
        return self::ROLE_CAPABILITIES[$role] ?? self::ROLE_CAPABILITIES['default'];
    }

    private static function permissionForAction(string $action, ?array $user = null): bool
    {
        $capabilities = self::capabilitiesForUser($user);
        return match ($action) {
            'delete' => (bool)$capabilities['delete'],
            'write' => (bool)$capabilities['write'],
            default => (bool)$capabilities['view'],
        };
    }
}
