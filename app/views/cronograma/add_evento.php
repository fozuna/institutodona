<?php /** @var array $crono */ ?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Adicionar evento</h1>
      <div class="text-sm text-gray-600"><?= htmlspecialchars($crono['cliente'] ?? '') ?> · Ano <?= (int)($crono['ano'] ?? date('Y')) ?></div>
      <?php if (!empty($crono['nome'])): ?><div class="text-sm text-gray-600">Cronograma: <?= htmlspecialchars($crono['nome']) ?></div><?php endif; ?>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=clientes/show&id=<?= (int)($crono['id_cliente'] ?? 0) ?>">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4">
    <form method="post" action="index.php?route=cronograma/addEvento" class="space-y-3" enctype="multipart/form-data" id="cronogramaAddEventoForm">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
      <input type="hidden" name="status_filter" value="todos" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">Data base</label>
          <input class="border rounded p-2 w-full" type="date" name="data" required />
        </div>
        <div>
          <label class="block text-sm">Periodicidade</label>
          <select class="border rounded p-2 w-full" name="periodicidade" required>
            <?php foreach (($periodicidades ?? []) as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Tipo de evento</label>
          <select class="border rounded p-2 w-full" name="tipo_evento" id="cronogramaTipoEvento" required>
            <?php foreach (\App\Models\CronogramaEventoModel::eventTypeOptions() as $tipo): ?>
              <option value="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm" for="topico">Pilar</label>
          <input id="topico" class="border rounded p-2 w-full" type="text" name="topico" list="pilares_options" required />
          <datalist id="pilares_options">
            <?php foreach (($pilares ?? []) as $p): ?>
              <option value="<?= htmlspecialchars((string)($p['nome'] ?? '')) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div>
          <label class="block text-sm">Departamento</label>
          <input class="border rounded p-2 w-full" type="text" name="unidade" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Atividade</label>
          <input class="border rounded p-2 w-full" type="text" name="atividade" required />
        </div>
        <div>
          <label class="block text-sm">Responsável</label>
          <input class="border rounded p-2 w-full" type="text" name="responsavel" />
        </div>
        <div>
          <label class="block text-sm">Modelo</label>
          <select class="border rounded p-2 w-full" name="modelo">
            <option value="">—</option>
            <option value="Online">On-line</option>
            <option value="Presencial">Presencial</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Status</label>
          <select class="border rounded p-2 w-full" name="status">
            <option value="Planejado">Planejado</option>
            <option value="Pendente">Pendente</option>
            <option value="Andamento">Andamento</option>
            <option value="Adiado">Adiado</option>
          </select>
        </div>
      </div>
      <div id="reuniaoAnexosBlock" class="hidden">
        <div class="p-3 border rounded bg-gray-50 space-y-2">
          <div class="text-sm font-medium">Documentos da reunião</div>
          <div id="reuniaoAnexosError" class="hidden px-3 py-2 text-xs text-red-700 bg-red-100 rounded"></div>
          <input type="hidden" name="MAX_FILE_SIZE" value="<?= 20 * 1024 * 1024 ?>" />
          <input
            class="border rounded p-2 w-full"
            type="file"
            name="anexos[]"
            id="reuniaoAnexosInput"
            multiple
            accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.svg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml"
          />
          <div class="text-xs text-gray-600">Obrigatório anexar pelo menos um documento. Limite: 10 arquivos por envio, até 20MB por arquivo.</div>
        </div>
      </div>
      <p class="text-xs text-gray-500">A recorrência é gerada no momento do cadastro, apenas dentro do ano deste cronograma.</p>
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar evento</button>
    </form>
  </div>
</div>

<script>
  (function () {
    const form = document.getElementById('cronogramaAddEventoForm');
    const tipo = document.getElementById('cronogramaTipoEvento');
    const block = document.getElementById('reuniaoAnexosBlock');
    const input = document.getElementById('reuniaoAnexosInput');
    const error = document.getElementById('reuniaoAnexosError');

    const showError = (msg) => {
      if (!error) return;
      error.textContent = msg;
      error.classList.remove('hidden');
    };
    const clearError = () => {
      if (!error) return;
      error.textContent = '';
      error.classList.add('hidden');
    };
    const sync = () => {
      const isReuniao = tipo && tipo.value === 'Reunião';
      if (block) block.classList.toggle('hidden', !isReuniao);
      clearError();
    };

    if (tipo) tipo.addEventListener('change', sync);
    if (input) input.addEventListener('change', clearError);
    if (form) {
      form.addEventListener('submit', (e) => {
        const isReuniao = tipo && tipo.value === 'Reunião';
        if (!isReuniao) return;
        const count = input && input.files ? input.files.length : 0;
        if (count <= 0) {
          e.preventDefault();
          showError('Para salvar uma reunião, anexe pelo menos um documento.');
        }
      });
    }
    sync();
  })();
</script>
