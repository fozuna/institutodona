<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$createView = file_get_contents(__DIR__ . '/../views/auditorias/create.php');
$controller = file_get_contents(__DIR__ . '/../controllers/AuditoriasController.php');
$routes = file_get_contents(__DIR__ . '/../../public_html/index.php');

if ($createView === false || $controller === false || $routes === false) {
    failFast('Não foi possível ler os arquivos do teste');
}

if (strpos($createView, "route=auditorias/api_clientes") === false) {
    failFast('View create não possui fallback para API de clientes');
}
ok('Fallback frontend para API de clientes presente');

if (strpos($controller, 'function apiClientes') === false) {
    failFast('Controller não possui endpoint apiClientes');
}
ok('Endpoint apiClientes presente no controller');

if (strpos($routes, "case 'auditorias/api_clientes'") === false) {
    failFast('Rota auditorias/api_clientes não registrada');
}
ok('Rota auditorias/api_clientes registrada');

if (strpos($controller, 'function apiColaboradores') === false) {
    failFast('Controller não possui endpoint apiColaboradores');
}
ok('Endpoint apiColaboradores presente no controller');

if (strpos($createView, "route=auditorias/api_colaboradores") === false) {
    failFast('View create não possui consulta de autocomplete de colaboradores');
}
ok('Autocomplete de colaboradores presente no frontend');

echo "All auditoria dropdown unit tests passed.\n";
