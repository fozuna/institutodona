<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $items */ /** @var int $total */ /** @var int $page */ /** @var int $perPage */ /** @var array $qs */ /** @var array $f */
/** @var ?array $colaborador */ /** @var array $responsaveis */ /** @var array $links */
$statusLabels = PessoasGestaoConfig::acaoStatusLabels();
$statusClasses = PessoasGestaoConfig::acaoStatusClasses();
$pessoasSubnavAtivo = 'acoes';
$listaRota = 'pessoas/acoes';
$listaRotulo = 'ação(ões)';
$semFlags = array_diff_key($qs, ['status' => 1, 'atrasadas' => 1, 'com_plano' => 1, 'com_treinamento' => 1, 'page' => 1]);
$atalho = static fn(array $extra): string => 'index.php?' . http_build_query(array_merge(['route' => 'pessoas/acoes'], $semFlags, $extra));
?>
<div class="p-4 md:p-6 space-y-5">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <div>
    <h1 class="text-2xl font-bold text-brand-black">Ações de Melhoria</h1>
    <p class="text-sm text-gray-600">Ações do Pilar de Pessoas. Atrasada = prazo anterior a hoje e ação ainda pendente ou em andamento. Concluir uma ação não resolve o GAP automaticamente.</p>
  </div>

  <div class="flex flex-wrap gap-2 text-sm" aria-label="Atalhos de filtro">
    <a class="px-3 py-1 rounded-full border <?= !empty($f['atrasadas']) ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['atrasadas' => 1])) ?>">Atrasadas</a>
    <a class="px-3 py-1 rounded-full border <?= ($f['status'] ?? '') === 'ativas' && empty($f['atrasadas']) ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['status' => 'ativas'])) ?>">Pendentes e em andamento</a>
    <a class="px-3 py-1 rounded-full border bg-white text-gray-700" href="<?= htmlspecialchars($atalho([])) ?>">Todas</a>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/acoes" />
    <?php require __DIR__ . '/../_filtros_escopo.php'; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fStatus">Status</label>
      <select id="fStatus" name="status" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <option value="ativas" <?= ($f['status'] ?? '') === 'ativas' ? 'selected' : '' ?>>Pendentes e em andamento</option>
        <?php foreach ($statusLabels as $k => $l): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= ($f['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!empty($responsaveis)): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fResp">Responsável</label>
      <select id="fResp" name="responsavel_id" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($responsaveis as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (int)($f['responsavel_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <fieldset class="md:col-span-2 flex flex-wrap gap-3 text-sm">
      <legend class="sr-only">Situação</legend>
      <label class="inline-flex items-center gap-1"><input type="checkbox" name="atrasadas" value="1" <?= !empty($f['atrasadas']) ? 'checked' : '' ?> /> Atrasadas</label>
      <label class="inline-flex items-center gap-1"><input type="checkbox" name="com_plano" value="1" <?= !empty($f['com_plano']) ? 'checked' : '' ?> /> Com Plano de Ação</label>
      <label class="inline-flex items-center gap-1"><input type="checkbox" name="com_treinamento" value="1" <?= !empty($f['com_treinamento']) ? 'checked' : '' ?> /> Com Treinamento</label>
    </fieldset>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 rounded-lg bg-brand-red text-white">Filtrar</button>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/acoes">Limpar</a>
    </div>
  </form>

  <?php if (!empty($colaborador)): ?>
    <div class="text-sm">Colaborador: <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
      · <a class="text-brand-pink" href="<?= htmlspecialchars('index.php?' . http_build_query(array_merge(['route' => 'pessoas/acoes'], array_diff_key($qs, ['colaborador_id' => 1, 'page' => 1])))) ?>">remover filtro</a>
      · <a class="text-brand-pink" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">abrir Central do Colaborador</a></div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">
      <?= !empty($f['atrasadas']) ? 'Nenhuma ação atrasada para os filtros selecionados.' : 'Nenhuma ação de melhoria encontrada para os filtros selecionados.' ?>
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-gray-600 border-b bg-gray-50">
          <tr><th class="p-3">Colaborador</th><th class="p-3">Ação</th><th class="p-3">Responsável</th><th class="p-3">Status</th><th class="p-3">Prazo</th><th class="p-3">Encaminhamentos</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $a): ?>
            <tr class="border-b last:border-0 align-top <?= (int)$a['vencida'] === 1 ? 'bg-red-50/40' : '' ?>">
              <td class="p-3">
                <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$a['colaborador_id'] ?>"><?= htmlspecialchars($a['colaborador_nome']) ?></a>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($a['empresa_nome']) ?></div>
              </td>
              <td class="p-3">
                <div class="font-medium"><?= htmlspecialchars($a['titulo']) ?></div>
                <?php if (!empty($a['gap_id'])): ?>
                  <div class="text-xs">GAP: <a class="text-brand-pink" href="index.php?route=pessoas/gapShow&id=<?= (int)$a['gap_id'] ?>"><?= htmlspecialchars(mb_strimwidth((string)$a['gap_titulo'], 0, 60, '…')) ?></a></div>
                <?php endif; ?>
                <div class="text-xs text-gray-500">Criada em <?= htmlspecialchars(DateHelper::formatDate((string)$a['created_at'])) ?></div>
              </td>
              <td class="p-3 text-xs"><?= htmlspecialchars((string)($a['responsavel_nome'] ?? 'Não definido')) ?></td>
              <td class="p-3"><span class="px-2 py-1 rounded text-xs <?= $statusClasses[$a['status']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$a['status']] ?? $a['status']) ?></span></td>
              <td class="p-3 text-xs">
                <?= !empty($a['prazo']) ? htmlspecialchars(DateHelper::formatDate((string)$a['prazo'])) : '—' ?>
                <?php if ((int)$a['vencida'] === 1): ?><div><span class="px-2 py-0.5 rounded bg-red-100 text-red-700 border border-red-200 font-semibold">Atrasada</span></div><?php endif; ?>
              </td>
              <td class="p-3 text-xs space-y-1">
                <?php if (!empty($a['plano_task_id'])): ?>
                  <div>Plano de Ação: <?= htmlspecialchars((string)$a['plano_status']) ?><?php if (!empty($links['plano'])): ?> · <a class="text-brand-pink" href="index.php?route=planoacao/show&id=<?= (int)$a['plano_task_id'] ?>">abrir</a><?php endif; ?></div>
                <?php endif; ?>
                <?php if ((int)$a['treinamentos_total'] > 0): ?><div>Treinamento: <?= (int)$a['treinamentos_total'] ?> vinculado(s)</div><?php endif; ?>
                <?php if ((int)$a['necessidades_pendentes'] > 0): ?><div><?= (int)$a['necessidades_pendentes'] ?> necessidade(s) de treinamento pendente(s)</div><?php endif; ?>
                <?php if (empty($a['plano_task_id']) && (int)$a['treinamentos_total'] === 0 && (int)$a['necessidades_pendentes'] === 0): ?><span class="text-gray-400">Sem encaminhamento</span><?php endif; ?>
              </td>
              <td class="p-3 text-right whitespace-nowrap"><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/acaoShow&id=<?= (int)$a['id'] ?>">Abrir</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../_paginacao.php'; ?>
  <?php endif; ?>
</div>
