<?php /** @var array $clientes */ /** @var int $selectedEmpresa */ /** @var array $modelosDisponiveis */ ?>
<div class="p-4 md:p-6 space-y-6 max-w-2xl">
  <h1 class="text-2xl font-bold text-brand-black">Novo Ciclo de Avaliação</h1>

  <form method="post" action="index.php?route=pessoas/cicloStore" class="bg-white shadow rounded-xl p-6 space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
      <select name="empresa_id" id="cicloEmpresaSelect" class="border border-gray-300 rounded-lg p-3 w-full" required onchange="location.href='index.php?route=pessoas/cicloCreate&empresa_id='+this.value">
        <option value="">Selecione</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Modelo de Avaliação</label>
      <?php if (empty($modelosDisponiveis)): ?>
        <p class="text-sm text-amber-700">Nenhum modelo ativo para esta empresa. <a class="underline" href="index.php?route=pessoas/modeloCreate&empresa_id=<?= (int)$selectedEmpresa ?>">Crie um modelo primeiro</a>.</p>
      <?php else: ?>
        <select name="modelo_id" class="border border-gray-300 rounded-lg p-3 w-full" required>
          <option value="">Selecione</option>
          <?php foreach ($modelosDisponiveis as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nome do ciclo</label>
      <input type="text" name="nome" class="border border-gray-300 rounded-lg p-3 w-full" placeholder="Ex.: Avaliação Semestral — 1º Semestre/2026" required maxlength="180" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
      <textarea name="descricao" class="border border-gray-300 rounded-lg p-3 w-full" rows="2" maxlength="500"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Início (opcional)</label>
        <input type="date" name="data_inicio" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fim (opcional)</label>
        <input type="date" name="data_fim" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white" <?= empty($modelosDisponiveis) ? 'disabled' : '' ?>>Criar ciclo</button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/index&empresa_id=<?= (int)$selectedEmpresa ?>">Cancelar</a>
    </div>
  </form>
</div>
