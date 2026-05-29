<?php /** @var array $item */ /** @var array $clientes */ /** @var array $departamentos */ ?>
<?php /** @var array $filiais */ /** @var array $linkedFiliais */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Editar Item</h1>
    <div class="flex items-center gap-2">
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>

  <form method="post" action="index.php?route=manuais/update" class="space-y-4 bg-white shadow rounded p-4" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>" />
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= 50 * 1024 * 1024 ?>" />
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-6">
        <label class="block text-sm">Empresa</label>
        <select name="empresa_id" id="manualEmpresa" class="border rounded p-2 w-full" required>
          <option value="">Selecione</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($item['empresa_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome_empresa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-6">
        <label class="block text-sm">Departamento</label>
        <select name="departamento_id" id="manualDepartamento" class="border rounded p-2 w-full" required>
          <option value="">Selecione</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= (int)$d['id'] ?>" data-empresa="<?= (int)$d['cliente_id'] ?>" <?= (int)($item['departamento_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Nome</label>
        <input type="text" name="nome" maxlength="255" required class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($item['nome'] ?? '')) ?>" />
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Descrição</label>
        <textarea name="descricao" maxlength="500" rows="4" class="border rounded p-2 w-full"><?= htmlspecialchars((string)($item['descricao'] ?? '')) ?></textarea>
        <div class="text-xs text-gray-500 mt-1">Máximo de 500 caracteres.</div>
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Substituir arquivo (opcional)</label>
        <input type="file"
               name="arquivo"
               id="manualArquivo"
               accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.svg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" />
        <div class="text-xs text-gray-500 mt-1">
          Tipos permitidos: PDF, DOC, DOCX, TXT, RTF, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, GIF, BMP, SVG. Tamanho máximo: 50MB.
        </div>
      </div>
      <div class="md:col-span-12 hidden" id="manualFiliaisWrap">
        <label class="block text-sm">Vincular às filiais</label>
        <div class="text-xs text-gray-500 mt-1">Selecione as unidades filiais que terão acesso ao manual da matriz.</div>
        <div id="manualFiliaisList" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2"></div>
      </div>
    </div>
    <div id="manualUploadError" class="hidden rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
    <div class="flex items-center gap-3 justify-end">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit" id="manualSubmitBtn">SALVAR</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=manuais/index&empresa_id=<?= (int)($item['empresa_id'] ?? 0) ?>">CANCELAR</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const empresa = document.getElementById('manualEmpresa');
    const departamento = document.getElementById('manualDepartamento');
    const arquivo = document.getElementById('manualArquivo');
    const errBox = document.getElementById('manualUploadError');
    const submitBtn = document.getElementById('manualSubmitBtn');
    const form = submitBtn ? submitBtn.closest('form') : null;
    const filiaisWrap = document.getElementById('manualFiliaisWrap');
    const filiaisList = document.getElementById('manualFiliaisList');
    const clientesMap = <?= json_encode(array_values($clientes), JSON_UNESCAPED_UNICODE) ?>;
    const byId = {};
    (clientesMap || []).forEach(function(c) { byId[String(c.id)] = c; });
    const linked = new Set(<?= json_encode(array_values($linkedFiliais ?? []), JSON_UNESCAPED_UNICODE) ?>.map(function(v){ return String(v); }));

    function setError(msg) {
      if (!errBox) return;
      if (!msg) {
        errBox.classList.add('hidden');
        errBox.textContent = '';
        return;
      }
      errBox.textContent = msg;
      errBox.classList.remove('hidden');
    }

    function fetchJson(url) {
      return fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function(r) { return r.json(); });
    }

    function renderFiliais(items) {
      if (!filiaisList) return;
      filiaisList.innerHTML = '';
      (items || []).forEach(function(f) {
        const id = String(f.id || '');
        const label = String(f.nome_empresa || f.nome || ('Filial ' + id));
        const row = document.createElement('label');
        row.className = 'flex items-center gap-2 p-2 rounded border bg-white';
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.name = 'filiais_ids[]';
        cb.value = id;
        cb.checked = linked.has(id);
        const span = document.createElement('span');
        span.className = 'text-sm text-gray-800';
        span.textContent = label;
        row.appendChild(cb);
        row.appendChild(span);
        filiaisList.appendChild(row);
      });
    }

    function syncFiliais() {
      if (!empresa || !filiaisWrap || !filiaisList) return;
      const empresaId = String(empresa.value || '');
      const info = empresaId ? byId[String(empresaId)] : null;
      const isMatriz = info && Number(info.is_matriz || 0) === 1;
      if (!empresaId || !isMatriz) {
        filiaisWrap.classList.add('hidden');
        renderFiliais([]);
        return;
      }
      fetchJson('index.php?route=manuais/apiFiliais&matriz_id=' + encodeURIComponent(empresaId))
        .then(function(payload) {
          const items = payload && payload.success ? (payload.items || []) : [];
          renderFiliais(items);
          filiaisWrap.classList.toggle('hidden', items.length === 0);
        })
        .catch(function() {
          renderFiliais([]);
          filiaisWrap.classList.add('hidden');
        });
    }

    if (empresa && departamento) {
      const syncDepartamento = function() {
        const empresaId = empresa.value;
        Array.from(departamento.options).forEach(function(option, idx) {
          if (idx === 0) return;
          const matches = !!empresaId && option.getAttribute('data-empresa') === empresaId;
          option.hidden = !matches;
          if (!matches && option.selected) {
            departamento.value = '';
          }
        });
      };
      empresa.addEventListener('change', syncDepartamento);
      syncDepartamento();
      empresa.addEventListener('change', syncFiliais);
      syncFiliais();
    }

    if (arquivo) {
      arquivo.addEventListener('change', function() {
        const file = arquivo.files && arquivo.files[0];
        if (!file) {
          setError('');
          return;
        }
        if (file.size > (50 * 1024 * 1024)) {
          arquivo.value = '';
          setError('Arquivo excede o limite de 50MB.');
          return;
        }
        setError('');
      });
    }

    if (form) {
      form.addEventListener('submit', function() {
        setError('');
      });
    }
  })();
</script>
