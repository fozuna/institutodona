<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$view = file_get_contents(__DIR__ . '/../views/avaliacoes/index.php');
$controller = file_get_contents(__DIR__ . '/../controllers/AvaliacoesController.php');
$routes = file_get_contents(__DIR__ . '/../../public_html/index.php');

if ($view === false || $controller === false || $routes === false) {
    failFast('Não foi possível ler os arquivos do teste');
}

if (strpos($controller, 'function deleteAjax') === false) {
    failFast('Controller não possui endpoint deleteAjax');
}
ok('Endpoint deleteAjax presente no controller');

if (strpos($routes, "case 'avaliacoes/delete-ajax'") === false) {
    failFast('Rota avaliacoes/delete-ajax não registrada');
}
ok('Rota avaliacoes/delete-ajax registrada');

if (strpos($view, 'btn-delete-avaliacao') === false) {
    failFast('View de listagem não possui botão de exclusão');
}
ok('Botão de exclusão presente na view');

if (strpos($view, 'id="avaliacaoDeleteModal"') === false) {
    failFast('View de listagem não possui modal de confirmação');
}
ok('Modal de confirmação presente na view');

if (strpos($view, "fetch('index.php?route=avaliacoes/delete-ajax'") === false) {
    failFast('View não possui chamada assíncrona para delete-ajax');
}
ok('Fetch para delete-ajax presente na view');

if (strpos($controller, 'dados vinculados') === false) {
    failFast('Controller não possui mensagem de dependências (constraint)');
}
ok('Mensagem de dependências (constraint) presente no controller');

echo "All avaliacoes delete ajax UI unit tests passed.\n";
