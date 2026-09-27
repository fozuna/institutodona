<?php use App\Core\PessoasAvaliacaoScale; ?>
<?php
/** @var array $dados */ /** @var array $clientes */ /** @var int $selectedEmpresa */ /** @var array $filters */
/** @var array $departamentos */ /** @var array $setores */ /** @var array $funcoes */ /** @var array $links */
$empresaQs = $selectedEmpresa > 0 ? '&empresa_id=' . (int)$selectedEmpresa : '';
$card = static function (string $label, $valor, string $nota = '', string $href = '') : string {
    $inner = '<div class="text-xs text-gray-500">' . htmlspecialchars($label) . '</div>'
        . '<div class="text-2xl font-bold text-brand-black">' . htmlspecialchars((string)$valor) . '</div>'
        . ($nota !== '' ? '<div class="text-xs text-gray-400 mt-1">' . htmlspecialchars($nota) . '</div>' : '');
    return $href !== ''
        ? '<a href="' . htmlspecialchars($href) . '" class="bg-white shadow rounded-xl p-4 block hover:shadow-md transition-shadow">' . $inner . '</a>'
        : '<div class="bg-white shadow rounded-xl p-4">' . $inner . '</div>';
};
$media = $dados['avaliacoes']['media'];
$mediaTxt = $media !== null ? number_format((float)$media, 2, ',', '.') . ' / ' . number_format((float)PessoasAvaliacaoScale::NOTA_MAX, 2, ',', '.') : '—';
$maxFaixa = max(1, ...array_map(static fn($d) => (int)$d['total'], $dados['distribuicao']));
$avUrl = !empty($links['avaliacoes']) ? 'index.php?route=pessoas/index' . $empresaQs : '';
$pdiAtivosUrl = !empty($links['pdi']) ? 'index.php?route=pessoas/pdiIndex&status=ativo' . $empresaQs : '';
$pdiRascUrl = !empty($links['pdi']) ? 'index.php?route=pessoas/pdiIndex&status=rascunho' . $empresaQs : '';
$pdiConcUrl = !empty($links['pdi']) ? 'index.php?route=pessoas/pdiIndex&status=concluido' . $empresaQs : '';
// Sprint 05: destinos acionáveis. Levam empresa + filtros organizacionais (nunca o
// período: estes contadores são de situação atual) e usam os mesmos predicados.
$escopoLista = array_filter([
    'empresa_id' => $selectedEmpresa > 0 ? (int)$selectedEmpresa : null,
    'departamento_id' => $filters['departamento_id'] ?? null,
    'setor_id' => $filters['setor_id'] ?? null,
    'funcao_id' => $filters['funcao_id'] ?? null,
]);
$lista = static function (string $chave, string $rota, array $extra) use ($links, $escopoLista): string {
    return !empty($links[$chave]) ? 'index.php?' . http_build_query(array_merge(['route' => $rota], $escopoLista, $extra)) : '';
};
$gapAbertoUrl = $lista('gaps', 'pessoas/gaps', ['status' => 'aberto']);
$gapTratUrl = $lista('gaps', 'pessoas/gaps', ['status' => 'em_tratamento']);
$gapSemAcaoUrl = $lista('gaps', 'pessoas/gaps', ['status' => 'aberto', 'tratamento' => 'sem_acao']);
$acaoPendUrl = $lista('acoes', 'pessoas/acoes', ['status' => 'pendente']);
$acaoAndUrl = $lista('acoes', 'pessoas/acoes', ['status' => 'em_andamento']);
$acaoVencUrl = $lista('acoes', 'pessoas/acoes', ['atrasadas' => 1]);
$necPendUrl = $lista('necessidades', 'pessoas/necessidades', ['status' => 'pendente']);
$pessoasSubnavAtivo = 'visao';

$atencao = [];
if ($dados['gaps']['abertos_sem_acao'] > 0) { $atencao[] = [$dados['gaps']['abertos_sem_acao'] . ' GAP(s) aberto(s) sem ação de melhoria', $gapSemAcaoUrl]; }
if ($dados['acoes']['vencidas'] > 0) { $atencao[] = [$dados['acoes']['vencidas'] . ' ação(ões) de melhoria atrasada(s)', $acaoVencUrl]; }
if ($dados['objetivos_vencidos'] > 0) { $atencao[] = [$dados['objetivos_vencidos'] . ' objetivo(s) de PDI vencido(s)', $pdiAtivosUrl]; }
if ($dados['pdis']['ativos_atrasados'] > 0) { $atencao[] = [$dados['pdis']['ativos_atrasados'] . ' PDI(s) ativo(s) com fim previsto ultrapassado', $pdiAtivosUrl]; }
if ($dados['necessidades_pendentes'] > 0) { $atencao[] = [$dados['necessidades_pendentes'] . ' necessidade(s) de treinamento pendente(s)', $necPendUrl]; }
if ($dados['gaps']['abertos'] > 0) { $atencao[] = [$dados['gaps']['abertos'] . ' GAP(s) aberto(s)', $gapAbertoUrl]; }
if ($dados['avaliacoes']['pendentes'] > 0) { $atencao[] = [$dados['avaliacoes']['pendentes'] . ' avaliação(ões) pendente(s)', $avUrl]; }
?>
<div class="p-4 md:p-6 space-y-6">
  <?php require __DIR__ . '/../_subnav.php'; ?>
  <div>
    <h1 class="text-2xl font-bold text-brand-black">Pessoas — Visão Geral</h1>
    <p class="text-sm text-gray-600">Panorama gerencial do Pilar de Pessoas.</p>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/visaoGeral" />
    <?php if (count($clientes) > 1): ?>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
      <select name="empresa_id" class="border border-gray-300 rounded-lg p-3 w-full">
        <option value="">Todas</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Período de</label>
      <input type="date" name="inicio" value="<?= htmlspecialchars($filters['inicio']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">até</label>
      <input type="date" name="fim" value="<?= htmlspecialchars($filters['fim']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
    </div>
    <?php if (!empty($departamentos)): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
      <select name="departamento_id" class="border border-gray-300 rounded-lg p-3 w-full">
        <option value="">Todos</option>
        <?php foreach ($departamentos as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= (int)($filters['departamento_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($setores)): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
      <select name="setor_id" class="border border-gray-300 rounded-lg p-3 w-full">
        <option value="">Todos</option>
        <?php foreach ($setores as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= (int)($filters['setor_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($funcoes)): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Função</label>
      <select name="funcao_id" class="border border-gray-300 rounded-lg p-3 w-full">
        <option value="">Todas</option>
        <?php foreach ($funcoes as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= (int)($filters['funcao_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Filtrar</button>
  </form>

  <p class="text-xs text-gray-500">Indicadores de <strong>situação atual</strong> não dependem do período; indicadores de <strong>eventos</strong> (finalizadas, resolvidos, concluídos, encaminhamentos) consideram apenas o período selecionado.</p>

  <section class="space-y-2">
    <h2 class="font-semibold">Colaboradores e avaliações</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
      <?= $card('Colaboradores ativos', $dados['colaboradores_ativos'], 'situação atual') ?>
      <?= $card('Avaliações pendentes', $dados['avaliacoes']['pendentes'], 'situação atual', $avUrl) ?>
      <?= $card('Avaliações em andamento', $dados['avaliacoes']['em_andamento'], 'situação atual', $avUrl) ?>
      <?= $card('Avaliações finalizadas', $dados['avaliacoes']['finalizadas'], 'no período') ?>
      <?= $card('Resultado médio', $mediaTxt, 'avaliações finalizadas no período') ?>
    </div>
  </section>

  <section class="bg-white shadow rounded-xl p-4">
    <h2 class="font-semibold mb-3">Distribuição de desempenho <span class="text-xs font-normal text-gray-500">(avaliações finalizadas no período)</span></h2>
    <div class="space-y-2">
      <?php foreach ($dados['distribuicao'] as $d): ?>
        <div class="flex items-center gap-3 text-sm">
          <div class="w-48 shrink-0"><?= htmlspecialchars($d['label']) ?></div>
          <div class="flex-1 bg-gray-100 rounded h-3 overflow-hidden"><div class="bg-brand-red h-3" style="width: <?= (int)round(($d['total'] / $maxFaixa) * 100) ?>%"></div></div>
          <div class="w-10 text-right font-medium"><?= (int)$d['total'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="space-y-2">
    <h2 class="font-semibold">GAPs</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <?= $card('GAPs abertos', $dados['gaps']['abertos'], 'situação atual', $gapAbertoUrl) ?>
      <?= $card('GAPs em tratamento', $dados['gaps']['em_tratamento'], 'situação atual', $gapTratUrl) ?>
      <?= $card('GAPs resolvidos', $dados['gaps']['resolvidos'], 'no período') ?>
      <?= $card('GAPs abertos sem ação', $dados['gaps']['abertos_sem_acao'], 'situação atual', $gapSemAcaoUrl) ?>
    </div>
  </section>

  <section class="space-y-2">
    <h2 class="font-semibold">Ações de melhoria e desenvolvimento</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3">
      <?= $card('Ações pendentes', $dados['acoes']['pendentes'], 'situação atual', $acaoPendUrl) ?>
      <?= $card('Ações em andamento', $dados['acoes']['em_andamento'], 'situação atual', $acaoAndUrl) ?>
      <?= $card('Ações concluídas', $dados['acoes']['concluidas'], 'no período') ?>
      <?= $card('Ações atrasadas', $dados['acoes']['vencidas'], 'prazo < hoje, não concluídas', $acaoVencUrl) ?>
      <?= $card('Ações → Plano de Ação', $dados['encaminhadas']['planos'], 'encaminhadas no período') ?>
      <?= $card('Ações → Treinamento', $dados['encaminhadas']['treinamentos'], 'encaminhadas no período') ?>
      <?= $card('Necessidades pendentes', $dados['necessidades_pendentes'], 'situação atual', $necPendUrl) ?>
    </div>
  </section>

  <section class="space-y-2">
    <h2 class="font-semibold">PDI</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <?= $card('PDIs em rascunho', $dados['pdis']['rascunho'], 'situação atual', $pdiRascUrl) ?>
      <?= $card('PDIs ativos', $dados['pdis']['ativos'], 'situação atual', $pdiAtivosUrl) ?>
      <?= $card('PDIs concluídos', $dados['pdis']['concluidos'], 'no período', $pdiConcUrl) ?>
      <?= $card('Objetivos vencidos', $dados['objetivos_vencidos'], 'PDIs ativos', $pdiAtivosUrl) ?>
    </div>
  </section>

  <section class="bg-white shadow rounded-xl p-4">
    <h2 class="font-semibold mb-2">Pontos de Atenção</h2>
    <?php if (empty($atencao)): ?>
      <div class="text-sm text-gray-500">Nenhum ponto de atenção no momento.</div>
    <?php else: ?>
      <ul class="space-y-1 text-sm">
        <?php foreach ($atencao as [$texto, $href]): ?>
          <li><?php if ($href !== ''): ?><a class="text-brand-pink hover:underline" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($texto) ?></a><?php else: ?><?= htmlspecialchars($texto) ?><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
