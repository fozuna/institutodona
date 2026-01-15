<?php /** @var int $cliente */ /** @var array $clientes */ /** @var array $metodologias */ /** @var array $funcoes */ /** @var array $consultores */ ?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Aplicar nova tarefa</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4">
    <form method="post" action="index.php?route=clientes/attach" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <?php if ($cliente): ?>
        <input type="hidden" name="id_cliente" value="<?= (int)$cliente ?>" />
        <div class="text-xs text-gray-600">Empresa selecionada: 
          <?php foreach ($clientes as $c): if ((int)$c['id'] === (int)$cliente) { echo htmlspecialchars($c['nome_empresa']); break; } endforeach; ?>
        </div>
      <?php else: ?>
        <label class="block text-sm">Cliente</label>
        <select name="id_cliente" class="w-full" required>
          <option value="">— selecione —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <label class="block text-sm">Tarefa</label>
      <select name="id_metodologia" class="w-full" required>
        <?php foreach ($metodologias as $m): ?>
          <option value="<?= (int)$m['id'] ?>">[<?= htmlspecialchars($m['pilar_nome']) ?>] <?= htmlspecialchars($m['item_pilar']) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="block text-sm">Funções (pode selecionar várias)</label>
      <select name="funcao_ids[]" class="w-full" multiple size="8" required>
        <?php foreach ($funcoes as $fn): ?>
          <option value="<?= (int)$fn['id'] ?>"><?= htmlspecialchars(($fn['departamento'] ?? '') . ' / ' . ($fn['setor'] ?? '') . ' / ' . $fn['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="block text-sm">Data prevista</label>
      <input type="date" name="data_prevista" class="w-full" />
      <label class="block text-sm">Consultor</label>
      <select name="consultor_id" class="w-full">
        <option value="">—</option>
        <?php foreach ($consultores as $cons): ?>
          <option value="<?= (int)$cons['id'] ?>"><?= htmlspecialchars($cons['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="block text-sm">Status inicial</label>
      <select name="status" class="w-full">
        <option value="A Fazer">A Fazer</option>
        <option value="Em Andamento">Em Andamento</option>
        <option value="Concluído">Concluído</option>
        <option value="Pendente">Pendente</option>
      </select>
      <div class="flex items-center gap-3">
        <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">SALVAR</button>
        <button type="button" class="px-4 py-2 rounded bg-gray-200 text-brand-brown" onclick="history.back()">CANCELAR</button>
      </div>
    </form>
  </div>
</div>
