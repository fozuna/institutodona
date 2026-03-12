<?php /** @var int $cliente */ /** @var array $clientes */ /** @var array $metodologias */ /** @var array $funcoes */ /** @var array $consultores */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Aplicar nova tarefa</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <div class="md:flex md:gap-6">
    <div class="md:basis-1/2">
      <div class="bg-white shadow rounded p-4">
        <form method="post" action="index.php?route=clientes/attach" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <?php if ($cliente): ?>
        <input type="hidden" name="id_cliente" value="<?= (int)$cliente ?>" />
        <div class="text-xs text-gray-600">Empresa selecionada: 
          <?php foreach ($clientes as $c): if ((int)$c['id'] === (int)$cliente) { echo htmlspecialchars($c['nome_empresa']); break; } endforeach; ?>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-6">
            <label class="block text-sm">Cliente</label>
            <select name="id_cliente" class="w-full" required>
              <option value="">— selecione —</option>
              <?php foreach ($clientes as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
         <div class="md:col-span-7">
          <label class="block text-sm">Tarefa</label>
          <select name="id_metodologia" class="w-full" required>
            <?php foreach ($metodologias as $m): ?>
              <option value="<?= (int)$m['id'] ?>">[<?= htmlspecialchars($m['pilar_nome']) ?>] <?= htmlspecialchars($m['item_pilar']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-5">
          <label class="block text-sm">Data prevista</label>
          <input type="date" name="data_prevista" class="w-full" />
        </div>
        <div class="md:col-span-4">
          <label class="block text-sm">Consultor</label>
          <select name="consultor_id" class="w-full">
            <option value="">—</option>
            <?php foreach ($consultores as $cons): ?>
              <option value="<?= (int)$cons['id'] ?>"><?= htmlspecialchars($cons['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="block text-sm">Status inicial</label>
          <select name="status" class="w-full">
            <option value="Planejado">Planejado</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Concluído">Concluído</option>
            <option value="Pendente">Pendente</option>
          </select>
        </div>
        <div class="md:col-span-12">
          <label class="block text-sm">Colaboradores selecionados</label>
          <div id="colabSelected" class="flex flex-wrap gap-2"></div>
        </div>
      </div>
      <div class="flex items-center gap-3 justify-end">
        <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">SALVAR</button>
        <button type="button" class="px-4 py-2 rounded bg-gray-200 text-brand-brown" onclick="history.back()">CANCELAR</button>
      </div>
        </form>
      </div>
    </div>
    <div class="md:basis-1/2">
      <div class="bg-white shadow rounded p-4">
        <label class="block text-sm">Lista de Colaboradores</label>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end mb-2">
          <input type="text" id="colabSearch" class="border rounded p-2 w-full md:col-span-9" placeholder="Buscar por nome..." />
          <button type="button" id="colabSearchBtn" class="px-3 py-2 rounded bg-brand-red text-white md:col-span-3">Filtrar</button>
        </div>
        <div class="border rounded overflow-hidden">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="p-2">Nome</th>
                <th class="p-2">E-mail</th>
                <th class="p-2">Ação</th>
              </tr>
            </thead>
            <tbody id="colabTable">
              <tr><td class="p-2 text-gray-500" colspan="3">Digite para buscar…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script>
    (function(){
      const cliente = <?= (int)$cliente ?> || document.querySelector('select[name="id_cliente"]')?.value || 0;
      const input = document.getElementById('colabSearch');
      const selected = document.getElementById('colabSelected');
      const tableBody = document.getElementById('colabTable');
      const searchBtn = document.getElementById('colabSearchBtn');
      const selectedIds = new Set();
      function renderSelected(){
        selected.innerHTML = '';
        selectedIds.forEach(id=>{
          const el = document.createElement('span');
          el.className = 'px-2 py-1 rounded bg-brand-brown text-white text-xs';
          el.textContent = cache[id] || ('#'+id);
          const rm = document.createElement('button');
          rm.className = 'ml-2 text-xs bg-gray-200 text-brand-brown px-1 rounded';
          rm.textContent = 'x';
          rm.type = 'button';
          rm.onclick = ()=>{ selectedIds.delete(id); renderSelected(); syncHidden(); };
          el.appendChild(rm);
          selected.appendChild(el);
        });
      }
      function syncHidden(){
        document.querySelectorAll('input[name="colaborador_ids[]"]').forEach(e=>e.remove());
        selectedIds.forEach(id=>{
          const h = document.createElement('input');
          h.type = 'hidden';
          h.name = 'colaborador_ids[]';
          h.value = id;
          document.forms[0].appendChild(h);
        });
      }
      let timer; const cache = {};
      async function runSearch(q){
        const cid = cliente || (document.querySelector('select[name="id_cliente"]')?.value || 0);
        if (!cid) { tableBody.innerHTML = '<tr><td class="p-2 text-gray-500" colspan="3">Selecione o cliente</td></tr>'; return; }
        const resp = await fetch(`index.php?route=colaboradores/search&cliente=${cid}&q=${encodeURIComponent(q)}`);
        const arr = await resp.json();
        if (!arr.length) { tableBody.innerHTML = '<tr><td class="p-2 text-gray-500" colspan="3">Nenhum resultado</td></tr>'; return; }
        tableBody.innerHTML = arr.map(r=>{
          cache[r.id] = r.nome;
          const disabled = selectedIds.has(r.id) ? 'disabled' : '';
          return `<tr class="border-b">
            <td class="p-2">${r.nome}</td>
            <td class="p-2">${r.email || '—'}</td>
            <td class="p-2"><button type="button" data-pick="${r.id}" class="px-2 py-1 rounded bg-brand-red text-white ${disabled}">Selecionar</button></td>
          </tr>`;
        }).join('');
        tableBody.querySelectorAll('button[data-pick]').forEach(btn=>{
          btn.onclick = ()=>{
            const id = parseInt(btn.getAttribute('data-pick'),10);
            if (selectedIds.has(id)) return;
            selectedIds.add(id);
            renderSelected(); syncHidden();
          };
        });
      }
      input.addEventListener('input', ()=>{
        const q = input.value.trim();
        clearTimeout(timer);
        timer = setTimeout(()=>runSearch(q), 250);
      });
      searchBtn.addEventListener('click', ()=>runSearch(input.value.trim()));
      // initial load
      runSearch('');
    })();
  </script>
</div>
