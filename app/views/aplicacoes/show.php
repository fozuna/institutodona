<?php /** @var array $app */ /** @var array $consultores */ /** @var array $colabs */ /** @var array $arquivos */ ?>
<div class="p-6">
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

    <div class="md:flex md:gap-6">
      <div class="md:basis-1/2">
        <form method="post" action="index.php?route=aplicacoes/update" class="space-y-4 bg-white shadow rounded p-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="id_aplicacao" value="<?= (int)$app['id'] ?>" />
          <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-6">
              <label class="block text-sm">Data prevista</label>
              <input type="date" name="data_prevista" value="<?= htmlspecialchars($app['data_prevista'] ?? '') ?>" />
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm">Consultor</label>
              <select name="consultor_id">
                <option value="">—</option>
                <?php foreach ($consultores as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= ((int)($app['consultor_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm">Status</label>
              <select name="status">
                <?php foreach (['Planejado','Em Andamento','Concluído','Pendente'] as $s): ?>
                  <option value="<?= $s ?>" <?= ($app['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="md:col-span-12">
              <label class="block text-sm">Colaboradores selecionados</label>
              <div id="colabSelected" class="flex flex-wrap gap-2">
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
        </form>
        <div class="bg-white shadow rounded p-4 mt-4">
          <div class="font-semibold mb-3">Observações e atualizações</div>
          <form method="post" action="index.php?route=aplicacoes/update" class="mb-3">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
            <input type="hidden" name="id_aplicacao" value="<?= (int)$app['id'] ?>" />
            <input type="hidden" name="status" value="<?= htmlspecialchars($app['status'] ?? 'Planejado') ?>" />
            <input type="hidden" name="data_prevista" value="<?= htmlspecialchars($app['data_prevista'] ?? '') ?>" />
            <input type="hidden" name="consultor_id" value="<?= (int)($app['consultor_id'] ?? 0) ?>" />
            <?php foreach ($colabs as $c): ?>
              <input type="hidden" name="colaborador_ids[]" value="<?= (int)$c['id'] ?>" />
            <?php endforeach; ?>
            <label class="block text-sm mb-1">Observação desta atualização</label>
            <textarea name="observacao_update" class="border rounded p-2 w-full" rows="3" placeholder="Descreva a alteração realizada..."></textarea>
            <div class="mt-2">
              <button class="px-3 py-2 rounded bg-brand-red text-white" type="submit">Registrar observação</button>
            </div>
          </form>
          <?php if (!empty($updates)): ?>
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="p-2">Data/Hora</th>
                  <th class="p-2">Usuário</th>
                  <th class="p-2">Resumo</th>
                  <th class="p-2">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($updates as $up): ?>
                  <tr class="border-b">
                    <td class="p-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($up['created_at']))) ?></td>
                    <td class="p-2"><?= htmlspecialchars($up['user_nome'] ?? '') ?></td>
                    <td class="p-2"><?= htmlspecialchars($up['summary'] ?? '') ?></td>
                    <td class="p-2 whitespace-nowrap">
                      <button type="button" class="px-2 py-1 rounded bg-gray-200 text-brand-brown" data-toggle="u<?= (int)$up['id'] ?>">Ver</button>
                      <a class="ml-2 text-brand-red hover:underline" href="index.php?route=aplicacoes/delete_update&id=<?= (int)$up['id'] ?>&ap=<?= (int)$app['id'] ?>" onclick="return confirm('Excluir este registro de atualização?')">Excluir</a>
                    </td>
                  </tr>
                  <tr class="border-b">
                    <td class="p-2" colspan="4">
                      <pre id="u<?= (int)$up['id'] ?>" class="hidden whitespace-pre-wrap text-xs bg-gray-50 p-2 rounded"><?= htmlspecialchars(json_encode(@json_decode($up['payload_json'] ?? '[]', true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <script>
              (function(){
                document.querySelectorAll('button[data-toggle]').forEach(btn=>{
                  btn.addEventListener('click', ()=>{
                    const id = btn.getAttribute('data-toggle');
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.classList.toggle('hidden');
                  });
                });
              })();
            </script>
          <?php else: ?>
            <div class="text-sm text-gray-600">Nenhuma atualização registrada.</div>
          <?php endif; ?>
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
        const cliente = <?= (int)($app['id_cliente'] ?? 0) ?>;
        const input = document.getElementById('colabSearch');
        const selected = document.getElementById('colabSelected');
        const tableBody = document.getElementById('colabTable');
        const searchBtn = document.getElementById('colabSearchBtn');
        const selectedIds = new Set(Array.from(document.querySelectorAll('input[name="colaborador_ids[]"]')).map(i=>parseInt(i.value,10)));
        const cache = {};
        function renderSelected(){
          selected.querySelectorAll('button[data-remove]').forEach(btn=>{
            btn.onclick = ()=>{
              const id = parseInt(btn.getAttribute('data-remove'),10);
              selectedIds.delete(id);
              document.querySelectorAll(`input[name="colaborador_ids[]"][value="${id}"]`).forEach(e=>e.remove());
              const chip = selected.querySelector(`span[data-id="${id}"]`);
              if (chip) chip.remove();
            };
          });
        }
        renderSelected();
        async function runSearch(q){
          const resp = await fetch(`index.php?route=colaboradores/search&cliente=${cliente}&q=${encodeURIComponent(q)}`);
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
        }
        let timer;
        input.addEventListener('input', ()=>{
          const q = input.value.trim();
          clearTimeout(timer);
          timer = setTimeout(()=>runSearch(q), 250);
        });
        searchBtn.addEventListener('click', ()=>runSearch(input.value.trim()));
        runSearch('');
      })();
    </script>
    <div class="bg-white shadow rounded p-4 mt-4">
      <div class="font-semibold mb-3">Arquivos da tarefa</div>
      <div class="mb-3">
        <div id="uploadZone" class="border-2 border-dashed border-brand-brown rounded p-4 text-center text-sm text-gray-600">
          Arraste e solte arquivos aqui ou selecione abaixo
        </div>
        <div class="mt-3 flex items-end gap-3">
          <input type="hidden" id="csrfUpload" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" id="uploadAplicacaoId" value="<?= (int)$app['id'] ?>" />
          <div class="flex-1">
            <label class="block text-sm">Selecionar arquivos</label>
            <input type="file" id="fileInput" multiple />
          </div>
          <button id="sendFilesBtn" class="px-4 py-2 rounded bg-brand-red text-white" type="button">Enviar</button>
        </div>
        <div id="uploadList" class="mt-3 space-y-2"></div>
      </div>
      <?php if (!empty($arquivos)): ?>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="p-2">Nome</th>
              <th class="p-2">Tipo</th>
              <th class="p-2">Tamanho</th>
              <th class="p-2">Data</th>
              <th class="p-2">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($arquivos as $f): ?>
              <tr class="border-b">
                <td class="p-2"><?= htmlspecialchars($f['nome_original']) ?></td>
                <td class="p-2"><?= htmlspecialchars($f['mime']) ?></td>
                <td class="p-2"><?= number_format((int)$f['tamanho'] / 1024, 1) ?> KB</td>
                <td class="p-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($f['uploaded_at']))) ?></td>
                <td class="p-2">
                  <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($f['arquivo_path']) ?>">Abrir</a>
                  <button type="button" class="ml-2 px-2 py-1 rounded bg-gray-200 text-brand-brown" data-preview="../<?= htmlspecialchars($f['arquivo_path']) ?>" data-mime="<?= htmlspecialchars($f['mime']) ?>">Preview</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="text-sm text-gray-600">Nenhum arquivo enviado ainda.</div>
      <?php endif; ?>
      <div class="mt-4">
        <div class="text-sm text-gray-600 mb-1">Visualização</div>
        <div id="filePreview" class="border rounded p-2 aspect-video bg-white flex items-center justify-center text-sm text-gray-600">Selecione Preview para visualizar aqui</div>
      </div>
      <script>
        (function(){
          const preview = document.getElementById('filePreview');
          const zone = document.getElementById('uploadZone');
          const input = document.getElementById('fileInput');
          const list = document.getElementById('uploadList');
          const btn = document.getElementById('sendFilesBtn');
          const csrf = document.getElementById('csrfUpload').value;
          const apid = document.getElementById('uploadAplicacaoId').value;
          document.querySelectorAll('button[data-preview]').forEach(btn=>{
            btn.addEventListener('click', ()=>{
              const url = btn.getAttribute('data-preview');
              const mime = btn.getAttribute('data-mime') || '';
              if (mime.startsWith('image/')) {
                preview.innerHTML = '<img src="'+url+'" alt="preview" style="max-width:100%;max-height:100%;">';
              } else if (mime === 'application/pdf') {
                preview.innerHTML = '<iframe src="'+url+'" style="width:100%;height:100%;" frameborder="0"></iframe>';
              } else {
                preview.innerHTML = '<a class="text-brand-red hover:underline" target="_blank" href="'+url+'">Abrir arquivo</a>';
              }
            });
          });
          function addProgressItem(file) {
            const row = document.createElement('div');
            row.className = 'border rounded p-2';
            row.innerHTML = '<div class="text-sm">'+file.name+' ('+Math.round(file.size/1024)+' KB)</div><div class="w-full h-2 bg-gray-200 rounded mt-2"><div class="h-2 bg-brand-red rounded" style="width:0%" ></div></div>';
            list.appendChild(row);
            return row.querySelector('div > div');
          }
          function uploadFiles(files) {
            if (!files || !files.length) return;
            Array.from(files).forEach(file=>{
              const bar = addProgressItem(file);
              const fd = new FormData();
              fd.append('csrf', csrf);
              fd.append('id_aplicacao', apid);
              fd.append('arquivo', file);
              const xhr = new XMLHttpRequest();
              xhr.open('POST', 'index.php?route=aplicacoes/upload&ajax=1');
              xhr.upload.onprogress = (e)=>{
                if (e.lengthComputable) {
                  const pct = Math.round((e.loaded / e.total) * 100);
                  bar.style.width = pct + '%';
                }
              };
              xhr.onload = ()=>{
                try {
                  const json = JSON.parse(xhr.responseText || '{}');
                  if (json.ok && json.files && json.files.length) {
                    const f = json.files[0];
                    const tbody = document.querySelector('table tbody');
                    if (tbody) {
                      const tr = document.createElement('tr');
                      tr.className = 'border-b';
                      tr.innerHTML = '<td class="p-2">'+(f.nome_original || file.name)+'</td><td class="p-2">'+(f.mime || file.type || '—')+'</td><td class="p-2">'+Math.round((f.tamanho||file.size)/1024)+' KB</td><td class="p-2">'+(f.uploaded_at || '')+'</td><td class="p-2"><a class="text-brand-red hover:underline" target="_blank" href="../'+f.arquivo_path+'">Abrir</a> <button type="button" class="ml-2 px-2 py-1 rounded bg-gray-200 text-brand-brown" data-preview="../'+f.arquivo_path+'" data-mime="'+(f.mime||'')+'">Preview</button></td>';
                      tbody.appendChild(tr);
                    }
                  }
                } catch (e) {}
              };
              xhr.send(fd);
            });
          }
          zone.addEventListener('dragover', (e)=>{ e.preventDefault(); zone.classList.add('bg-gray-50'); });
          zone.addEventListener('dragleave', ()=> zone.classList.remove('bg-gray-50'));
          zone.addEventListener('drop', (e)=>{ e.preventDefault(); zone.classList.remove('bg-gray-50'); uploadFiles(e.dataTransfer.files); });
          btn.addEventListener('click', ()=> uploadFiles(input.files));
        })();
      </script>
    </div>
  <?php endif; ?>
</div>
