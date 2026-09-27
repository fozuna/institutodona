<?php use App\Core\DateHelper; ?>
<?php /** @var array $items */ /** @var array $clientes */ /** @var int $selectedEmpresa */ ?>
<?php
$statusLabels = \App\Models\PessoaCicloAvaliacaoModel::statusOptions();
$statusClasses = [
    'rascunho' => 'bg-gray-100 text-gray-700',
    'aberto' => 'bg-green-100 text-green-700',
    'encerrado' => 'bg-blue-100 text-blue-700',
];
?>
<div class="p-4 md:p-6 space-y-6">
  <?php $pessoasSubnavAtivo = 'avaliacoes'; require __DIR__ . '/../_subnav.php'; ?>
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Avaliações de Desempenho</h1>
      <p class="text-sm text-gray-600">Ciclos de avaliação: crie, selecione participantes, abra e acompanhe o progresso.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-red text-white" href="index.php?route=pessoas/cicloCreate&empresa_id=<?= (int)$selectedEmpresa ?>">Novo Ciclo</a>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/modelos&empresa_id=<?= (int)$selectedEmpresa ?>">Modelos de Avaliação</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/index" />
    <div class="md:col-span-3">
      <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
      <select name="empresa_id" class="border border-gray-300 rounded-lg p-3 w-full" onchange="this.form.submit()">
        <option value="">Selecione</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <?php if (!$selectedEmpresa): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Selecione uma Empresa para ver os ciclos de avaliação.</div>
  <?php elseif (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Nenhum ciclo cadastrado ainda para esta Empresa.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <?php foreach ($items as $c): ?>
        <?php
          $total = (int)$c['total_participantes'];
          $finalizadas = (int)$c['total_finalizadas'];
          $pct = $total > 0 ? round(($finalizadas / $total) * 100) : 0;
        ?>
        <a href="index.php?route=pessoas/cicloShow&id=<?= (int)$c['id'] ?>" class="bg-white shadow rounded-xl p-4 hover:shadow-md transition-shadow block">
          <div class="flex items-start justify-between gap-2 mb-2">
            <div class="font-semibold"><?= htmlspecialchars($c['nome']) ?></div>
            <span class="px-2 py-1 rounded text-xs shrink-0 <?= $statusClasses[$c['status']] ?? 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($statusLabels[$c['status']] ?? $c['status']) ?></span>
          </div>
          <div class="text-xs text-gray-500 mb-3"><?= htmlspecialchars($c['modelo_nome']) ?></div>
          <?php if (!empty($c['data_inicio']) || !empty($c['data_fim'])): ?>
            <div class="text-xs text-gray-500 mb-3">
              <?= !empty($c['data_inicio']) ? htmlspecialchars(DateHelper::formatDate((string)$c['data_inicio'])) : '—' ?>
              a
              <?= !empty($c['data_fim']) ? htmlspecialchars(DateHelper::formatDate((string)$c['data_fim'])) : '—' ?>
            </div>
          <?php endif; ?>
          <div class="text-sm mb-1"><?= $total ?> participante(s)</div>
          <?php if ($total > 0): ?>
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
              <div class="bg-brand-red h-2" style="width: <?= (int)$pct ?>%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1"><?= $finalizadas ?>/<?= $total ?> finalizadas (<?= (int)$pct ?>%)</div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
