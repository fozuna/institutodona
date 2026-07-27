<?php
require_once __DIR__ . '/../autoload.php';

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

// Estado reportado pelo usuário: acesso direto/sem navegação prévia, sem
// cliente resolvido ainda (Instituto não recebe auto-seleção de cliente).
$clientes = [
    ['id' => 1, 'nome_empresa' => 'QUEIJOS BRUN LTDA'],
];
$cliente = 0;
$indicadores = [];
$indicadorId = 0;
$periodoInicio = '';
$periodoFim = '';
$items = [];

ob_start();
require __DIR__ . '/../views/indicadores/realizado.php';
$htmlSemCliente = (string)ob_get_clean();

assert_true(
    strpos($htmlSemCliente, 'Selecione um cliente para continuar') !== false,
    'Sem cliente: mensagem de orientação é exibida'
);
assert_true(
    preg_match('/id="indicadoresIndicador"[^>]*disabled/', $htmlSemCliente) === 1,
    'Sem cliente: campo Indicador permanece desabilitado no primeiro carregamento'
);
assert_true(
    strpos($htmlSemCliente, 'id="indicadoresRealizadoList"') === false,
    'Sem cliente: a lista de lançamento não é renderizada (div não existe no DOM)'
);

// Regressão do bug: selecionar uma empresa no <select> precisa navegar/recarregar
// a página (o servidor recalcula indicadores/estados), pois não existe reidratação
// client-side dos campos dependentes.
assert_true(
    strpos($htmlSemCliente, "clienteSelect.addEventListener('change'") !== false,
    'Select de cliente possui listener de troca (antes, selecionar a empresa não disparava nada)'
);
assert_true(
    strpos($htmlSemCliente, 'form.submit();') !== false,
    'Troca de cliente aciona reload real da página (form.submit) em vez de ficar preso em AJAX'
);

// Regressão do "clique morto" no botão Aplicar filtro: sem lista carregada ainda,
// o submit nativo (GET) não pode ser bloqueado por e.preventDefault().
assert_true(
    strpos($htmlSemCliente, 'if (!clienteSelect || !clienteSelect.value || !listWrap) return;') !== false,
    'Submit do formulário deixa a navegação nativa ocorrer quando não há cliente/lista carregados'
);

// Com cliente já resolvido, o comportamento anterior (AJAX ao trocar indicador/período)
// deve continuar intacto.
$cliente = 1;
$indicadores = [['id' => 5, 'indicador' => 'Faturamento']];
$indicadorId = 5;
$periodoInicio = '2026-07-01';
$periodoFim = '2026-07-31';
$items = [];

ob_start();
require __DIR__ . '/../views/indicadores/realizado.php';
$htmlComCliente = (string)ob_get_clean();

assert_true(
    strpos($htmlComCliente, 'Selecione um cliente para continuar') === false,
    'Com cliente: mensagem de orientação não é exibida'
);
assert_true(
    strpos($htmlComCliente, 'id="indicadoresRealizadoList"') !== false,
    'Com cliente: a lista de lançamento é renderizada'
);
assert_true(
    preg_match('/id="indicadoresIndicador"[^>]*disabled/', $htmlComCliente) !== 1,
    'Com cliente: campo Indicador fica habilitado'
);

echo "Indicadores realizado cliente-change wiring regression tests passed.\n";
