<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AuditoriasController;
use App\Controllers\DepartamentosController;
use App\Controllers\FuncoesController;
use App\Controllers\SetoresController;
use App\Controllers\UsuariosController;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\UsuarioModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$cleanup = ['cliente' => 0, 'usuario' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        if (!empty($cleanup['usuario'])) {
            $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(['id' => $cleanup['usuario']]);
        }
        if (!empty($cleanup['cliente'])) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente']]);
        }
    } catch (\Throwable $e) {
    }
});

// Sessão instituto apenas para criar os registros de teste (bypass de escopo).
$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$clientes = new ClienteModel();
$clienteId = $clientes->create(['nome_empresa' => 'Cliente RBAC ' . $suffix, 'CNPJ' => '99.888.777/0001-' . substr($suffix, 0, 2), 'contato' => 'x']);
if ($clienteId <= 0) failFast('Falha ao criar cliente de teste');
$cleanup['cliente'] = $clienteId;

$usuarios = new UsuarioModel();
$userId = $usuarios->create([
    'nome' => 'Cliente Admin RBAC ' . $suffix,
    'email' => 'cliente.admin.rbac.' . $suffix . '@example.com',
    'senha_hash' => password_hash('teste12345', PASSWORD_DEFAULT),
    'tipo_acesso' => 'cliente_admin',
    'id_cliente' => $clienteId,
]);
if ($userId <= 0) failFast('Falha ao criar usuário Cliente Admin de teste');
$cleanup['usuario'] = $userId;
ok('Criou cliente e usuário Cliente Admin reais para o teste (refreshScope precisa de linha real em usuarios)');

// A partir daqui, a sessão passa a ser a do Cliente Admin real recém-criado.
// requireLogin() -> Auth::refreshScope() vai reidratar tipo_acesso/id_cliente a partir do banco.
$_SESSION['user'] = ['id' => $userId, 'nome' => 'Cliente Admin RBAC', 'email' => 'x', 'tipo_acesso' => 'cliente_admin', 'allowed_client_ids' => [$clienteId]];
$_SERVER['REQUEST_METHOD'] = 'GET';

trait DenyAccessProbe
{
    public bool $wasDenied = false;

    protected function denyAccess(string $message = 'Acesso negado.', ?string $route = null, ?int $clienteId = null, bool $json = false): void
    {
        $this->wasDenied = true;
    }
}

class UsuariosControllerProbe extends UsuariosController { use DenyAccessProbe; }
class DepartamentosControllerProbe extends DepartamentosController { use DenyAccessProbe; }
class SetoresControllerProbe extends SetoresController { use DenyAccessProbe; }
class FuncoesControllerProbe extends FuncoesController { use DenyAccessProbe; }

function assertBlocked(string $route, object $probe, callable $call, string $label): void
{
    $_GET['route'] = $route;
    ob_start();
    try {
        $call();
    } catch (\Throwable $e) {
        ob_end_clean();
        failFast("$label lançou exceção inesperada em vez de bloquear: " . get_class($e) . ': ' . $e->getMessage());
    }
    ob_end_clean();
    if (!$probe->wasDenied) {
        failFast("$label deveria ter sido bloqueado para Cliente Admin, mas denyAccess() não foi acionado");
    }
}

$usuariosProbe = new UsuariosControllerProbe();
assertBlocked('usuarios/index', $usuariosProbe, fn() => $usuariosProbe->index(), 'GET usuarios/index');
$departamentosProbe = new DepartamentosControllerProbe();
assertBlocked('departamentos/index', $departamentosProbe, fn() => $departamentosProbe->index(), 'GET departamentos/index');
$setoresProbe = new SetoresControllerProbe();
assertBlocked('setores/index', $setoresProbe, fn() => $setoresProbe->index(), 'GET setores/index');
$funcoesProbe = new FuncoesControllerProbe();
assertBlocked('funcoes/index', $funcoesProbe, fn() => $funcoesProbe->index(), 'GET funcoes/index');
ok('Cliente Admin real é bloqueado end-to-end (via controller real) em usuarios/departamentos/setores/funcoes');

// Módulo operacional (auditorias) deve continuar acessível normalmente.
$_GET['route'] = 'auditorias/index';
$_SESSION['flash_error'] = null;
ob_start();
try {
    (new AuditoriasController())->index();
    $html = (string)ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Cliente Admin não deveria ser bloqueado em auditorias/index: ' . get_class($e) . ': ' . $e->getMessage());
}
if (http_response_code() === 403) failFast('Cliente Admin recebeu 403 em auditorias/index (módulo operacional deveria continuar liberado)');
if (!str_contains($html, 'Auditorias')) failFast('auditorias/index não renderizou normalmente para Cliente Admin');
ok('Cliente Admin continua com acesso normal a auditorias/index (módulo operacional não foi afetado)');

$_SESSION['user'] = null;
$_POST = [];
ob_end_flush();
echo "TODOS OS TESTES DE BLOQUEIO RBAC PASSARAM\n";
