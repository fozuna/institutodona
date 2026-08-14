<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

/**
 * Sprint de normalização do menu lateral: main.php passou a montar os links
 * via helpers ($navLink/$submenuTrigger - ver app/views/layouts/main.php),
 * então strings como "route=reunioes/index" deixaram de existir literalmente
 * no código-fonte (viram argumentos de função, montadas em runtime). Este
 * teste foi reescrito para verificar o HTML renderizado (comportamento real),
 * não mais o código-fonte bruto - mais robusto a refactors futuros que
 * preservem o comportamento.
 */

function renderMenu(array $user): string
{
    $_SESSION['user'] = $user;
    $_GET['route'] = 'dashboard/index';
    $content = '<div>Painel de teste</div>';
    ob_start();
    require __DIR__ . '/../views/layouts/main.php';
    return (string)ob_get_clean();
}

$institutoBase = [
    'id' => 1,
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
];

// 1) Usuário sem "Ozuna" no nome: Reuniões/Coaching/Processos devem existir no HTML
//    (renderizados, para não quebrar CSS/JS) mas ocultos via display:none + aria-hidden.
$htmlSemOzuna = renderMenu($institutoBase + ['nome' => 'Fulano de Tal']);
foreach (['reunioes/index', 'coaching/index', 'processos/index'] as $route) {
    if (strpos($htmlSemOzuna, 'route=' . $route) === false) {
        failFast("Item de $route deveria estar presente no HTML (oculto via atributo, não removido)");
    }
}
$hiddenCount = substr_count($htmlSemOzuna, 'style="display:none" aria-hidden="true" tabindex="-1"');
if ($hiddenCount < 3) {
    failFast('Reuniões/Coaching/Processos deveriam estar ocultos (display:none) para usuário sem "Ozuna" no nome. Ocultos encontrados: ' . $hiddenCount);
}
ok('Reuniões/Coaching/Processos ficam ocultos (mas presentes no DOM) para usuário sem "Ozuna" no nome');

// 2) Usuário com "Ozuna" no nome: os mesmos itens devem aparecer visíveis (sem o atributo de ocultação).
$htmlComOzuna = renderMenu($institutoBase + ['nome' => 'Instituto Ozuna']);
if (strpos($htmlComOzuna, 'style="display:none" aria-hidden="true" tabindex="-1"') !== false) {
    failFast('Usuário com "Ozuna" no nome não deveria ter nenhum item de menu oculto por essa regra');
}
foreach (['reunioes/index', 'coaching/index', 'processos/index'] as $route) {
    if (strpos($htmlComOzuna, 'route=' . $route) === false) {
        failFast("Item de $route deveria aparecer visível para usuário com \"Ozuna\" no nome");
    }
}
ok('Reuniões/Coaching/Processos aparecem visíveis para usuário com "Ozuna" no nome');

// 3) Item unificado "Sobre e Manual de Uso" (sem itens separados "Manual"/"Sobre").
if (str_contains($htmlSemOzuna, '>Manual</span>') || str_contains($htmlSemOzuna, '>Sobre</span>')) {
    failFast('Menu lateral não deve exibir itens separados "Manual" e "Sobre" após unificação.');
}
if (!str_contains($htmlSemOzuna, 'Sobre e Manual de Uso')) {
    failFast('Menu lateral deve exibir o item "Sobre e Manual de Uso".');
}
if (!str_contains($htmlSemOzuna, 'href="index.php?route=about/index"')) {
    failFast('Menu lateral deve apontar para route=about/index.');
}
if (str_contains($htmlSemOzuna, 'route=about/index&tab=')) {
    failFast('Menu lateral não deve depender do parâmetro tab para a página unificada.');
}
ok('Menu lateral unificado em "Sobre e Manual de Uso" sem itens redundantes');

echo "menu_ozuna_only_items_smoke passed.\n";
