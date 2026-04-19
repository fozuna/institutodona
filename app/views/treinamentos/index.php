<?php /** @var array $items */ /** @var array $clientes */ /** @var array $filters */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Pilar de Treinamentos') ?></h1>
      <p class="text-sm text-gray-600">Gestão completa de treinamentos, colaboradores, agendamentos e certificações.</p>
    </div>
    <div class="flex items-center gap-2">
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/create">Novo Treinamento</a>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/dashboard">Dashboard</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded p-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
    <input type="hidden" name="route" value="treinamentos/index" />
    <div class="md:col-span-4">
      <label class="block text-sm">Unidade</label>
      <select name="cliente_id" class="border rounded p-2 w-full">
        <option value="0">Todas</option>
        <?php foreach ($clientes as $cliente): ?>
          <option value="<?= (int)$cliente['id'] ?>" <?= ((int)($filters['cliente_id'] ?? 0) === (int)$cliente['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cliente['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-6">
      <label class="block text-sm">Busca</label>
      <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>" class="border rounded p-2 w-full" placeholder="Nome, objetivo ou fornecedor" />
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white w-full">Filtrar</button>
    </div>
  </form>

  <?php if (empty($items)): ?>
    <div class="bg-white shadow rounded p-8 text-center text-gray-500">
      Nenhum treinamento cadastrado até o momento.
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <?php foreach ($items as $item): ?>
        <div class="bg-white shadow rounded p-5 border border-gray-100">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-brand-black"><?= htmlspecialchars($item['nome']) ?></h2>
              <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($item['unidade_nome'] ?? '') ?> • <?= htmlspecialchars($item['departamento_nome'] ?? '') ?></p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700"><?= htmlspecialchars(\App\Models\TreinamentoModel::periodicidadeOptions()[$item['periodicidade'] ?? 'avulso'] ?? 'Avulso') ?></span>
          </div>
          <div class="text-sm text-gray-700 mt-3 line-clamp-3"><?= nl2br(htmlspecialchars((string)($item['objetivo'] ?? ''))) ?></div>
          <div class="grid grid-cols-2 gap-3 text-sm mt-4">
            <div class="rounded bg-gray-50 p-3">
              <div class="text-gray-500">Colaboradores</div>
              <div class="font-semibold"><?= (int)($item['total_colaboradores'] ?? 0) ?></div>
            </div>
            <div class="rounded bg-gray-50 p-3">
              <div class="text-gray-500">Concluídos</div>
              <div class="font-semibold"><?= (int)($item['total_concluidos'] ?? 0) ?></div>
            </div>
            <div class="rounded bg-gray-50 p-3">
              <div class="text-gray-500">Agendamentos</div>
              <div class="font-semibold"><?= (int)($item['total_agendamentos'] ?? 0) ?></div>
            </div>
            <div class="rounded bg-gray-50 p-3">
              <div class="text-gray-500">Fornecedor</div>
              <div class="font-semibold"><?= htmlspecialchars((string)($item['fornecedor'] ?? '—')) ?></div>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2 mt-4">
            <a class="px-3 py-2 rounded bg-brand-pink text-white" href="index.php?route=treinamentos/show&id=<?= (int)$item['id'] ?>">Abrir</a>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/edit&id=<?= (int)$item['id'] ?>">Editar</a>
            <form method="post" action="index.php?route=treinamentos/delete" onsubmit="return confirm('Excluir treinamento?');">
              <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <button class="px-3 py-2 rounded bg-red-100 text-red-700" type="submit">Excluir</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
