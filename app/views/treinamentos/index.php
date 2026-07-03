<?php use App\Core\DateHelper; ?>
<?php /** @var array $items */ /** @var array $clientes */ /** @var array $filters */ ?>
<?php
$page = max(1, (int)($page ?? 1));
$per = max(1, (int)($per ?? 10));
$total = (int)($total ?? 0);
$totalPages = max(1, (int)($totalPages ?? 1));
$visibleFrom = $total > 0 ? (($page - 1) * $per) + 1 : 0;
$visibleTo = min($total, $page * $per);
$statusClasses = [
    'success' => 'bg-green-100 text-green-700',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-blue-100 text-blue-700',
    'neutral' => 'bg-gray-100 text-gray-700',
];
$buildIndexUrl = static function (array $overrides = []) use ($filters, $per): string {
    $params = [
        'route' => 'treinamentos/index',
        'cliente_id' => (int)($filters['cliente_id'] ?? 0),
        'q' => trim((string)($filters['q'] ?? '')),
        'per' => $per,
    ];
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '' || $value === []) {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }
    if (empty($params['cliente_id'])) {
        unset($params['cliente_id']);
    }
    if (empty($params['q'])) {
        unset($params['q']);
    }
    if ((int)($params['per'] ?? 10) === 10) {
        unset($params['per']);
    }
    if ((int)($params['page'] ?? 1) <= 1) {
        unset($params['page']);
    }
    return 'index.php?' . http_build_query($params);
};
$pageWindowStart = max(1, $page - 2);
$pageWindowEnd = min($totalPages, $page + 2);
?>
<div class="p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Pilar de Treinamentos') ?></h1>
      <p class="text-sm text-gray-600">Listagem enxuta com foco em nome, data relevante, status, progresso e ações rápidas.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/create">Novo Treinamento</a>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/dashboard">Dashboard</a>
    </div>
  </div>

  <form method="get" action="index.php" id="treinamentosFiltersForm" class="bg-white shadow rounded p-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
    <input type="hidden" name="route" value="treinamentos/index" />
    <div class="md:col-span-4">
      <label class="block text-sm">Unidade</label>
      <select name="cliente_id" id="treinamentosClienteFilter" class="border rounded p-2 w-full">
        <option value="0">Todas</option>
        <?php foreach ($clientes as $cliente): ?>
          <option value="<?= (int)$cliente['id'] ?>" <?= ((int)($filters['cliente_id'] ?? 0) === (int)$cliente['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cliente['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-5">
      <label class="block text-sm">Busca</label>
      <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>" class="border rounded p-2 w-full" placeholder="Nome, objetivo ou fornecedor" />
    </div>
    <div class="md:col-span-1">
      <label class="block text-sm">Por página</label>
      <select name="per" id="treinamentosPerPage" class="border rounded p-2 w-full">
        <?php foreach ([5, 10, 15, 20, 25] as $option): ?>
          <option value="<?= $option ?>" <?= $per === $option ? 'selected' : '' ?>><?= $option ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2 flex items-end gap-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white w-full">Filtrar</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown whitespace-nowrap" href="index.php?route=treinamentos/index">Limpar</a>
    </div>
  </form>

  <div class="bg-white shadow rounded p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="text-sm text-gray-600">
      Exibindo <?= (int)$visibleFrom ?>-<?= (int)$visibleTo ?> de <?= (int)$total ?> treinamento(s)
    </div>
    <div class="text-sm text-gray-500">
      Página <?= (int)$page ?> de <?= (int)$totalPages ?>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded p-8 text-center text-gray-500">
      Nenhum treinamento cadastrado até o momento.
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded divide-y divide-gray-100 overflow-hidden">
      <?php foreach ($items as $item): ?>
        <?php
        $statusVariant = (string)($item['status_variant'] ?? 'neutral');
        $statusClass = $statusClasses[$statusVariant] ?? $statusClasses['neutral'];
        $periodicidade = \App\Models\TreinamentoModel::periodicidadeOptions()[$item['periodicidade'] ?? 'avulso'] ?? 'Avulso';
        $dataLabel = (string)($item['data_referencia_rotulo'] ?? 'Data');
        $dataValue = !empty($item['data_referencia'])
            ? DateHelper::formatDateTime((string)$item['data_referencia'])
            : 'Sem agenda';
        ?>
        <div class="p-4 md:p-5 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-lg font-semibold text-brand-black"><?= htmlspecialchars($item['nome']) ?></h2>
              <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700"><?= htmlspecialchars($periodicidade) ?></span>
              <span class="text-xs px-3 py-1 rounded-full font-semibold <?= $statusClass ?>"><?= htmlspecialchars((string)($item['status_resumo'] ?? 'Planejamento')) ?></span>
            </div>
            <p class="text-sm text-gray-600 mt-1">
              <?= htmlspecialchars((string)($item['unidade_nome'] ?? '')) ?>
              <?php if (!empty($item['departamento_nome'])): ?>
                • <?= htmlspecialchars((string)$item['departamento_nome']) ?>
              <?php endif; ?>
            </p>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
              <div class="rounded border border-gray-100 bg-gray-50 px-3 py-2">
                <div class="text-gray-500"><?= htmlspecialchars($dataLabel) ?></div>
                <div class="font-semibold text-brand-black"><?= htmlspecialchars($dataValue) ?></div>
              </div>
              <div class="rounded border border-gray-100 bg-gray-50 px-3 py-2">
                <div class="text-gray-500">Progresso</div>
                <div class="font-semibold text-brand-black"><?= (int)($item['progresso_pct'] ?? 0) ?>%</div>
              </div>
              <div class="rounded border border-gray-100 bg-gray-50 px-3 py-2">
                <div class="text-gray-500">Conclusão</div>
                <div class="font-semibold text-brand-black"><?= htmlspecialchars((string)($item['progresso_resumo'] ?? 'Sem colaboradores vinculados')) ?></div>
              </div>
            </div>
          </div>
          <div class="w-full xl:w-72 space-y-3">
            <div>
              <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Andamento</span>
                <span><?= (int)($item['progresso_pct'] ?? 0) ?>%</span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-brand-red" style="width: <?= max(0, min(100, (int)($item['progresso_pct'] ?? 0))) ?>%;"></div>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
              <a class="px-3 py-2 rounded bg-brand-pink text-white" href="index.php?route=treinamentos/show&id=<?= (int)$item['id'] ?>">Abrir</a>
              <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/edit&id=<?= (int)$item['id'] ?>">Editar</a>
              <form method="post" action="index.php?route=treinamentos/delete" onsubmit="return confirm('Excluir treinamento?');">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
                <button class="px-3 py-2 rounded bg-red-100 text-red-700" type="submit">Excluir</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex items-center gap-2">
        <?php if ($page > 1): ?>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($buildIndexUrl(['page' => $page - 1])) ?>">Anterior</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($buildIndexUrl(['page' => $page + 1])) ?>">Próxima</a>
        <?php endif; ?>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <?php for ($current = $pageWindowStart; $current <= $pageWindowEnd; $current++): ?>
          <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $current])) ?>" class="px-3 py-1 rounded <?= $current === $page ? 'bg-brand-red text-white' : 'bg-gray-200 text-brand-brown' ?>">
            <?= (int)$current ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<script>
  (function() {
    const form = document.getElementById('treinamentosFiltersForm');
    if (!form) {
      return;
    }
    const cliente = document.getElementById('treinamentosClienteFilter');
    const perPage = document.getElementById('treinamentosPerPage');
    cliente?.addEventListener('change', function() {
      form.submit();
    });
    perPage?.addEventListener('change', function() {
      form.submit();
    });
  })();
</script>
