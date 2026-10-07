<?php
// Pilar de Pessoas - Sprint 05: Central do Colaborador. Cobre tenant,
// cross-tenant, resumo (mesmos predicados das listagens), avaliação mais
// recente, GAPs, ações, PDI ativo e progresso, necessidades, pontos de
// atenção (fatos, sem score), timeline derivada, desenvolvimento em lote
// equivalente ao individual e número constante de consultas (sem N+1).
require __DIR__ . '/../autoload.php';
require __DIR__ . '/helpers/pessoas_sprint05_fixtures.php';

use App\Controllers\PessoasGestaoController;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaDesenvolvimentoModel;
use App\Models\PessoaOperacionalModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }
function asInstituto(): void { $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []]; }
function asClienteAdmin(int $eid): void { $_SESSION['user'] = ['id' => 987654, 'nome' => 'CA', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eid, 'allowed_client_ids' => [$eid]]; }
function eq($got, $exp, string $what): void { if ($got !== $exp) { failFast("$what: esperado " . json_encode($exp) . ' obtido ' . json_encode($got)); } }

asInstituto();
$F = s05_fixtures('A1 <script>alert(77)</script> ' . bin2hex(random_bytes(2)));
$eA = $F['eA']; $a1 = $F['a1'];
$pdo = Database::getConnection();
$m = new PessoaOperacionalModel();
ok('Fixtures criadas (nome do colaborador com tentativa de XSS)');

// ---- Tenant ----
asClienteAdmin($F['B']['eid']);
if ((new ColaboradorModel())->find($a1) !== null) { failFast('Cliente Admin de B não pode resolver colaborador de A'); }
if ($m->perfilOrganizacional($a1) !== []) { failFast('Perfil organizacional de A não pode vazar para B'); }
if ($m->treinamentosDoColaborador($a1, $eA) !== []) { failFast('Treinamentos de A não podem vazar para B'); }
asClienteAdmin($eA);
if ((new ColaboradorModel())->find($a1) === null) { failFast('Cliente Admin de A deve resolver o próprio colaborador'); }
ok('Colaborador do tenant correto acessível; cross-tenant bloqueado no Model');

// ---- Resumo ----
$R = $m->resumoColaborador($a1, $eA);
eq($R, [
    'gaps_abertos' => 2, 'gaps_em_tratamento' => 1, 'gaps_abertos_sem_acao' => 2,
    'acoes_pendentes' => 1, 'acoes_em_andamento' => 0, 'acoes_vencidas' => 1,
    'necessidades_pendentes' => 1, 'avaliacoes_pendentes' => 0, 'avaliacoes_em_andamento' => 1,
    // Sprint 05.1: contadores de Feedback (sem score) - o fixture de a1 tem 1 positivo.
    'feedbacks_total' => 1, 'feedbacks_positivos' => 1, 'feedbacks_melhoria' => 0,
], 'resumo do colaborador a1');
// Mesma semântica das listagens:
eq($m->listarGaps([$eA], ['colaborador_id' => $a1, 'status' => 'aberto', 'tratamento' => 'sem_acao'], 1, 1)['total'], $R['gaps_abertos_sem_acao'], 'Central x listagem (GAP sem ação)');
eq($m->listarAcoes([$eA], ['colaborador_id' => $a1, 'atrasadas' => 1], 1, 1)['total'], $R['acoes_vencidas'], 'Central x listagem (ações atrasadas)');
eq($m->listarNecessidades([$eA], ['colaborador_id' => $a1, 'status' => 'pendente'], 1, 1)['total'], $R['necessidades_pendentes'], 'Central x listagem (necessidades)');
ok('Resumo correto e idêntico às listagens');

$perfil = $m->perfilOrganizacional($a1);
if (!str_contains((string)$perfil['empresa_nome'], 'Emp S05 A') || !str_contains((string)$perfil['departamento_nome'], 'Dep1 A') || !str_contains((string)$perfil['setor_nome'], 'Set1 A') || !str_contains((string)$perfil['funcao_nome'], 'Fun1 A')) {
    failFast('Perfil organizacional incompleto: ' . json_encode($perfil));
}
$trs = $m->treinamentosDoColaborador($a1, $eA);
eq([count($trs), $trs[0]['status']], [1, 'pendente'], 'treinamentos do colaborador (roster)');
ok('Posição organizacional e treinamentos relacionados');

// ---- Pontos de atenção (sem score) ----
$pdiAtivo = null;
foreach ((new \App\Models\PessoaPdiModel())->listByColaborador($a1, $eA) as $p) { if ($p['status'] === 'ativo') { $pdiAtivo = $p; } }
eq([(float)$pdiAtivo['progresso'], (int)$pdiAtivo['objetivos_validos'], (int)$pdiAtivo['objetivos_concluidos']], [50.0, 2, 1], 'PDI ativo e progresso (cancelado fora do denominador)');
$pts = PessoaOperacionalModel::pontosDeAtencao($a1, $R, $pdiAtivo);
eq(array_column($pts, 'texto'), [
    '2 GAP(s) aberto(s) sem ação de melhoria',
    '1 ação(ões) de melhoria atrasada(s)',
    '1 necessidade(s) de treinamento pendente(s)',
    '1 avaliação(ões) pendente(s) ou em andamento',
    'PDI ativo com 1 objetivo(s) pendente(s)',
], 'pontos de atenção factuais');
eq($pts[0]['href'], 'index.php?route=pessoas/gaps&status=aberto&tratamento=sem_acao&colaborador_id=' . $a1, 'ponto de atenção acionável (GAP sem ação)');
$zero = array_fill_keys(array_keys($R), 0);
eq(PessoaOperacionalModel::pontosDeAtencao($F['a3'], $zero, null), [], 'sem pendências => lista vazia');
ok('Pontos de atenção: fatos acionáveis, sem score, estado vazio neutro');

// ---- Timeline ----
$html = (function () use ($a1): string {
    $_GET = ['route' => 'pessoas/colaboradorHistorico', 'id' => (string)$a1];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    (new PessoasGestaoController())->colaboradorHistorico();
    return (string)ob_get_clean();
})();
foreach ([
    'Central do Colaborador', 'Emp S05 A', 'Dep1 A', 'Fun1 A', 'PDI ativo — 50%', 'Avaliação concluída em 15/09/2026', '1 ação(ões) atrasada(s)',
    '4,50 / 5,00', 'Pontos de atenção', 'Linha do tempo', 'Abrir PDI ativo', 'Registrar feedback', 'Registrar GAP', 'Criar ação',
    'PDI criado', 'Ação encaminhada para Plano de Ação', 'GAP registrado', 'Feedback Positivo registrado', 'Avaliação concluída —',
    'route=pessoas%2Fgaps&amp;colaborador_id=' . $a1 . '&amp;status=aberto&amp;tratamento=sem_acao',
    'aria-label="Navegação do Pilar de Pessoas"',
] as $needle) {
    if (!str_contains($html, $needle)) { failFast('Central deveria conter: ' . $needle); }
}
if (!str_contains($html, '>Ativo</span>') || str_contains($html, '>Inativo</span>')) { failFast('Colaborador ativo deve exibir o selo "Ativo" (parciais não podem sobrescrever variáveis da view)'); }
if (str_contains($html, '<script>alert(77)</script>')) { failFast('Nome do colaborador precisa ser escapado (XSS)'); }
if (!str_contains($html, '&lt;script&gt;alert(77)&lt;/script&gt;')) { failFast('Nome escapado não encontrado'); }
if (str_contains($html, '>Criar PDI</a>') && str_contains(substr($html, 0, (int)strpos($html, 'Resumo')), 'pdiCreate')) { failFast('Com PDI ativo o atalho deve ser "Abrir PDI ativo"'); }
$posPdi = strpos($html, 'PDI criado'); $posGap = strpos($html, 'GAP registrado — gA1'); $posFb = strpos($html, 'Feedback Positivo registrado');
if (!($posPdi < $posGap && $posGap < $posFb)) { failFast('Timeline deveria estar em ordem decrescente (PDI 19/09 > GAP 16/09 > Feedback 10/09)'); }
ok('Central renderiza cabeçalho, resumo, situação, pontos de atenção, atalhos e timeline decrescente; XSS escapado');

$tl = PessoaOperacionalModel::timeline([], [], [], [], ['planos' => [], 'treinamentos' => [], 'necessidades' => []], []);
eq($tl, [], 'timeline vazia sem eventos');
ok('Timeline derivada dos registros (nenhuma tabela nova)');

// ---- Desenvolvimento em lote == individual ----
$dev = new PessoaDesenvolvimentoModel();
$acoesModel = new PessoaAcaoMelhoriaModel();
foreach ([$a1, $F['a2']] as $col) {
    $acoes = $acoesModel->listByColaborador($col, $eA);
    $lote = $dev->desenvolvimentoDasAcoes($acoes, $col);
    foreach ($acoes as $ac) {
        $ind = $dev->desenvolvimentoDaAcao($ac);
        $b = $lote[(int)$ac['id']];
        $norm = static fn(array $d): array => [
            $d['estado'], $d['plano']['plano_task_id'] ?? null,
            array_map(static fn($t) => [(int)$t['treinamento_id'], $t['situacao']], $d['treinamentos']),
            array_map(static fn($n) => (int)$n['id'], $d['necessidades']),
        ];
        eq($norm($b), $norm($ind), 'desenvolvimento em lote = individual (ação ' . $ac['titulo'] . ')');
    }
}
ok('desenvolvimentoDasAcoes() produz exatamente o mesmo resultado de desenvolvimentoDaAcao()');

// ---- Número de consultas constante (sem N+1) ----
$q = static function () use ($pdo): int { return (int)$pdo->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(PDO::FETCH_NUM)[1]; };
$acoesA2 = $acoesModel->listByColaborador($F['a2'], $eA);          // 2 ações, 1 com treinamento
$acoesUma = array_values(array_filter($acoesA2, static fn($a) => $a['titulo'] === 'ac4 vencida treinamento'));
$q0 = $q(); $dev->desenvolvimentoDasAcoes($acoesUma, $F['a2']); $n1 = $q() - $q0 - 1;
// multiplica as ações (mesmo colaborador) para simular volume
$muitas = array_merge($acoesA2, $acoesA2, $acoesA2, $acoesA2);
$q0 = $q(); $dev->desenvolvimentoDasAcoes($muitas, $F['a2']); $n2 = $q() - $q0 - 1;
if ($n1 !== $n2 || $n1 > 6) { failFast("Desenvolvimento em lote deveria ter consultas constantes: 1 ação=$n1, 8 ações=$n2"); }
$q0 = $q(); $m->resumoColaborador($a1, $eA); $m->encaminhamentosDoColaborador($a1, $eA); $m->treinamentosDoColaborador($a1, $eA); $c1 = $q() - $q0 - 1;
$q0 = $q(); $m->resumoColaborador($F['a3'], $eA); $m->encaminhamentosDoColaborador($F['a3'], $eA); $m->treinamentosDoColaborador($F['a3'], $eA); $c3 = $q() - $q0 - 1;
if ($c1 !== $c3) { failFast("Consultas da Central deveriam independer do volume: a1=$c1, a3=$c3"); }
ok("Sem N+1: desenvolvimento em lote = $n1 consultas para 1 ou 8 ações; Central = $c1 consultas por colaborador");

echo "\nTodos os testes da Central do Colaborador passaram.\n";
