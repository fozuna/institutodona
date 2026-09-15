<?php /** @var array $items */ /** @var array $clientes */ /** @var int $selectedEmpresa */ ?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Modelos de Avaliação</h1>
      <p class="text-sm text-gray-600">Pergunte por grupos, defina pesos e reaproveite o mesmo modelo em vários ciclos.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-red text-white" href="index.php?route=pessoas/modeloCreate&empresa_id=<?= (int)$selectedEmpresa ?>">Novo Modelo</a>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/index&empresa_id=<?= (int)$selectedEmpresa ?>">Ciclos de Avaliação</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded-xl p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="route" value="pessoas/modelos" />
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
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Selecione uma Empresa para ver os modelos.</div>
  <?php elseif (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">Nenhum modelo cadastrado ainda para esta Empresa.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Nome</th>
            <th class="p-3">Grupos</th>
            <th class="p-3">Perguntas</th>
            <th class="p-3">Ciclos</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $m): ?>
            <tr class="border-b">
              <td class="p-3">
                <div class="font-semibold"><?= htmlspecialchars($m['nome']) ?></div>
                <?php if (!empty($m['descricao'])): ?><div class="text-xs text-gray-500"><?= htmlspecialchars($m['descricao']) ?></div><?php endif; ?>
              </td>
              <td class="p-3"><?= (int)$m['total_grupos'] ?></td>
              <td class="p-3"><?= (int)$m['total_perguntas'] ?></td>
              <td class="p-3"><?= (int)$m['total_ciclos'] ?></td>
              <td class="p-3">
                <span class="px-2 py-1 rounded text-xs <?= (int)$m['ativo'] === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= (int)$m['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></span>
              </td>
              <td class="p-3 text-right whitespace-nowrap">
                <a class="text-brand-pink font-semibold" href="index.php?route=pessoas/modeloEdit&id=<?= (int)$m['id'] ?>">Editar</a>
                <form method="post" action="index.php?route=pessoas/modeloToggleAtivo" class="inline">
                  <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
                  <button type="submit" class="ml-3 text-brand-brown font-semibold"><?= (int)$m['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
