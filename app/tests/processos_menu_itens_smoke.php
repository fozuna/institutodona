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

// Ordem vigente do menu lateral (nível superior). Usa marcadores estruturais
// (href da rota / data-submenu-trigger) em vez do rótulo, porque rótulos como
// "Avaliações" ou "Processos" também aparecem dentro de outros itens/grupos.
$expectedOrder = [
    'Dashboard' => 'href="index.php?route=dashboard/index"',
    'Avaliações' => 'href="index.php?route=avaliacoes/index"',
    'Cronograma' => 'href="index.php?route=cronograma/index"',
    'Processos (grupo)' => 'data-submenu-trigger="processos"',
    'Resultados (grupo)' => 'data-submenu-trigger="resultados"',
    'Pessoas (grupo)' => 'data-submenu-trigger="pessoas"',
    'Plano de Ação' => 'href="index.php?route=planoacao/index"',
    'Tarefas' => 'href="index.php?route=tarefas/index"',
    'Agenda' => 'href="index.php?route=agenda/index"',
    'Cadastros (grupo)' => 'data-submenu-trigger="cadastros"',
    'Sobre e Manual de Uso' => 'href="index.php?route=about/index"',
];
$pos = -1;
foreach ($expectedOrder as $label => $marker) {
    $current = strpos($html, $marker);
    if ($current === false) {
        failFast('Não foi possível localizar "' . $label . '" para validar ordem do menu.');
    }
    if ($current <= $pos) {
        failFast('Ordem do menu incorreta: "' . $label . '" apareceu fora de sequência.');
    }
    $pos = $current;
}

// Itens agrupados precisam estar dentro do painel do respectivo grupo.
$dentroDoPainel = static function (string $html, string $painel, string $proximoMarcador, array $rotas): void {
    $ini = strpos($html, 'data-submenu-panel="' . $painel . '"');
    $fim = strpos($html, $proximoMarcador, (int)$ini);
    if ($ini === false || $fim === false) {
        failFast('Painel "' . $painel . '" não encontrado.');
    }
    foreach ($rotas as $rota) {
        $p = strpos($html, 'href="index.php?route=' . $rota . '"', $ini);
        if ($p === false || $p > $fim) {
            failFast('Item ' . $rota . ' deveria estar dentro do grupo "' . $painel . '".');
        }
    }
};
$dentroDoPainel($html, 'processos', 'data-submenu-trigger="resultados"', ['manuais/index', 'auditorias/index', 'processos/index']);
$dentroDoPainel($html, 'resultados', 'data-submenu-trigger="pessoas"', ['indicadores/index']);
$dentroDoPainel($html, 'pessoas', 'href="index.php?route=planoacao/index"', ['pessoas/visaoGeral', 'treinamentos/index']);
$dentroDoPainel($html, 'cadastros', 'href="index.php?route=about/index"', ['clientes/index', 'usuarios/index', 'consultores/index', 'pilares/index']);

ok('Menu reorganizado renderiza grupos e ordem conforme especificação');
echo "processos_menu_itens_smoke passed.\n";
