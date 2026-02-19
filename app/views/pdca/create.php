<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-2xl">
  <h1 class="text-2xl font-bold mb-4">Nova Tarefa (PDCA)</h1>
  <form method="post" action="index.php?route=pdca/store" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="id_cliente">
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Título</label>
      <input type="text" name="titulo" required />
    </div>
    <div>
      <label class="block text-sm">Descrição</label>
      <textarea name="descricao" rows="3"></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm">Meta (quantitativa/qualitativa)</label>
        <input type="number" step="0.01" name="meta_valor" />
      </div>
      <div>
        <label class="block text-sm">Indicador/Unidade</label>
        <input type="text" name="meta_unidade" />
      </div>
      <div>
        <label class="block text-sm">Prazo</label>
        <input type="date" name="prazo" />
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm">Responsável</label>
        <input type="text" name="responsavel" />
      </div>
      <div>
        <label class="block text-sm">Fase</label>
        <select name="fase">
          <option value="PLAN">Planejar</option>
          <option value="DO">Executar</option>
          <option value="CHECK">Verificar</option>
          <option value="ACT">Agir</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Progresso (%)</label>
        <input type="number" name="progresso" min="0" max="100" value="0" />
      </div>
    </div>
    <div>
      <label class="block text-sm">Status</label>
      <select name="status">
        <option value="A Fazer">A Fazer</option>
        <option value="Em Andamento">Em Andamento</option>
        <option value="Concluído">Concluído</option>
        <option value="Pendente">Pendente</option>
      </select>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
</div>
