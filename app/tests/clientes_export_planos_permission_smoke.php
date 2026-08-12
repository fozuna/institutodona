<?php
require __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Core\Auth;

/**
 * Este teste testava exportPlanos() chamando o controller diretamente sem
 * nunca popular $_GET['route'] - authorizeRoute() trata route='' como "sem
 * checagem" (BaseController.php: `if ($route === '' || Auth::isInstituto())
 * return;`), então o bloqueio real de RBAC de rota (clientes/* = ADMIN_MODULE,
 * só Instituto) nunca era exercitado. Isso mascarava o bug do item 08: em
 * produção, Cliente Admin recebia 404 oculto ANTES de chegar em
 * Auth::canExportPlanosAcao(), mas o teste "passava" porque pulava esse gate.
 *
 * A correção do item 08 moveu a exportação para planoacao/export (módulo
 * CLIENT_ADMIN_MODULE, acessível a Cliente Admin) e manteve clientes/exportPlanos
 * apenas como redirecionamento de compatibilidade, ainda preso a ADMIN_MODULE
 * de propósito (a correção não deveria liberar ADMIN_MODULE para Cliente
 * Admin). Este teste agora verifica o caminho real de autorização
 * (AccessControl::canAccessRoute(), o mesmo usado por authorizeRoute()) para
 * as duas rotas, com $_GET['route'] populado corretamente.
 */

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function userFor(string $role, array $allowedClientIds): array
{
    return [
        'id' => 10,
        'nome' => ucfirst($role),
        'email' => strtolower($role) . '@example.com',
        'tipo_acesso' => $role,
        'allowed_client_ids' => $allowedClientIds,
    ];
}

$clienteAdmin = userFor('cliente_admin', [5]);
$cliente = userFor('cliente', [5]);
$consultor = userFor('consultor', [5]);
$instituto = userFor('instituto', []);

$results = [
    // Regra de negócio (Auth::canExportPlanosAcao()) já liberava cliente/cliente_admin -
    // isso nunca foi o bug; o bug era o RBAC de rota impedir alcançá-la.
    'cliente_admin_business_rule_allows' => (static function () use ($clienteAdmin): bool {
        $_SESSION['user'] = $clienteAdmin;
        return Auth::canExportPlanosAcao();
    })(),
    'cliente_business_rule_allows' => (static function () use ($cliente): bool {
        $_SESSION['user'] = $cliente;
        return Auth::canExportPlanosAcao();
    })(),
    'consultor_business_rule_denies' => (static function () use ($consultor): bool {
        $_SESSION['user'] = $consultor;
        return !Auth::canExportPlanosAcao();
    })(),

    // Caminho real de autorização de rota (o mesmo que authorizeRoute() usa) -
    // é aqui que o bug do item 08 vivia.
    'cliente_admin_blocked_on_old_route' => !AccessControl::canAccessRoute('clientes/exportPlanos', 'GET', $clienteAdmin),
    'cliente_admin_allowed_on_new_route' => AccessControl::canAccessRoute('planoacao/export', 'GET', $clienteAdmin),
    'cliente_blocked_on_old_route' => !AccessControl::canAccessRoute('clientes/exportPlanos', 'GET', $cliente),
    // O papel "cliente" (não-admin) nunca teve acesso a nenhuma rota do módulo
    // planoacao/* (pré-existente, módulo = CLIENT_ADMIN_MODULE) - antes desta
    // correção ele também já não alcançava a rota antiga (ADMIN_MODULE). A regra
    // de negócio (canExportPlanosAcao()) permite, mas não há hoje nenhuma tela
    // alcançável por esse papel para de fato disparar a exportação; a correção
    // do item 08 não amplia nem reduz esse comportamento pré-existente.
    'cliente_blocked_on_new_route_same_as_before' => !AccessControl::canAccessRoute('planoacao/export', 'GET', $cliente),
    'consultor_blocked_on_old_route' => !AccessControl::canAccessRoute('clientes/exportPlanos', 'GET', $consultor),
    'consultor_blocked_on_new_route' => !AccessControl::canAccessRoute('planoacao/export', 'GET', $consultor),
    'instituto_allowed_on_old_route' => AccessControl::canAccessRoute('clientes/exportPlanos', 'GET', $instituto),
    'instituto_allowed_on_new_route' => AccessControl::canAccessRoute('planoacao/export', 'GET', $instituto),

    // ADMIN_MODULE continua restrito: Cliente Admin não ganhou acesso a outras
    // rotas administrativas do prefixo clientes/* como efeito colateral.
    'cliente_admin_still_blocked_from_clientes_index' => !AccessControl::canAccessRoute('clientes/index', 'GET', $clienteAdmin),
    'cliente_admin_still_blocked_from_clientes_create' => !AccessControl::canAccessRoute('clientes/create', 'GET', $clienteAdmin),
    'cliente_admin_still_blocked_from_clientes_delete' => !AccessControl::canAccessRoute('clientes/delete', 'POST', $clienteAdmin),
];

echo json_encode($results, JSON_UNESCAPED_UNICODE) . "\n";

foreach ($results as $label => $passed) {
    if ($passed) {
        ok($label);
    } else {
        failFast($label);
    }
}

echo "Clientes export planos permission smoke (fixed) passed.\n";
