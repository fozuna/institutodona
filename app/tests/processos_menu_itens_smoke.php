<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
];
$_GET['route'] = 'dashboard/index';
$content = '<div>Painel de teste</div>';

ob_start();
require __DIR__ . '/../views/layouts/main.php';
$html = (string)ob_get_clean();

foreach ([
    'Dashboard',
    'Pessoas',
    'Treinamentos',
    'Processos',
    'Biblioteca',
    'Auditorias',
    'Resultados',
    'Indicadores',
    'Agenda',
    'Plano de Ação',
    'Tarefas',
    'Cronograma',
    'Avaliações',
    'Cadastros',
    'Clientes',
    'Usuários',
    'Consultores',
    'Pilares',
    'Sobre e Manual de Uso',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('Menu deveria conter: ' . $needle);
    }
}

if (str_contains($html, 'Sobre & Manual')) {
    failFast('Layout não deveria manter o rótulo antigo "Sobre & Manual".');
}

$expectedOrder = [
    'Dashboard',
    'Pessoas',
    'Processos',
    'Resultados',
    'Agenda',
    'Plano de Ação',
    'Tarefas',
    'Cronograma',
    'Avaliações',
    'Cadastros',
    'Sobre e Manual de Uso',
];
$pos = -1;
foreach ($expectedOrder as $label) {
    $current = strpos($html, $label);
    if ($current === false) {
        failFast('Não foi possível localizar "' . $label . '" para validar ordem do menu.');
    }
    if ($current <= $pos) {
        failFast('Ordem do menu incorreta: "' . $label . '" apareceu fora de sequência.');
    }
    $pos = $current;
}

ok('Menu reorganizado renderiza grupos e ordem conforme especificação');
echo "processos_menu_itens_smoke passed.\n";
