<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$clientes = [
    ['id' => 10, 'nome_empresa' => 'Empresa Alpha'],
    ['id' => 20, 'nome_empresa' => 'Empresa Beta'],
];
$setores = [];
$selectedCliente = 0;
$selectedSetor = 0;
$values = [];
$errors = [];

ob_start();
require __DIR__ . '/../views/auditorias/create.php';
$html = (string)ob_get_clean();

if (strpos($html, 'Empresa Alpha') === false || strpos($html, 'Empresa Beta') === false) {
    failFast('Dropdown não renderizou empresas fornecidas pelo backend');
}
ok('Dropdown renderiza empresas fornecidas');

if (substr_count($html, '<option value="') < 3) {
    failFast('Quantidade de opções do select de empresa abaixo do esperado');
}
ok('Dropdown possui opções esperadas');

if (strpos($html, 'clienteDebugStatus') === false) {
    failFast('Indicador de debug do dropdown não foi renderizado');
}
ok('Indicador de debug renderizado');

if (strpos($html, 'responsavelList_${index}') === false) {
    failFast('Template de autocomplete de responsável não foi renderizado');
}
ok('Template de autocomplete de responsável renderizado');

echo "All auditoria dropdown integration tests passed.\n";
