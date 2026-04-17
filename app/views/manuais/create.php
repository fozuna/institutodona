<?php /** @var array $clientes */ /** @var array $departamentos */ /** @var int $selectedEmpresa */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Novo Manual</h1>
    <div class="flex items-center gap-2">
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>

  <form method="post" action="index.php?route=manuais/store" class="space-y-4 bg-white shadow rounded p-4" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
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
        <input type="file" name="arquivo" id="manualArquivo" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required />
        <div class="text-xs text-gray-500 mt-1">Tipos permitidos: pdf, doc, docx. Tamanho máximo: 10MB.</div>
      </div>
    </div>
    <div class="flex items-center gap-3 justify-end">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=manuais/index<?= $selectedEmpresa > 0 ? '&empresa_id=' . (int)$selectedEmpresa : '' ?>">CANCELAR</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const empresa = document.getElementById('manualEmpresa');
    const departamento = document.getElementById('manualDepartamento');
    const arquivo = document.getElementById('manualArquivo');
    if (empresa && departamento) {
      const sync = function() {
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
      empresa.addEventListener('change', sync);
      sync();
    }
    if (arquivo) {
      arquivo.addEventListener('change', function() {
        const file = arquivo.files && arquivo.files[0];
        if (!file) return;
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!['pdf', 'doc', 'docx'].includes(ext)) {
          alert('Tipo de arquivo inválido. Use pdf, doc ou docx.');
          arquivo.value = '';
          return;
        }
        if (file.size > (10 * 1024 * 1024)) {
          alert('Arquivo excede o limite de 10MB.');
          arquivo.value = '';
        }
      });
    }
  })();
</script>
