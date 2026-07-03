<?php
require_once __DIR__ . '/../autoload.php';

$file = __DIR__ . '/../views/auditorias/index.php';
$source = file_get_contents($file);

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

assert_true($source !== false, 'Carrega a view de listagem de auditorias');
assert_true(str_contains($source, "const storageKey = 'auditorias:index:filters';"), 'View define chave dedicada de sessionStorage');
assert_true(str_contains($source, 'sessionStorage.setItem(storageKey, JSON.stringify(readState()));'), 'View persiste os filtros automaticamente na sessão');
assert_true(str_contains($source, 'btnRedefinirFiltrosSalvos'), 'View expõe ação explícita para redefinir filtros salvos');
assert_true(str_contains($source, 'window.requestAnimationFrame(()=>filtroForm.submit());'), 'View restaura filtros salvos e reaplica a busca ao retornar para a tela');

echo "auditorias_filters_session_storage_smoke passed.\n";
