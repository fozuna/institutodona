<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AccessControl;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function runManageProbe(string $role, int $clienteId = 1): string
{
    $probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'treinamentos_manage_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg($role) . ' ' . escapeshellarg((string)$clienteId);
    $out = [];
    @exec($cmd . ' 2>&1', $out);
    return trim(implode("\n", $out));
}

// 1) AccessControl: Cliente Admin passa a ter acesso ao módulo de Treinamentos.
$userClienteAdmin = ['tipo_acesso' => 'cliente_admin', 'id_cliente' => 1, 'allowed_client_ids' => [1]];
if (!AccessControl::canAccessRoute('treinamentos/index', 'GET', $userClienteAdmin)) {
    failFast('Cliente Admin deveria conseguir acessar treinamentos/index');
}
ok('AccessControl libera treinamentos/index para o perfil Cliente Admin');

if (!AccessControl::canAccessRoute('treinamentos/create', 'GET', $userClienteAdmin)) {
    failFast('Cliente Admin deveria conseguir acessar treinamentos/create');
}
ok('AccessControl libera treinamentos/create para o perfil Cliente Admin');

// 2) AccessControl: perfis sem o módulo client_admin continuam sem acesso (comportamento já existente, não deve mudar).
$userConsultor = ['tipo_acesso' => 'consultor', 'allowed_client_ids' => []];
if (AccessControl::canAccessRoute('treinamentos/index', 'GET', $userConsultor)) {
    failFast('Consultor não deveria ter acesso a treinamentos (mesmo padrão de agenda/cronograma/planoacao)');
}
ok('Consultor continua sem acesso a Treinamentos, mesmo padrão de Agenda/Cronograma/Plano de Ação');

// 3) Estruturas globais continuam fora do alcance do Cliente Admin (não deve ter sido afetado por esta correção).
foreach (['usuarios/index', 'pilares/index', 'departamentos/index', 'setores/index', 'funcoes/index', 'consultores/index'] as $forbiddenRoute) {
    if (AccessControl::canAccessRoute($forbiddenRoute, 'GET', $userClienteAdmin)) {
        failFast("Cliente Admin não deveria acessar {$forbiddenRoute} (cadastro estrutural do sistema)");
    }
}
ok('Cadastros estruturais do sistema continuam bloqueados para Cliente Admin (usuarios/pilares/departamentos/setores/funcoes/consultores)');

// 4) Controller: TreinamentosController::requireManagePermission() permite Cliente Admin e Instituto, bloqueia perfil "cliente" comum.
$outClienteAdmin = runManageProbe('cliente_admin');
if (stripos($outClienteAdmin, 'Acesso restrito') !== false || stripos($outClienteAdmin, '<!DOCTYPE') === false) {
    failFast('Cliente Admin deveria conseguir acessar o formulário de criação de treinamento (requireManagePermission)');
}
ok('TreinamentosController permite Cliente Admin criar/movimentar treinamentos (requireManagePermission)');

$outInstituto = runManageProbe('instituto');
if (stripos($outInstituto, 'Acesso restrito') !== false || stripos($outInstituto, '<!DOCTYPE') === false) {
    failFast('Instituto deveria continuar conseguindo acessar o formulário de criação de treinamento');
}
ok('Instituto continua com acesso normal (sem regressão)');

$outCliente = runManageProbe('cliente');
if (stripos($outCliente, 'Acesso restrito ao perfil Cliente Admin') === false) {
    failFast('Perfil "cliente" comum deveria continuar bloqueado de criar/movimentar treinamentos');
}
ok('Perfil "cliente" comum (não admin) continua bloqueado de movimentar treinamentos, como esperado');

echo "Treinamentos cliente admin access regression tests passed.\n";
