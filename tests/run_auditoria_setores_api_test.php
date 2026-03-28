<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\AuditoriasController;
use App\Models\SetorModel;
use App\Models\UsuarioModel;

ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '0');
ini_set('session.save_path', sys_get_temp_dir());

class MockSetorModel extends SetorModel
{
    public array $calls = [];
    public array $data = [];
    public bool $throw = false;

    public function __construct()
    {
    }

    public function allByCliente(int $clienteId): array
    {
        $this->calls[] = $clienteId;
        if ($this->throw) {
            throw new \Exception('DB fail');
        }
        return $this->data[$clienteId] ?? [];
    }
}

class TestableAuditoriasController extends AuditoriasController
{
}

class MockUsuarioModel extends UsuarioModel
{
    public function __construct()
    {
    }

    public function find(int $id): ?array
    {
        return null;
    }
}

function setPrivateProp(object $obj, string $prop, $value): void
{
    $ref = new ReflectionClass($obj);
    while ($ref && !$ref->hasProperty($prop)) {
        $ref = $ref->getParentClass();
    }
    $p = $ref->getProperty($prop);
    $p->setAccessible(true);
    $p->setValue($obj, $value);
}

function assertTest($condition, $message) {
    static $passed = 0, $total = 0;
    $total++;
    if ($condition) {
        echo "[PASS] $message\n";
        $passed++;
    } else {
        echo "[FAIL] $message\n";
    }
    return [$passed, $total];
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$passed = 0;
$total = 0;
echo "Running Auditoria API Setores Tests...\n";
echo "=====================================\n";

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Tester',
    'email' => 'tester@example.com',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
    'allowed_client_ids' => [],
    'selected_client_ids' => [],
    'unrestricted_access' => true,
];

$_SESSION['cache_auditoria_setores'] = [];
$_SESSION['cache_auditoria_setores_ts'] = [];

$mock = new MockSetorModel();
$mock->data = [
    1 => [['id' => 10, 'nome' => 'FINANCEIRO', 'departamento_id' => 1]],
];

$ref = new ReflectionClass(TestableAuditoriasController::class);
$controller = $ref->newInstanceWithoutConstructor();
setPrivateProp($controller, 'setores', $mock);
setPrivateProp($controller, 'usuarios', new MockUsuarioModel());

echo "\nTest Case 1: Return items\n";
$_GET = ['route' => 'auditorias/api_setores', 'cliente_id' => 1];
ob_start();
$controller->apiSetores();
$out = ob_get_clean();
$json = json_decode($out, true);
[$passed,$total] = assertTest(is_array($json) && ($json['success'] ?? false) === true, "success=true");
[$passed,$total] = assertTest(count($json['items'] ?? []) === 1, "returns 1 item");

echo "\nTest Case 2: Cache refresh on force when cached empty\n";
$_SESSION['cache_auditoria_setores'][1] = [];
$_SESSION['cache_auditoria_setores_ts'][1] = time();
$_GET = ['route' => 'auditorias/api_setores', 'cliente_id' => 1, 'force' => 1];
ob_start();
$controller->apiSetores();
$out = ob_get_clean();
$json = json_decode($out, true);
[$passed,$total] = assertTest(count($json['items'] ?? []) === 1, "force refresh returns item");

echo "\nTest Case 3: Missing cliente_id returns empty\n";
$_GET = ['route' => 'auditorias/api_setores'];
ob_start();
$controller->apiSetores();
$out = ob_get_clean();
$json = json_decode($out, true);
[$passed,$total] = assertTest(is_array($json) && ($json['success'] ?? false) === true, "success=true without cliente_id");
[$passed,$total] = assertTest(count($json['items'] ?? []) === 0, "returns empty items");

echo "\nTest Case 4: Exception returns success=false\n";
$mock->throw = true;
$_GET = ['route' => 'auditorias/api_setores', 'cliente_id' => 1, 'force' => 1];
ob_start();
$controller->apiSetores();
$out = ob_get_clean();
$json = json_decode($out, true);
[$passed,$total] = assertTest(is_array($json) && ($json['success'] ?? true) === false, "success=false on error");

echo "\n=====================================\n";
echo "Tests Completed: $passed/$total passed.\n";
echo "=====================================\n";

$buffer = ob_get_clean();
echo $buffer;
exit($passed === $total ? 0 : 1);
