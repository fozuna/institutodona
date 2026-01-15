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
      <label class="block text-sm">Colaboradores</label>
      <div class="space-y-2">
        <input type="text" id="colabSearch" class="border rounded p-2 w-full" placeholder="Buscar por nome..." />
        <div id="colabResults" class="border rounded p-2 max-h-48 overflow-auto text-sm"></div>
        <div id="colabSelected" class="flex flex-wrap gap-2"></div>
      </div>
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
  <script>
    (function(){
      const cliente = <?= (int)$cliente ?> || document.querySelector('select[name="id_cliente"]')?.value || 0;
      const input = document.getElementById('colabSearch');
      const results = document.getElementById('colabResults');
      const selected = document.getElementById('colabSelected');
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
      input.addEventListener('input', ()=>{
        const q = input.value.trim();
        results.innerHTML = '';
        if (!q) return;
        clearTimeout(timer);
        timer = setTimeout(async ()=>{
          const cid = cliente || (document.querySelector('select[name="id_cliente"]')?.value || 0);
          if (!cid) { results.textContent = 'Selecione o cliente primeiro'; return; }
          const resp = await fetch(`index.php?route=colaboradores/search&cliente=${cid}&q=${encodeURIComponent(q)}`);
          const arr = await resp.json();
          results.innerHTML = arr.map(r=>{
            cache[r.id] = r.nome;
            const disabled = selectedIds.has(r.id) ? 'disabled' : '';
            return `<button data-id="${r.id}" class="block w-full text-left px-2 py-1 hover:bg-gray-100 ${disabled}">${r.nome}${r.email ? ' • '+r.email : ''}</button>`;
          }).join('') || '<div class="text-gray-500">Nenhum resultado</div>';
          results.querySelectorAll('button[data-id]').forEach(btn=>{
            btn.onclick = ()=>{
              const id = parseInt(btn.getAttribute('data-id'),10);
              selectedIds.add(id);
              renderSelected(); syncHidden();
            };
          });
        }, 300);
      });
    })();
  </script>
</div>
