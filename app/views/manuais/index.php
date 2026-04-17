<?php /** @var array $items */ /** @var array $clientes */ /** @var array $departamentos */ ?>
<?php /** @var int $selectedEmpresa */ /** @var int $selectedDepartamento */ /** @var string $searchNome */ ?>
<?php /** @var bool $canManageManuais */ ?>
<?php /** @var string $portalLink */ ?>
<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($basePath === '.' || $basePath === '/' || $basePath === '\\') { $basePath = ''; } ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Manuais</h1>
    <div class="flex items-center gap-2">
      <?php if ($canManageManuais): ?>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=manuais/create<?= $selectedEmpresa > 0 ? '&empresa_id=' . (int)$selectedEmpresa : '' ?>">Novo Manual</a>
      <?php endif; ?>
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>

  <?php if ($canManageManuais && $selectedEmpresa > 0): ?>
    <div class="bg-white shadow rounded p-4 mb-4">
      <div class="flex items-center justify-between">
        <div>
          <div class="font-semibold">Portal público de manuais</div>
          <div class="text-sm text-gray-600">Gere um link seguro para o cliente acessar somente os manuais.</div>
        </div>
        <form method="post" action="index.php?route=manuais/generatePortalLink">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="empresa_id" value="<?= (int)$selectedEmpresa ?>" />
          <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Gerar link</button>
        </form>
      </div>
      <?php if (!empty($portalLink)): ?>
        <div class="mt-3">
          <label class="block text-sm mb-1">Link</label>
          <div class="flex items-center gap-2">
            <input type="text" readonly class="border rounded p-2 w-full" id="portalLinkInput" value="<?= htmlspecialchars($portalLink) ?>" />
            <button type="button" class="px-3 py-2 rounded bg-gray-200 text-brand-brown" id="copyPortalLinkBtn">Copiar</button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="get" action="index.php" class="mb-4 bg-white shadow rounded p-4">
    <input type="hidden" name="route" value="manuais/index" />
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-4">
        <label class="block text-sm">Empresa</label>
        <select name="empresa_id" id="manualEmpresaFilter" class="border rounded p-2 w-full">
          <option value="">-- Todas --</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome_empresa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-4">
        <label class="block text-sm">Departamento</label>
        <select name="departamento_id" id="manualDepartamentoFilter" class="border rounded p-2 w-full">
          <option value="">-- Todos --</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= (int)$d['id'] ?>" data-empresa="<?= (int)$d['cliente_id'] ?>" <?= $selectedDepartamento === (int)$d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-4">
        <label class="block text-sm">Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($searchNome) ?>" maxlength="255" />
      </div>
    </div>
    <div class="mt-4 flex items-center gap-2 justify-end">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=manuais/index">Limpar</a>
    </div>
  </form>

  <div class="bg-white shadow rounded overflow-x-auto">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Nome</th>
          <th class="p-3">Descrição</th>
          <th class="p-3">Empresa</th>
          <th class="p-3">Departamento</th>
          <th class="p-3">Data</th>
          <th class="p-3">Download</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td class="p-4 text-sm text-gray-600" colspan="6">Nenhum manual encontrado para os filtros selecionados.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $manual): ?>
            <tr class="border-b">
              <td class="p-3 font-medium"><?= htmlspecialchars($manual['nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($manual['descricao'] ?? '—')) ?></td>
              <td class="p-3"><?= htmlspecialchars($manual['empresa_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars($manual['departamento_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$manual['created_at']))) ?></td>
              <td class="p-3">
                <a class="px-3 py-2 rounded bg-brand-red text-white inline-flex items-center gap-2" href="<?= $basePath ?>/manuais/download/<?= (int)$manual['id'] ?>">
                  <span data-feather="download"></span>
                  <span>Baixar</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  (function() {
    var copyBtn = document.getElementById('copyPortalLinkBtn');
    var linkInput = document.getElementById('portalLinkInput');
    if (copyBtn && linkInput && navigator && navigator.clipboard) {
      copyBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(linkInput.value || '');
      });
    } else if (copyBtn && linkInput) {
      copyBtn.addEventListener('click', function() {
        linkInput.select();
        document.execCommand('copy');
      });
    }

    const empresa = document.getElementById('manualEmpresaFilter');
    const departamento = document.getElementById('manualDepartamentoFilter');
    if (!empresa || !departamento) return;
    const sync = function() {
      const empresaId = empresa.value;
      Array.from(departamento.options).forEach(function(option, idx) {
        if (idx === 0) return;
        const matches = !empresaId || option.getAttribute('data-empresa') === empresaId;
        option.hidden = !matches;
        if (!matches && option.selected) {
          departamento.value = '';
        }
      });
    };
    empresa.addEventListener('change', sync);
    sync();
  })();
</script>
