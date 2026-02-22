<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-2xl">
  <h1 class="text-2xl font-bold mb-4">Novo Plano de Ação</h1>
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
        <label class="block text-sm">Responsável</label>
        <input type="text" name="responsavel" required />
      </div>
      <div>
        <label class="block text-sm">Data de término</label>
        <input type="date" name="prazo" required />
      </div>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
</div>
