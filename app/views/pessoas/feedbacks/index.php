<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $items */ /** @var int $total */ /** @var int $page */ /** @var int $perPage */ /** @var array $qs */ /** @var array $f */
/** @var ?array $colaborador */ /** @var array $links */
$tipoLabels = PessoasGestaoConfig::feedbackTipoLabels();
$tipoClasses = PessoasGestaoConfig::feedbackTipoClasses();
$pessoasSubnavAtivo = 'feedbacks';
$listaRota = 'pessoas/feedbacks';
$listaRotulo = 'feedback(s)';
$atalho = static fn(array $extra): string => 'index.php?' . http_build_query(array_merge(['route' => 'pessoas/feedbacks'], array_diff_key($qs, ['tipo' => 1, 'page' => 1]), $extra));
?>
<div class="p-4 md:p-6 space-y-5">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Feedbacks</h1>
      <p class="text-sm text-gray-600">Registro histórico de gestão — feedbacks positivos e de melhoria registrados para os colaboradores.</p>
    </div>
    <a class="px-4 py-2 rounded-lg bg-brand-red text-white text-sm font-semibold shrink-0" href="index.php?route=pessoas/feedbackCreateForm">+ Novo Feedback</a>
  </div>

  <div class="flex flex-wrap gap-2 text-sm" aria-label="Atalhos de filtro">
    <a class="px-3 py-1 rounded-full border <?= ($f['tipo'] ?? '') === 'positivo' ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['tipo' => 'positivo'])) ?>">Positivos</a>
    <a class="px-3 py-1 rounded-full border <?= ($f['tipo'] ?? '') === 'melhoria' ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['tipo' => 'melhoria'])) ?>">De melhoria</a>
    <a class="px-3 py-1 rounded-full border bg-white text-gray-700" href="<?= htmlspecialchars($atalho([])) ?>">Todos</a>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/feedbacks" />
    <?php require __DIR__ . '/../_filtros_escopo.php'; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fTipo">Tipo</label>
      <select id="fTipo" name="tipo" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($tipoLabels as $k => $l): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= ($f['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 rounded-lg bg-brand-red text-white">Filtrar</button>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/feedbacks">Limpar</a>
    </div>
  </form>

  <?php if (!empty($colaborador)): ?>
    <div class="text-sm">Colaborador: <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
      · <a class="text-brand-pink" href="<?= htmlspecialchars('index.php?' . http_build_query(array_merge(['route' => 'pessoas/feedbacks'], array_diff_key($qs, ['colaborador_id' => 1, 'page' => 1])))) ?>">remover filtro</a>
      · <a class="text-brand-pink" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">abrir Central do Colaborador</a></div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Nenhum feedback encontrado para os filtros selecionados.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-gray-600 border-b bg-gray-50">
          <tr><th class="p-3">Colaborador</th><th class="p-3">Feedback</th><th class="p-3">Tipo</th><th class="p-3">Contexto</th><th class="p-3">Registrado</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $fb): ?>
            <tr class="border-b last:border-0 align-top">
              <td class="p-3">
                <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$fb['colaborador_id'] ?>"><?= htmlspecialchars($fb['colaborador_nome']) ?></a>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($fb['empresa_nome']) ?></div>
              </td>
              <td class="p-3">
                <div class="font-medium"><?= htmlspecialchars($fb['titulo']) ?></div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars(mb_strimwidth((string)$fb['descricao'], 0, 120, '…')) ?></div>
              </td>
              <td class="p-3"><span class="px-2 py-1 rounded text-xs <?= $tipoClasses[$fb['tipo']] ?? '' ?>"><?= htmlspecialchars($tipoLabels[$fb['tipo']] ?? $fb['tipo']) ?></span></td>
              <td class="p-3 text-xs">
                <?php if (!empty($fb['gap_id'])): ?>
                  <div><?php if (!empty($links['gaps'])): ?><a class="text-brand-pink" href="index.php?route=pessoas/gapShow&id=<?= (int)$fb['gap_id'] ?>">GAP: <?= htmlspecialchars(mb_strimwidth((string)$fb['gap_titulo'], 0, 30, '…')) ?></a><?php else: ?>GAP: <?= htmlspecialchars(mb_strimwidth((string)$fb['gap_titulo'], 0, 30, '…')) ?><?php endif; ?></div>
                <?php endif; ?>
                <?php if (!empty($fb['avaliacao_id'])): ?>
                  <div>Avaliação<?= !empty($fb['ciclo_nome']) ? ' — ' . htmlspecialchars($fb['ciclo_nome']) : '' ?></div>
                <?php endif; ?>
                <?php if (empty($fb['gap_id']) && empty($fb['avaliacao_id'])): ?>
                  <span class="text-gray-400">Independente</span>
                <?php endif; ?>
              </td>
              <td class="p-3 text-xs">
                <?= htmlspecialchars(DateHelper::formatDate((string)$fb['data_feedback'])) ?>
                <?php if (!empty($fb['registrado_por_nome'])): ?><div class="text-gray-500">por <?= htmlspecialchars($fb['registrado_por_nome']) ?></div><?php endif; ?>
              </td>
              <td class="p-3 text-right whitespace-nowrap"><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/feedbackShow&id=<?= (int)$fb['id'] ?>">Abrir</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../_paginacao.php'; ?>
  <?php endif; ?>
</div>
