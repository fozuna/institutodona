<?php /** @var array $item */ /** @var array $apps */ /** @var array $metodologias */ ?>
<div class="p-6">
  <div class="mb-4">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-brand-black">Perfil do Cliente</h1>
      <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=metodologias/create&cliente=<?= (int)$item['id'] ?>">Criar Tarefa</a>
    </div>
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
        <div>
          <div class="text-sm text-gray-500">Tipo</div>
          <div class="font-semibold"><?= ((int)($item['is_matriz'] ?? 1) === 1) ? 'Matriz' : 'Filial' ?></div>
        </div>
        <?php if (!empty($item['matriz_id'])): ?>
        <div>
          <div class="text-sm text-gray-500">Matriz</div>
          <div class="font-semibold">
            <?php foreach ($matrizes as $m): if ((int)$m['id'] === (int)$item['matriz_id']) { echo htmlspecialchars($m['nome_empresa']); break; } endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="mb-6">
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-3">Estrutura Organizacional</div>
      <div class="flex flex-wrap gap-3">
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=departamentos/index&cliente=<?= (int)$item['id'] ?>">Departamentos</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=setores/index&cliente=<?= (int)$item['id'] ?>">Setores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=funcoes/index&cliente=<?= (int)$item['id'] ?>">Funções</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/index&cliente=<?= (int)$item['id'] ?>">Colaboradores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=dashboard/index&cliente=<?= (int)$item['id'] ?>">Tarefas</a>
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
                <th class="p-3">Função</th>
                <th class="p-3">Manual</th>
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
                  <td class="p-3"><?= htmlspecialchars($a['funcao_nome'] ?? '') ?></td>
                  <td class="p-3">
                    <?php if (($a['tipo'] ?? 'tarefa') === 'manual' && !empty($a['arquivo_path'])): ?>
                      <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($a['arquivo_path']) ?>">baixar</a>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
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
        <div class="px-4 py-3 border-b font-semibold flex justify-between items-center">
          <span>Aplicar nova tarefa</span>
          <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=clientes/index">Voltar</a>
        </div>
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
            <label class="block text-sm">Função</label>
            <select name="funcao_id" class="w-full" required>
              <option value="">— selecione —</option>
              <?php foreach ($funcoes as $fn): ?>
                <option value="<?= (int)$fn['id'] ?>"><?= htmlspecialchars($fn['departamento'] . ' / ' . $fn['setor'] . ' / ' . $fn['nome']) ?></option>
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
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold">Criar nova tarefa</div>
        <div class="p-4">
          <form method="post" action="index.php?route=metodologias/store" class="space-y-3" enctype="multipart/form-data">
            <input type="hidden" name="id_cliente" value="<?= (int)$item['id'] ?>" />
            <label class="block text-sm">Pilar</label>
            <select name="id_pilar" class="w-full" required>
              <?php foreach ($pilares as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="block text-sm">Item do Pilar</label>
            <input name="item_pilar" class="border rounded p-2 w-full" required />
            <label class="block text-sm">Tipo</label>
            <select name="tipo" class="w-full">
              <option value="tarefa">Tarefa</option>
              <option value="manual">Manual</option>
            </select>
            <label class="block text-sm">Arquivo (opcional)</label>
            <input type="file" name="arquivo" class="border rounded p-2 w-full" />
            <div class="text-xs text-gray-500">PDF, DOC, DOCX, XLS, XLSX ou TXT</div>
            <button type="submit" class="icon-btn icon-btn--primary" title="Criar" aria-label="Criar"><span data-feather="check"></span></button>
          </form>
        </div>
      </div>
      <?php if ((int)($item['is_matriz'] ?? 1) === 1): ?>
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold">Filiais</div>
        <div class="p-4">
          <?php if (empty($filiais)): ?>
            <div class="text-sm text-gray-600 mb-3">Nenhuma filial cadastrada.</div>
          <?php else: ?>
            <ul class="list-disc pl-5 mb-3">
              <?php foreach ($filiais as $f): ?>
                <li><a class="text-brand-red hover:underline" href="index.php?route=clientes/show&id=<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nome_empresa']) ?></a> — CNPJ: <?= htmlspecialchars($f['CNPJ']) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <form method="post" action="index.php?route=clientes/storeFilial" class="space-y-3">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
            <input type="hidden" name="matriz_id" value="<?= (int)$item['id'] ?>" />
            <label class="block text-sm">Nome da Filial</label>
            <input name="nome_empresa" class="border rounded p-2 w-full" required />
            <label class="block text-sm">CNPJ</label>
            <input name="CNPJ" class="border rounded p-2 w-full" required />
            <label class="block text-sm">Contato</label>
            <input name="contato" class="border rounded p-2 w-full" />
            <button type="submit" class="icon-btn icon-btn--primary" title="Cadastrar Filial" aria-label="Cadastrar Filial"><span data-feather="plus"></span></button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
