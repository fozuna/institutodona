<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-900">Nova Ata</h1>
    <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" href="index.php?route=atas/index" title="Voltar" aria-label="Voltar">
      <span data-feather="arrow-left" class="h-4 w-4"></span>
      <span>Voltar</span>
    </a>
  </div>

  <form method="post" action="index.php?route=atas/store" class="space-y-4 bg-white shadow rounded-lg p-4" enctype="multipart/form-data" id="ataForm">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= 20 * 1024 * 1024 ?>" />
    <div>
      <label class="block text-sm text-slate-700">Nome/título</label>
      <input type="text" name="nome" maxlength="255" class="border rounded p-2 w-full" required />
    </div>
    <div>
      <label class="block text-sm text-slate-700">Descrição</label>
      <textarea name="descricao" maxlength="500" rows="4" class="border rounded p-2 w-full"></textarea>
      <div class="text-xs text-gray-500 mt-1">Máximo de 500 caracteres.</div>
    </div>
    <div>
      <label class="block text-sm text-slate-700">Arquivo</label>
      <input type="file"
             name="arquivo"
             id="ataArquivo"
             accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
             required />
      <div class="text-xs text-gray-500 mt-1">Tipos permitidos: PDF, DOC, DOCX. Tamanho máximo: 20MB.</div>
    </div>
    <div id="ataUploadError" class="hidden rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
    <div class="flex items-center gap-3 justify-end">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit" id="ataSubmitBtn">SALVAR</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=atas/index">CANCELAR</a>
    </div>
  </form>
</div>
<script>
  (function () {
    const form = document.getElementById('ataForm');
    const arquivo = document.getElementById('ataArquivo');
    const errBox = document.getElementById('ataUploadError');
    const submitBtn = document.getElementById('ataSubmitBtn');
    const maxBytes = 20 * 1024 * 1024;

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

    function setSubmitting(submitting) {
      if (!submitBtn) return;
      submitBtn.disabled = !!submitting;
      submitBtn.classList.toggle('opacity-75', !!submitting);
      submitBtn.classList.toggle('cursor-not-allowed', !!submitting);
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        setError('');
        const file = arquivo && arquivo.files && arquivo.files[0];
        if (!file) {
          setError('Selecione um arquivo para envio.');
          return;
        }
        if (file.size > maxBytes) {
          setError('Arquivo excede o limite de 20MB.');
          return;
        }
        const data = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function () {
          setSubmitting(false);
          let payload = null;
          try { payload = JSON.parse(xhr.responseText || '{}'); } catch (err) { payload = null; }
          if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
            window.location.href = payload.redirect || 'index.php?route=atas/index';
            return;
          }
          const msg = (payload && payload.message) ? payload.message : (xhr.responseText || 'Falha ao enviar arquivo.');
          setError(String(msg).replace(/<[^>]+>/g, '').trim());
        });
        xhr.addEventListener('error', function () {
          setSubmitting(false);
          setError('Erro de rede ao enviar o arquivo.');
        });
        setSubmitting(true);
        xhr.send(data);
      });
    }
  })();
</script>
