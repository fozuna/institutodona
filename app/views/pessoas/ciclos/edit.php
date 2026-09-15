<?php /** @var array $ciclo */ /** @var array $modelosDisponiveis */ ?>
<div class="p-4 md:p-6 space-y-6 max-w-2xl">
  <h1 class="text-2xl font-bold text-brand-black">Editar Ciclo — <?= htmlspecialchars($ciclo['nome']) ?></h1>

  <form method="post" action="index.php?route=pessoas/cicloUpdate" class="bg-white shadow rounded-xl p-6 space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="id" value="<?= (int)$ciclo['id'] ?>" />
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Modelo de Avaliação</label>
      <select name="modelo_id" class="border border-gray-300 rounded-lg p-3 w-full" required>
        <?php foreach ($modelosDisponiveis as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= (int)$m['id'] === (int)$ciclo['modelo_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nome do ciclo</label>
      <input type="text" name="nome" value="<?= htmlspecialchars($ciclo['nome']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" required maxlength="180" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
      <textarea name="descricao" class="border border-gray-300 rounded-lg p-3 w-full" rows="2" maxlength="500"><?= htmlspecialchars((string)($ciclo['descricao'] ?? '')) ?></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Início (opcional)</label>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars((string)($ciclo['data_inicio'] ?? '')) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fim (opcional)</label>
        <input type="date" name="data_fim" value="<?= htmlspecialchars((string)($ciclo['data_fim'] ?? '')) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Salvar</button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/cicloShow&id=<?= (int)$ciclo['id'] ?>">Cancelar</a>
    </div>
  </form>
</div>
