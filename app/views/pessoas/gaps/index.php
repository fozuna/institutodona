<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $items */ /** @var int $total */ /** @var int $page */ /** @var int $perPage */ /** @var array $qs */ /** @var array $f */ /** @var ?array $colaborador */
$statusLabels = PessoasGestaoConfig::gapStatusLabels();
$statusClasses = PessoasGestaoConfig::gapStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$pessoasSubnavAtivo = 'gaps';
$listaRota = 'pessoas/gaps';
$listaRotulo = 'GAP(s)';
$atalho = static fn(array $extra): string => 'index.php?' . http_build_query(array_merge(['route' => 'pessoas/gaps'], array_diff_key($qs, ['status' => 1, 'tratamento' => 1, 'page' => 1]), $extra));
?>
<div class="p-4 md:p-6 space-y-5">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <div>
    <h1 class="text-2xl font-bold text-brand-black">GAPs</h1>
    <p class="text-sm text-gray-600">Diferenças de desempenho registradas. GAPs abertos sem ação de melhoria exigem tratamento.</p>
  </div>

  <div class="flex flex-wrap gap-2 text-sm" aria-label="Atalhos de filtro">
    <a class="px-3 py-1 rounded-full border <?= ($f['status'] ?? '') === 'aberto' && ($f['tratamento'] ?? '') === 'sem_acao' ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['status' => 'aberto', 'tratamento' => 'sem_acao'])) ?>">Abertos sem ação</a>
    <a class="px-3 py-1 rounded-full border <?= ($f['status'] ?? '') === 'nao_resolvido' && empty($f['tratamento']) ? 'bg-brand-red text-white border-brand-red' : 'bg-white text-gray-700' ?>" href="<?= htmlspecialchars($atalho(['status' => 'nao_resolvido'])) ?>">Não resolvidos</a>
    <a class="px-3 py-1 rounded-full border bg-white text-gray-700" href="<?= htmlspecialchars($atalho([])) ?>">Todos</a>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/gaps" />
    <?php require __DIR__ . '/../_filtros_escopo.php'; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fStatus">Status</label>
      <select id="fStatus" name="status" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <option value="nao_resolvido" <?= ($f['status'] ?? '') === 'nao_resolvido' ? 'selected' : '' ?>>Não resolvidos</option>
        <?php foreach ($statusLabels as $k => $l): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= ($f['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fTrat">Tratamento</label>
      <select id="fTrat" name="tratamento" class="border border-gray-300 rounded-lg p-2 w-full">
        <option value="">Todos</option>
        <option value="com_acao" <?= ($f['tratamento'] ?? '') === 'com_acao' ? 'selected' : '' ?>>Com ação</option>
        <option value="sem_acao" <?= ($f['tratamento'] ?? '') === 'sem_acao' ? 'selected' : '' ?>>Sem ação</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 rounded-lg bg-brand-red text-white">Filtrar</button>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/gaps">Limpar</a>
    </div>
  </form>

  <?php if (!empty($colaborador)): ?>
    <div class="text-sm">Colaborador: <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
      · <a class="text-brand-pink" href="<?= htmlspecialchars('index.php?' . http_build_query(array_merge(['route' => 'pessoas/gaps'], array_diff_key($qs, ['colaborador_id' => 1, 'page' => 1])))) ?>">remover filtro</a>
      · <a class="text-brand-pink" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">abrir Central do Colaborador</a></div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">
      <?= ($f['status'] ?? '') === 'aberto' || ($f['status'] ?? '') === 'nao_resolvido' ? 'Nenhum GAP em aberto para os filtros selecionados.' : 'Nenhum GAP encontrado para os filtros selecionados.' ?>
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-gray-600 border-b bg-gray-50">
          <tr><th class="p-3">Colaborador</th><th class="p-3">GAP</th><th class="p-3">Origem</th><th class="p-3">Status</th><th class="p-3">Tratamento</th><th class="p-3">Registrado</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $g): ?>
            <?php $semAcao = (int)$g['acoes_total'] === 0; ?>
            <tr class="border-b last:border-0 align-top">
              <td class="p-3">
                <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$g['colaborador_id'] ?>"><?= htmlspecialchars($g['colaborador_nome']) ?></a>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($g['empresa_nome']) ?></div>
              </td>
              <td class="p-3">
                <div class="font-medium"><?= htmlspecialchars($g['titulo']) ?></div>
                <?php if (!empty($g['descricao'])): ?><div class="text-xs text-gray-500"><?= htmlspecialchars(mb_strimwidth((string)$g['descricao'], 0, 120, '…')) ?></div><?php endif; ?>
                <div class="text-xs text-gray-500">Prioridade: <?= htmlspecialchars($prioridadeLabels[$g['prioridade']] ?? $g['prioridade']) ?></div>
              </td>
              <td class="p-3 text-xs">
                <?php if ($g['origem'] === 'avaliacao'): ?>
                  Avaliação<?= !empty($g['ciclo_nome']) ? ' — ' . htmlspecialchars($g['ciclo_nome']) : '' ?>
                  <?php if (!empty($g['avaliacao_id']) && ($g['avaliacao_status'] ?? '') === 'finalizada'): ?>
                    <div><a class="text-brand-pink" href="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$g['avaliacao_id'] ?>">ver avaliação</a></div>
                  <?php endif; ?>
                <?php else: ?>Manual<?php endif; ?>
              </td>
              <td class="p-3"><span class="px-2 py-1 rounded text-xs <?= $statusClasses[$g['status']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$g['status']] ?? $g['status']) ?></span></td>
              <td class="p-3 text-xs">
                <?php if ($semAcao): ?>
                  <span class="px-2 py-1 rounded bg-red-50 text-red-700 border border-red-200">Sem ação</span>
                <?php else: ?>
                  <?= (int)$g['acoes_total'] ?> ação(ões)<?= (int)$g['acoes_ativas'] > 0 ? ' · ' . (int)$g['acoes_ativas'] . ' ativa(s)' : '' ?>
                  <div><a class="text-brand-pink" href="index.php?route=pessoas/acaoShow&id=<?= (int)$g['primeira_acao_id'] ?>">ver ação</a></div>
                <?php endif; ?>
              </td>
              <td class="p-3 text-xs"><?= htmlspecialchars(DateHelper::formatDate((string)$g['created_at'])) ?><?php if (!empty($g['resolvido_em'])): ?><div class="text-gray-500">Resolvido em <?= htmlspecialchars(DateHelper::formatDate((string)$g['resolvido_em'])) ?></div><?php endif; ?></td>
              <td class="p-3 text-right whitespace-nowrap"><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/gapShow&id=<?= (int)$g['id'] ?>"><?= $semAcao && $g['status'] !== 'resolvido' ? 'Tratar' : 'Abrir' ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require __DIR__ . '/../_paginacao.php'; ?>
  <?php endif; ?>
</div>
