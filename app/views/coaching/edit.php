<?php /** @var array|null $item */ /** @var array $clientes */ ?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Editar Coaching') ?></h1>
    <a class="icon-btn icon-btn--muted icon-btn--lg" href="index.php?route=coaching/index" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
  </div>
  <?php if (!$item): ?>
    <div class="bg-red-100 border border-red-200 text-red-800 rounded p-4">Registro não encontrado.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-4">
      <form method="post" action="index.php?route=coaching/update" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm">Cliente</label>
            <select name="cliente_id" class="border rounded p-2 w-full" required>
              <?php foreach ($clientes as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$item['cliente_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['nome_empresa']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Título</label>
            <input class="border rounded p-2 w-full" type="text" name="titulo" value="<?= htmlspecialchars((string)$item['titulo']) ?>" required />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Coach</label>
            <input class="border rounded p-2 w-full" type="text" name="coach" value="<?= htmlspecialchars((string)($item['coach'] ?? '')) ?>" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Observações</label>
            <textarea class="border rounded p-2 w-full" name="observacoes" rows="3"><?= htmlspecialchars((string)($item['observacoes'] ?? '')) ?></textarea>
          </div>
          <div>
            <label class="block text-sm">Data início</label>
            <input class="border rounded p-2 w-full" type="datetime-local" name="data_inicio" value="<?= htmlspecialchars(str_replace(' ', 'T', (string)($item['data_inicio'] ?? ''))) ?>" required />
          </div>
          <div>
            <label class="block text-sm">Data fim</label>
            <input class="border rounded p-2 w-full" type="datetime-local" name="data_fim" value="<?= htmlspecialchars((string)($item['data_fim'] ? str_replace(' ', 'T', (string)$item['data_fim']) : '')) ?>" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Status</label>
            <select class="border rounded p-2 w-full" name="status">
              <?php foreach (['Planejado','Pendente','Andamento','Adiado','Finalizado'] as $status): ?>
                <option value="<?= $status ?>" <?= ((string)($item['status'] ?? '')) === $status ? 'selected' : '' ?>><?= $status ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
      </form>
    </div>
  <?php endif; ?>
</div>

