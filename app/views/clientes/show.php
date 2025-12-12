<?php /** @var array $item */ /** @var array $apps */ /** @var array $metodologias */ ?>
<div class="p-6">
  <div class="mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Perfil do Cliente</h1>
    <div class="mt-2 bg-white shadow rounded p-4">
      <?php if (!empty($item['logo_path'])): ?>
        <div class="mb-3">
          <img src="../<?= htmlspecialchars($item['logo_path']) ?>" alt="Logo do cliente" class="h-12 w-auto" />
        </div>
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-500">Empresa</div>
          <div class="font-semibold"><?= htmlspecialchars($item['nome_empresa'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">CNPJ</div>
          <div class="font-semibold"><?= htmlspecialchars($item['CNPJ'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Contato</div>
          <div class="font-semibold"><?= htmlspecialchars($item['contato'] ?? '') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
      <div class="bg-white shadow rounded">
        <div class="px-4 py-3 border-b font-semibold">Tarefas aplicadas</div>
        <div class="p-4">
          <?php if (empty($apps)): ?>
            <div class="text-sm text-gray-600">Nenhuma metodologia aplicada ainda.</div>
          <?php else: ?>
          <table class="min-w-full">
            <thead>
              <tr class="text-left border-b">
                <th class="p-3">Pilar</th>
                <th class="p-3">Tarefa</th>
                <th class="p-3">Prevista</th>
                <th class="p-3">Consultor</th>
                <th class="p-3">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($apps as $a): ?>
                <tr class="border-b">
                  <td class="p-3"><?= htmlspecialchars($a['pilar_nome']) ?></td>
                  <td class="p-3"><a class="text-brand-red hover:underline" href="index.php?route=aplicacoes/show&id=<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['item_pilar']) ?></a></td>
                  <td class="p-3"><?= htmlspecialchars($a['data_prevista'] ?? '') ?></td>
                  <td class="p-3"><?= htmlspecialchars($a['consultor_nome'] ?? '') ?></td>
                  <td class="p-3"><?= htmlspecialchars($a['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div>
      <div class="bg-white shadow rounded">
        <div class="px-4 py-3 border-b font-semibold">Aplicar nova tarefa</div>
        <div class="p-4">
          <form method="post" action="index.php?route=clientes/attach" class="space-y-3">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
            <input type="hidden" name="id_cliente" value="<?= (int)$item['id'] ?>" />
            <label class="block text-sm">Tarefa</label>
            <select name="id_metodologia" class="w-full">
              <?php foreach ($metodologias as $m): ?>
                <option value="<?= (int)$m['id'] ?>">[<?= htmlspecialchars($m['pilar_nome']) ?>] <?= htmlspecialchars($m['item_pilar']) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="block text-sm">Data prevista</label>
            <input type="date" name="data_prevista" class="w-full" />
            <label class="block text-sm">Consultor</label>
            <select name="consultor_id" class="w-full">
              <option value="">—</option>
              <?php foreach ((new \App\Models\ConsultorModel())->all() as $cons): ?>
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
            <button type="submit" class="icon-btn icon-btn--primary" title="Aplicar" aria-label="Aplicar"><span data-feather="plus"></span></button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
