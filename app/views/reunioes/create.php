<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Nova Reunião') ?></h1>
    <a class="icon-btn icon-btn--muted icon-btn--lg" href="index.php?route=reunioes/index" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
  </div>
  <div class="bg-white shadow rounded p-4">
    <form method="post" action="index.php?route=reunioes/store" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm">Cliente</label>
          <select name="cliente_id" class="border rounded p-2 w-full" required>
            <option value="">—</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Título</label>
          <input class="border rounded p-2 w-full" type="text" name="titulo" required />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Local</label>
          <input class="border rounded p-2 w-full" type="text" name="local" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Pauta</label>
          <textarea class="border rounded p-2 w-full" name="pauta" rows="3"></textarea>
        </div>
        <div>
          <label class="block text-sm">Data início</label>
          <input class="border rounded p-2 w-full" type="datetime-local" name="data_inicio" required />
        </div>
        <div>
          <label class="block text-sm">Data fim</label>
          <input class="border rounded p-2 w-full" type="datetime-local" name="data_fim" />
        </div>
        <div class="md:col-span-2">
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

