<?php /** @var array $app */ /** @var array $consultores */ /** @var array $colabs */ ?>
<div class="p-6 max-w-3xl">
  <h1 class="text-2xl font-bold text-brand-black mb-2">Editar Tarefa</h1>
  <?php if (!$app): ?>
    <div class="bg-white shadow rounded p-4">Tarefa não encontrada.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-500">Cliente</div>
          <div class="font-semibold"><?= htmlspecialchars($app['cliente_nome'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Pilar</div>
          <div class="font-semibold"><?= htmlspecialchars($app['pilar_nome'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Tarefa</div>
          <div class="font-semibold"><?= htmlspecialchars($app['item_pilar'] ?? '') ?></div>
        </div>
      </div>
    </div>

    <form method="post" action="index.php?route=aplicacoes/update" class="space-y-4 bg-white shadow rounded p-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id_aplicacao" value="<?= (int)$app['id'] ?>" />
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div>
          <label class="block text-sm">Data prevista</label>
          <input type="date" name="data_prevista" value="<?= htmlspecialchars($app['data_prevista'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-sm">Consultor</label>
          <select name="consultor_id">
            <option value="">—</option>
            <?php foreach ($consultores as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((int)($app['consultor_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Status</label>
          <select name="status">
            <?php foreach (['A Fazer','Em Andamento','Concluído','Pendente'] as $s): ?>
              <option value="<?= $s ?>" <?= ($app['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm">Colaboradores vinculados</label>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <input type="text" id="colabSearch" class="border rounded p-2 w-full md:col-span-4" placeholder="Buscar por nome..." />
          <div id="colabResults" class="border rounded p-2 max-h-48 overflow-auto text-sm md:col-span-4"></div>
          <div id="colabSelected" class="flex flex-wrap gap-2 md:col-span-4">
            <?php foreach ($colabs as $c): ?>
              <span class="px-2 py-1 rounded bg-brand-brown text-white text-xs" data-id="<?= (int)$c['id'] ?>">
                <?= htmlspecialchars($c['nome']) ?>
                <button type="button" class="ml-2 text-xs bg-gray-200 text-brand-brown px-1 rounded" data-remove="<?= (int)$c['id'] ?>">x</button>
              </span>
              <input type="hidden" name="colaborador_ids[]" value="<?= (int)$c['id'] ?>" />
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3 justify-end">
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
        <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=clientes/show&id=<?= (int)$app['id_cliente'] ?>">CANCELAR</a>
      </div>
      <script>
        (function(){
          const cliente = <?= (int)($app['id_cliente'] ?? 0) ?>;
          const input = document.getElementById('colabSearch');
          const results = document.getElementById('colabResults');
          const selected = document.getElementById('colabSelected');
          const selectedIds = new Set(Array.from(document.querySelectorAll('input[name="colaborador_ids[]"]')).map(i=>parseInt(i.value,10)));
          const cache = {};
          function renderSelected(){
            // sync remove buttons
            selected.querySelectorAll('button[data-remove]').forEach(btn=>{
              btn.onclick = ()=>{
                const id = parseInt(btn.getAttribute('data-remove'),10);
                selectedIds.delete(id);
                // remove hidden input
                document.querySelectorAll(`input[name="colaborador_ids[]"][value="${id}"]`).forEach(e=>e.remove());
                // remove chip
                const chip = selected.querySelector(`span[data-id="${id}"]`);
                if (chip) chip.remove();
              };
            });
          }
          renderSelected();
          let timer;
          input.addEventListener('input', ()=>{
            const q = input.value.trim();
            results.innerHTML = '';
            if (!q) return;
            clearTimeout(timer);
            timer = setTimeout(async ()=>{
              const resp = await fetch(`index.php?route=colaboradores/search&cliente=${cliente}&q=${encodeURIComponent(q)}`);
              const arr = await resp.json();
              results.innerHTML = arr.map(r=>{
                cache[r.id] = r.nome;
                const disabled = selectedIds.has(r.id) ? 'disabled' : '';
                return `<button type="button" data-id="${r.id}" class="block w-full text-left px-2 py-1 hover:bg-gray-100 ${disabled}">${r.nome}${r.email ? ' • '+r.email : ''}</button>`;
              }).join('') || '<div class="text-gray-500">Nenhum resultado</div>';
              results.querySelectorAll('button[data-id]').forEach(btn=>{
                btn.onclick = ()=>{
                  const id = parseInt(btn.getAttribute('data-id'),10);
                  if (selectedIds.has(id)) return;
                  selectedIds.add(id);
                  const chip = document.createElement('span');
                  chip.className = 'px-2 py-1 rounded bg-brand-brown text-white text-xs';
                  chip.setAttribute('data-id', String(id));
                  chip.textContent = cache[id] || ('#'+id);
                  const rm = document.createElement('button');
                  rm.className = 'ml-2 text-xs bg-gray-200 text-brand-brown px-1 rounded';
                  rm.textContent = 'x';
                  rm.type = 'button';
                  rm.setAttribute('data-remove', String(id));
                  chip.appendChild(rm);
                  selected.appendChild(chip);
                  const h = document.createElement('input');
                  h.type = 'hidden';
                  h.name = 'colaborador_ids[]';
                  h.value = id;
                  document.forms[0].appendChild(h);
                  renderSelected();
                };
              });
            }, 300);
          });
          selected.addEventListener('click', (e)=>{
            const t = e.target;
            if (t.tagName === 'BUTTON' && t.textContent === 'x') {
              e.preventDefault();
            }
          });
        })();
      </script>
    </form>
  <?php endif; ?>
</div>
