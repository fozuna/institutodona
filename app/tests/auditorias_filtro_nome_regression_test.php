<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AuditoriasController;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$auditorias = new AuditoriaModel();

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['aud_a1' => 0, 'aud_a2' => 0, 'aud_b1' => 0, 'setor_a' => 0, 'setor_b' => 0, 'dep_a' => 0, 'dep_b' => 0, 'cliente_a' => 0, 'cliente_b' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        foreach (['aud_a1', 'aud_a2', 'aud_b1'] as $k) {
            if (!empty($cleanup[$k])) {
                $pdo->prepare('DELETE FROM auditoria_questoes WHERE auditoria_id = :id')->execute(['id' => $cleanup[$k]]);
                $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $cleanup[$k]]);
            }
        }
        if (!empty($cleanup['setor_a'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_a']]); }
        if (!empty($cleanup['setor_b'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_b']]); }
        if (!empty($cleanup['dep_a'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['dep_a']]); }
        if (!empty($cleanup['dep_b'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['dep_b']]); }
        if (!empty($cleanup['cliente_a'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_a']]); }
        if (!empty($cleanup['cliente_b'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_b']]); }
    } catch (\Throwable $e) {
    }
});

$clienteA = $clientes->create(['nome_empresa' => 'Cliente Filtro A ' . $suffix, 'CNPJ' => '11.111.111/0001-' . substr($suffix, 0, 2), 'contato' => 'x']);
$clienteB = $clientes->create(['nome_empresa' => 'Cliente Filtro B ' . $suffix, 'CNPJ' => '22.222.222/0001-' . substr($suffix, 0, 2), 'contato' => 'x']);
if ($clienteA <= 0 || $clienteB <= 0) failFast('Falha ao criar clientes de teste');
$cleanup['cliente_a'] = $clienteA;
$cleanup['cliente_b'] = $clienteB;

$depA = $departamentos->create(['nome' => 'Dep A ' . $suffix, 'cliente_id' => $clienteA, 'cliente_ids' => [$clienteA]]);
$setorA = $setores->create(['nome' => 'Setor A ' . $suffix, 'departamento_id' => $depA]);
$depB = $departamentos->create(['nome' => 'Dep B ' . $suffix, 'cliente_id' => $clienteB, 'cliente_ids' => [$clienteB]]);
$setorB = $setores->create(['nome' => 'Setor B ' . $suffix, 'departamento_id' => $depB]);
$cleanup['dep_a'] = $depA;
$cleanup['setor_a'] = $setorA;
$cleanup['dep_b'] = $depB;
$cleanup['setor_b'] = $setorB;
ok('Criou clientes, departamentos e setores para o teste');

$nomeSeguranca = 'Auditoria de Segurança Elétrica ' . $suffix;
$nomeQualidade = 'Auditoria de Qualidade ' . $suffix;
$nomeClienteB = 'Auditoria Cliente B ' . $suffix;

$audA1 = $auditorias->create(['cliente_id' => $clienteA, 'setor_id' => $setorA, 'nome_auditoria' => $nomeSeguranca, 'data_auditoria' => '2026-07-05', 'questoes' => [['pergunta' => 'P1', 'referencia_esperada' => 'R1']]], 1);
$audA2 = $auditorias->create(['cliente_id' => $clienteA, 'setor_id' => $setorA, 'nome_auditoria' => $nomeQualidade, 'data_auditoria' => '2026-07-06', 'questoes' => [['pergunta' => 'P1', 'referencia_esperada' => 'R1']]], 1);
$audB1 = $auditorias->create(['cliente_id' => $clienteB, 'setor_id' => $setorB, 'nome_auditoria' => $nomeClienteB, 'data_auditoria' => '2026-07-07', 'questoes' => [['pergunta' => 'P1', 'referencia_esperada' => 'R1']]], 1);
if ($audA1 <= 0 || $audA2 <= 0 || $audB1 <= 0) failFast('Falha ao criar auditorias de teste');
$cleanup['aud_a1'] = $audA1;
$cleanup['aud_a2'] = $audA2;
$cleanup['aud_b1'] = $audB1;
ok('Criou 3 auditorias de teste (2 no cliente A, 1 no cliente B)');

// 1) Sintoma original: filtrar por nome não deve mais lançar exceção nem redirecionar para erro.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'auditorias/index', 'q' => 'Segurança'];
ob_start();
try {
    (new AuditoriasController())->index();
    $html = (string)ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Filtro por nome ainda lança exceção: ' . get_class($e) . ': ' . $e->getMessage());
}
if (!str_contains($html, 'Auditorias')) failFast('Página não renderizou corretamente com filtro de nome');
ok('Filtro por nome não lança mais exceção nem quebra a página');

// 2) Nome exato.
if (!str_contains($html, $nomeSeguranca)) failFast('Filtro por nome parcial "Segurança" não encontrou a auditoria esperada');
if (str_contains($html, $nomeQualidade)) failFast('Filtro por nome vazou auditoria não correspondente (Qualidade)');
ok('Filtro por termo parcial retorna apenas a auditoria correspondente');

// 3) Nome parcial (substring no meio da palavra).
$_GET = ['route' => 'auditorias/index', 'q' => 'Elétrica'];
ob_start();
(new AuditoriasController())->index();
$htmlParcial = (string)ob_get_clean();
if (!str_contains($htmlParcial, $nomeSeguranca)) failFast('Busca parcial por "Elétrica" não encontrou a auditoria esperada');
ok('Busca parcial por parte final do nome funciona');

// 4) Filtro sem resultados.
$_GET = ['route' => 'auditorias/index', 'q' => 'TermoQueNaoExisteXPTO' . $suffix];
ob_start();
(new AuditoriasController())->index();
$htmlVazio = (string)ob_get_clean();
if (str_contains($htmlVazio, $nomeSeguranca) || str_contains($htmlVazio, $nomeQualidade)) failFast('Filtro sem correspondência retornou resultados indevidos');
ok('Filtro sem resultados não gera erro e não retorna itens indevidos');

// 5) Filtro com acentos (busca usando exatamente os acentos do nome cadastrado).
$_GET = ['route' => 'auditorias/index', 'q' => 'Segurança Elétrica'];
ob_start();
try {
    (new AuditoriasController())->index();
    $htmlAcento = (string)ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Filtro com acentos lança exceção: ' . $e->getMessage());
}
if (!str_contains($htmlAcento, $nomeSeguranca)) failFast('Filtro com acentos não encontrou a auditoria esperada');
ok('Filtro com acentos funciona sem erro e retorna o resultado correto');

// 6) Isolamento multiempresa: usuário escopado só ao cliente A não deve ver a auditoria do cliente B, mesmo pesquisando pelo nome dela.
// Usa um id de usuário que certamente não existe em `usuarios`, para que Auth::refreshScope()
// (chamado por requireLogin()) não sobrescreva os dados fictícios de sessão com os de um usuário real.
$_SESSION['user'] = ['id' => 999999997, 'nome' => 'Cliente Admin A', 'email' => 'clienteA@example.com', 'tipo_acesso' => 'cliente_admin', 'allowed_client_ids' => [$clienteA]];
$_GET = ['route' => 'auditorias/index', 'q' => 'Auditoria Cliente B'];
ob_start();
(new AuditoriasController())->index();
$htmlScoped = (string)ob_get_clean();
if (str_contains($htmlScoped, $nomeClienteB)) failFast('Vazamento multiempresa: usuário do cliente A viu auditoria do cliente B ao filtrar por nome');
ok('Isolamento multiempresa preservado: filtro por nome não vaza auditorias de outro cliente');
$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

// 7) Combinação com outros filtros existentes (nome + cliente).
$_GET = ['route' => 'auditorias/index', 'q' => 'Auditoria de', 'cliente' => (string)$clienteA];
ob_start();
(new AuditoriasController())->index();
$htmlCombinado = (string)ob_get_clean();
if (!str_contains($htmlCombinado, $nomeSeguranca) || !str_contains($htmlCombinado, $nomeQualidade)) {
    failFast('Combinação de filtro por nome + cliente não retornou as duas auditorias esperadas do cliente A');
}
if (str_contains($htmlCombinado, $nomeClienteB)) failFast('Combinação de filtros vazou auditoria de outro cliente');
ok('Combinação do filtro por nome com filtro de cliente funciona corretamente');

// 8) Filtro vazio mantém a listagem normal (sem cláusula de nome aplicada).
$_GET = ['route' => 'auditorias/index', 'q' => '', 'cliente' => (string)$clienteA];
ob_start();
(new AuditoriasController())->index();
$htmlSemFiltro = (string)ob_get_clean();
if (!str_contains($htmlSemFiltro, $nomeSeguranca) || !str_contains($htmlSemFiltro, $nomeQualidade)) {
    failFast('Filtro de nome vazio não deveria restringir a listagem do cliente A');
}
ok('Filtro de nome vazio mantém a listagem normal');

// 9) Paginação preserva o parâmetro de busca (page + q juntos não devem quebrar).
$_GET = ['route' => 'auditorias/index', 'q' => 'Auditoria de', 'cliente' => (string)$clienteA, 'page' => '1'];
ob_start();
try {
    (new AuditoriasController())->index();
    $htmlPaginado = (string)ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Combinação de paginação com filtro de nome lança exceção: ' . $e->getMessage());
}
if (!str_contains($htmlPaginado, $nomeSeguranca)) failFast('Paginação combinada com filtro de nome não retornou o resultado esperado');
ok('Paginação combinada com filtro de nome funciona sem erro');

// 10) Método de agregação usado pelo relatório executivo (Item 9) também usa o mesmo helper — confere que não quebra mais.
$filtersExec = ['cliente' => $clienteA, 'departamento' => null, 'setor' => null, 'status' => null, 'farol' => null, 'inicio' => null, 'fim' => null, 'q' => 'Segurança'];
try {
    $execData = $auditorias->executiveReportData($filtersExec);
    ok('executiveReportData() (relatório executivo) também aceita filtro de nome sem erro, após a correção do helper compartilhado');
} catch (\Throwable $e) {
    failFast('executiveReportData() ainda lança exceção com filtro de nome: ' . $e->getMessage());
}

echo "Auditorias filtro por nome regression tests passed.\n";
ob_end_flush();
