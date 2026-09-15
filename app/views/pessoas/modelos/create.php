<?php /** @var array $clientes */ /** @var int $selectedEmpresa */ ?>
<div class="p-4 md:p-6 space-y-6 max-w-2xl">
  <h1 class="text-2xl font-bold text-brand-black">Novo Modelo de Avaliação</h1>

  <form method="post" action="index.php?route=pessoas/modeloStore" class="bg-white shadow rounded-xl p-6 space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
      <select name="empresa_id" class="border border-gray-300 rounded-lg p-3 w-full" required>
        <option value="">Selecione</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
      <input type="text" name="nome" class="border border-gray-300 rounded-lg p-3 w-full" placeholder="Ex.: Avaliação Semestral Administrativa" required maxlength="180" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
      <textarea name="descricao" class="border border-gray-300 rounded-lg p-3 w-full" rows="3" maxlength="500"></textarea>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Criar e adicionar grupos</button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/modelos&empresa_id=<?= (int)$selectedEmpresa ?>">Cancelar</a>
    </div>
  </form>
</div>
