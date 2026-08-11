<?php
  /** @var array $clientes */
  $selectedCliente = (int)($selectedCliente ?? 0);
  $backHref = $selectedCliente > 0
    ? 'index.php?route=clientes/show&id=' . $selectedCliente
    : 'index.php?route=tarefas/index';
?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Nova Tarefa') ?></h1>
    <a class="icon-btn icon-btn--muted icon-btn--lg" href="<?= $backHref ?>" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
  </div>
  <div class="bg-white shadow rounded p-4">
    <form method="post" action="index.php?route=tarefas/store" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <?php if ($selectedCliente > 0): ?>
        <input type="hidden" name="voltar_perfil" value="1" />
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm">Cliente</label>
          <select name="cliente_id" class="border rounded p-2 w-full" required>
            <option value="">—</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Título</label>
          <input class="border rounded p-2 w-full" type="text" name="titulo" required />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Descrição</label>
          <textarea class="border rounded p-2 w-full" name="descricao" rows="3"></textarea>
        </div>
        <div>
          <label class="block text-sm">Data início</label>
          <input class="border rounded p-2 w-full" type="datetime-local" name="data_inicio" required />
        </div>
        <div>
          <label class="block text-sm">Data fim</label>
          <input class="border rounded p-2 w-full" type="datetime-local" name="data_fim" />
        </div>
        <div>
          <label class="block text-sm">Prioridade</label>
          <select class="border rounded p-2 w-full" name="prioridade">
            <option value="baixa">Baixa</option>
            <option value="media" selected>Média</option>
            <option value="alta">Alta</option>
          </select>
        </div>
        <div>
          <label class="block text-sm">Status</label>
          <select class="border rounded p-2 w-full" name="status">
            <option value="Planejado" selected>Planejado</option>
            <option value="Pendente">Pendente</option>
            <option value="Andamento">Andamento</option>
            <option value="Adiado">Adiado</option>
            <option value="Finalizado">Finalizado</option>
          </select>
        </div>
      </div>
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
    </form>
  </div>
</div>

