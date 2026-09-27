<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $items */ /** @var int $total */ /** @var int $page */ /** @var int $perPage */ /** @var array $qs */ /** @var array $f */
/** @var ?array $colaborador */ /** @var array $links */
$statusLabels = PessoasGestaoConfig::necessidadeStatusLabels();
$statusClasses = PessoasGestaoConfig::necessidadeStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$pessoasSubnavAtivo = 'necessidades';
$listaRota = 'pessoas/necessidades';
$listaRotulo = 'necessidade(s)';
$atalho = static fn(array $extra): string => 'index.php?' . http_build_query(array_merge(['route' => 'pessoas/necessidades'], array_diff_key($qs, ['status' => 1, 'page' => 1]), $extra));
?>
<div class="p-4 md:p-6 space-y-5">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <div>
    <h1 class="text-2xl font-bold text-brand-black">Necessidades de Treinamento</h1>
    <p class="text-sm text-gray-600">Necessidades registradas a partir das Ações de Melhoria. Para atender, abra a ação e vincule um treinamento existente.</p>
  </div>

  <div class="flex flex-wrap gap-2 text-sm" aria-label="Atalhos de filtro">
    <a class="px-3 py-1 rounded-full border <?= ($f['status'] ?? '') === 'pendente' ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['status' => 'pendente'])) ?>">Pendentes</a>
    <a class="px-3 py-1 rounded-full border bg-white text-gray-700" href="<?= htmlspecialchars($atalho([])) ?>">Todas</a>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/necessidades" />
    <?php require __DIR__ . '/../_filtros_escopo.php'; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fStatus">Status</label>
      <select id="fStatus" name="status" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($statusLabels as $k => $l): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= ($f['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 rounded-lg bg-brand-red text-white">Filtrar</button>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/necessidades">Limpar</a>
    </div>
  </form>

  <?php if (!empty($colaborador)): ?>
    <div class="text-sm">Colaborador: <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
      · <a class="text-brand-pink" href="<?= htmlspecialchars('index.php?' . http_build_query(array_merge(['route' => 'pessoas/necessidades'], array_diff_key($qs, ['colaborador_id' => 1, 'page' => 1])))) ?>">remover filtro</a>
      · <a class="text-brand-pink" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">abrir Central do Colaborador</a></div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">
      <?= ($f['status'] ?? '') === 'pendente' ? 'Nenhuma necessidade de treinamento pendente para os filtros selecionados.' : 'Nenhuma necessidade de treinamento encontrada para os filtros selecionados.' ?>
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-gray-600 border-b bg-gray-50">
          <tr><th class="p-3">Colaborador</th><th class="p-3">Necessidade</th><th class="p-3">Origem (ação)</th><th class="p-3">Status</th><th class="p-3">Treinamento</th><th class="p-3">Registrada</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $n): ?>
            <tr class="border-b last:border-0 align-top">
              <td class="p-3">
                <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$n['colaborador_id'] ?>"><?= htmlspecialchars($n['colaborador_nome']) ?></a>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($n['empresa_nome']) ?></div>
              </td>
              <td class="p-3">
                <div class="font-medium"><?= htmlspecialchars($n['titulo']) ?></div>
                <div class="text-xs text-gray-500">Prioridade: <?= htmlspecialchars($prioridadeLabels[$n['prioridade']] ?? $n['prioridade']) ?></div>
              </td>
              <td class="p-3 text-xs"><a class="text-brand-pink" href="index.php?route=pessoas/acaoShow&id=<?= (int)$n['acao_melhoria_id'] ?>"><?= htmlspecialchars(mb_strimwidth((string)($n['acao_titulo'] ?? 'Ação'), 0, 60, '…')) ?></a></td>
              <td class="p-3"><span class="px-2 py-1 rounded text-xs <?= $statusClasses[$n['status']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$n['status']] ?? $n['status']) ?></span></td>
              <td class="p-3 text-xs">
                <?php if (!empty($n['treinamento_id'])): ?>
                  <?= htmlspecialchars((string)$n['treinamento_nome']) ?><?php if (!empty($links['treinamento'])): ?> · <a class="text-brand-pink" href="index.php?route=treinamentos/show&id=<?= (int)$n['treinamento_id'] ?>">abrir</a><?php endif; ?>
                  <?php if (!empty($n['atendida_em'])): ?><div class="text-gray-500">Atendida em <?= htmlspecialchars(DateHelper::formatDate((string)$n['atendida_em'])) ?></div><?php endif; ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td class="p-3 text-xs"><?= htmlspecialchars(DateHelper::formatDate((string)$n['created_at'])) ?></td>
              <td class="p-3 text-right whitespace-nowrap"><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/acaoShow&id=<?= (int)$n['acao_melhoria_id'] ?>"><?= $n['status'] === 'pendente' ? 'Atender' : 'Abrir ação' ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../_paginacao.php'; ?>
  <?php endif; ?>
</div>
