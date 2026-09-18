<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $items */ /** @var int $total */ /** @var int $page */ /** @var int $perPage */ /** @var array $filters */ /** @var array $clientes */ /** @var int $selectedEmpresa */
$statusLabels = PessoasGestaoConfig::pdiStatusLabels();
$statusClasses = PessoasGestaoConfig::pdiStatusClasses();
$pages = max(1, (int)ceil($total / max(1, $perPage)));
$qs = static function (array $extra) use ($filters, $selectedEmpresa): string {
    $base = ['route' => 'pessoas/pdiIndex', 'empresa_id' => $selectedEmpresa ?: '', 'status' => $filters['status'], 'q' => $filters['q'], 'inicio' => $filters['inicio'], 'fim' => $filters['fim']];
    return 'index.php?' . http_build_query(array_filter(array_merge($base, $extra), static fn($v) => $v !== '' && $v !== null));
};
?>
<div class="p-4 md:p-6 space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-brand-black">PDI — Plano de Desenvolvimento Individual</h1>
    <p class="text-sm text-gray-600">Acompanhe o desenvolvimento de cada colaborador. Para criar um PDI, abra o Histórico do colaborador.</p>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/pdiIndex" />
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Colaborador</label>
      <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" placeholder="Nome" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select name="status" class="border border-gray-300 rounded-lg p-3 w-full">
        <option value="">Todos</option>
        <?php foreach ($statusLabels as $k => $l): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Início a partir de</label>
      <input type="date" name="inicio" value="<?= htmlspecialchars($filters['inicio']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Início até</label>
      <input type="date" name="fim" value="<?= htmlspecialchars($filters['fim']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
    </div>
    <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Filtrar</button>
  </form>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Nenhum PDI encontrado.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-gray-600 border-b">
          <tr><th class="p-3">Colaborador</th><th class="p-3">Título</th><th class="p-3">Período</th><th class="p-3">Status</th><th class="p-3">Progresso do PDI</th><th class="p-3">Prazo</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p): ?>
            <tr class="border-b last:border-0">
              <td class="p-3"><?= htmlspecialchars($p['colaborador_nome']) ?></td>
              <td class="p-3 font-medium"><?= htmlspecialchars($p['titulo']) ?></td>
              <td class="p-3 text-xs text-gray-600"><?= !empty($p['data_inicio']) ? htmlspecialchars(DateHelper::formatDate((string)$p['data_inicio'])) : '—' ?> a <?= !empty($p['data_fim_prevista']) ? htmlspecialchars(DateHelper::formatDate((string)$p['data_fim_prevista'])) : '—' ?></td>
              <td class="p-3"><span class="px-2 py-1 rounded text-xs <?= $statusClasses[$p['status']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$p['status']] ?? $p['status']) ?></span></td>
              <td class="p-3">
                <div class="w-28 bg-gray-100 rounded-full h-2 overflow-hidden"><div class="bg-brand-red h-2" style="width: <?= (int)round($p['progresso']) ?>%"></div></div>
                <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars(PessoasGestaoConfig::formatProgresso((float)$p['progresso'])) ?> (<?= (int)$p['objetivos_concluidos'] ?>/<?= (int)$p['objetivos_validos'] ?>)</div>
              </td>
              <td class="p-3 text-xs"><?= !empty($p['data_fim_prevista']) ? htmlspecialchars(DateHelper::formatDate((string)$p['data_fim_prevista'])) : '—' ?><?php if ((int)$p['objetivos_vencidos'] > 0): ?><div class="text-red-600"><?= (int)$p['objetivos_vencidos'] ?> objetivo(s) vencido(s)</div><?php endif; ?></td>
              <td class="p-3"><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/pdiShow&id=<?= (int)$p['id'] ?>">Abrir</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600"><?= (int)$total ?> PDI(s) — página <?= (int)$page ?> de <?= (int)$pages ?></span>
        <div class="flex gap-2">
          <?php if ($page > 1): ?><a class="px-3 py-2 rounded bg-gray-200" href="<?= htmlspecialchars($qs(['page' => $page - 1])) ?>">Anterior</a><?php endif; ?>
          <?php if ($page < $pages): ?><a class="px-3 py-2 rounded bg-gray-200" href="<?= htmlspecialchars($qs(['page' => $page + 1])) ?>">Próxima</a><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
