<?php /** @var array $clientes */ /** @var array $departamentos */ /** @var int $selectedEmpresa */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Novo Item</h1>
    <div class="flex items-center gap-2">
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>

  <form method="post" action="index.php?route=manuais/store" class="space-y-4 bg-white shadow rounded p-4" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= 50 * 1024 * 1024 ?>" />
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-6">
        <label class="block text-sm">Empresa</label>
        <select name="empresa_id" id="manualEmpresa" class="border rounded p-2 w-full" required>
          <option value="">Selecione</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>>
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
            <option value="<?= (int)$d['id'] ?>" data-empresa="<?= (int)$d['cliente_id'] ?>">
              <?= htmlspecialchars($d['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Nome</label>
        <input type="text" name="nome" maxlength="255" required />
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Descrição</label>
        <textarea name="descricao" maxlength="500" rows="4"></textarea>
        <div class="text-xs text-gray-500 mt-1">Máximo de 500 caracteres.</div>
      </div>
      <div class="md:col-span-12">
        <label class="block text-sm">Arquivo</label>
        <input type="file"
               name="arquivo"
               id="manualArquivo"
               accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.svg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml"
               required />
        <div class="text-xs text-gray-500 mt-1">
          Tipos permitidos: PDF, DOC, DOCX, TXT, RTF, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, GIF, BMP, SVG. Tamanho máximo: 50MB.
        </div>
      </div>
    </div>
    <div id="manualUploadError" class="hidden rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
    <div id="manualPreview" class="hidden rounded border border-gray-200 bg-gray-50 p-4">
      <div class="text-sm font-semibold text-gray-800 mb-2">Pré-visualização</div>
      <div class="flex flex-col md:flex-row md:items-center gap-3">
        <div id="manualPreviewThumb" class="w-24 h-24 rounded bg-white border flex items-center justify-center overflow-hidden text-gray-500"></div>
        <div class="min-w-0">
          <div id="manualPreviewName" class="text-sm font-medium text-gray-900 truncate"></div>
          <div id="manualPreviewMeta" class="text-xs text-gray-600 mt-1"></div>
        </div>
        <div class="md:ml-auto">
          <button type="button" id="manualClearFile" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Remover</button>
        </div>
      </div>
    </div>
    <div id="manualUploadProgressWrap" class="hidden">
      <div class="text-xs text-gray-600 mb-1" id="manualUploadProgressText">Enviando...</div>
      <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
        <div id="manualUploadProgressBar" class="bg-brand-red h-2" style="width: 0%"></div>
      </div>
    </div>
    <div class="flex items-center gap-3 justify-end">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit" id="manualSubmitBtn">SALVAR</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=manuais/index<?= $selectedEmpresa > 0 ? '&empresa_id=' . (int)$selectedEmpresa : '' ?>">CANCELAR</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const empresa = document.getElementById('manualEmpresa');
    const departamento = document.getElementById('manualDepartamento');
    const arquivo = document.getElementById('manualArquivo');
    const preview = document.getElementById('manualPreview');
    const previewThumb = document.getElementById('manualPreviewThumb');
    const previewName = document.getElementById('manualPreviewName');
    const previewMeta = document.getElementById('manualPreviewMeta');
    const clearBtn = document.getElementById('manualClearFile');
    const errBox = document.getElementById('manualUploadError');
    const progressWrap = document.getElementById('manualUploadProgressWrap');
    const progressBar = document.getElementById('manualUploadProgressBar');
    const progressText = document.getElementById('manualUploadProgressText');
    const submitBtn = document.getElementById('manualSubmitBtn');
    const form = submitBtn ? submitBtn.closest('form') : null;

    function humanSize(bytes) {
      const n = Number(bytes || 0);
      if (!n) return '0 B';
      const units = ['B', 'KB', 'MB', 'GB'];
      let u = 0;
      let v = n;
      while (v >= 1024 && u < units.length - 1) {
        v /= 1024;
        u++;
      }
      return (v >= 10 || u === 0 ? v.toFixed(0) : v.toFixed(1)) + ' ' + units[u];
    }

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

    function clearPreview() {
      if (arquivo) arquivo.value = '';
      if (previewThumb) previewThumb.innerHTML = '';
      if (previewName) previewName.textContent = '';
      if (previewMeta) previewMeta.textContent = '';
      if (preview) preview.classList.add('hidden');
      setError('');
    }
    if (empresa && departamento) {
      const sync = function() {
        const empresaId = empresa.value;
        Array.from(departamento.options).forEach(function(option, idx) {
          if (idx === 0) return;
          if (idx === 0) return;
          const matches = !!empresaId && option.getAttribute('data-empresa') === empresaId;
          option.hidden = !matches;
          if (!matches && option.selected) {
            departamento.value = '';
          }
        });
      };
      empresa.addEventListener('change', sync);
      sync();
    }

    function renderPreview(file) {
      if (!file || !preview) return;
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (previewName) previewName.textContent = file.name;
      if (previewMeta) previewMeta.textContent = (file.type ? (file.type + ' • ') : '') + humanSize(file.size);
      if (previewThumb) {
        previewThumb.innerHTML = '';
        const isRaster = ['jpg','jpeg','png','gif','bmp'].includes(ext);
        if (isRaster) {
          const img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          img.className = 'w-full h-full object-cover';
          img.alt = '';
          previewThumb.appendChild(img);
        } else {
          const iconWrap = document.createElement('div');
          iconWrap.className = 'text-xs font-semibold text-gray-600';
          iconWrap.textContent = ext ? ext.toUpperCase() : 'ARQ';
          previewThumb.appendChild(iconWrap);
        }
      }
      preview.classList.remove('hidden');
      if (typeof feather !== 'undefined') feather.replace();
    }

    if (arquivo) {
      arquivo.addEventListener('change', function() {
        const file = arquivo.files && arquivo.files[0];
        if (!file) {
          clearPreview();
          return;
        }
        if (file.size > (50 * 1024 * 1024)) {
          clearPreview();
          setError('Arquivo excede o limite de 50MB.');
          return;
        }
        setError('');
        renderPreview(file);
      });
    }
    if (clearBtn) {
      clearBtn.addEventListener('click', function() {
        clearPreview();
      });
    }

    function setProgress(active, pct) {
      if (!progressWrap || !progressBar || !progressText) return;
      if (!active) {
        progressWrap.classList.add('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '';
        return;
      }
      progressWrap.classList.remove('hidden');
      const p = Math.max(0, Math.min(100, Number(pct || 0)));
      progressBar.style.width = p + '%';
      progressText.textContent = 'Enviando... ' + p.toFixed(0) + '%';
    }

    function setSubmitting(submitting) {
      if (!submitBtn) return;
      submitBtn.disabled = !!submitting;
      submitBtn.classList.toggle('opacity-75', !!submitting);
      submitBtn.classList.toggle('cursor-not-allowed', !!submitting);
    }

    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        setError('');
        const file = arquivo && arquivo.files && arquivo.files[0];
        if (!file) {
          setError('Selecione um arquivo para envio.');
          return;
        }
        if (file.size > (50 * 1024 * 1024)) {
          setError('Arquivo excede o limite de 50MB.');
          return;
        }
        const data = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.addEventListener('progress', function(ev) {
          if (!ev.lengthComputable) return;
          const pct = (ev.loaded / ev.total) * 100;
          setProgress(true, pct);
        });
        xhr.addEventListener('load', function() {
          setSubmitting(false);
          setProgress(false, 0);
          let payload = null;
          try { payload = JSON.parse(xhr.responseText || '{}'); } catch (err) { payload = null; }
          if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
            window.location.href = payload.redirect || 'index.php?route=manuais/index';
            return;
          }
          const msg = (payload && payload.message) ? payload.message : (xhr.responseText || 'Falha ao enviar arquivo.');
          setError(String(msg).replace(/<[^>]+>/g, '').trim());
        });
        xhr.addEventListener('error', function() {
          setSubmitting(false);
          setProgress(false, 0);
          setError('Erro de rede ao enviar o arquivo.');
        });
        setSubmitting(true);
        setProgress(true, 0);
        xhr.send(data);
      });
    }
  })();
</script>
