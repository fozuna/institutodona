<?php /** @var array $items */ /** @var array $clientes */ /** @var array $departamentos */ ?>
<?php /** @var int $selectedEmpresa */ /** @var int $selectedDepartamento */ /** @var string $searchNome */ ?>
<?php /** @var bool $canManageManuais */ ?>
<?php /** @var bool $canDeleteManuais */ ?>
<?php /** @var array $portalLinks */ ?>
<?php /** @var string $selectedSortCol */ ?>
<?php /** @var string $selectedSortDir */ ?>
<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($basePath === '.' || $basePath === '/' || $basePath === '\\') { $basePath = ''; } ?>
<?php
$selectedSortCol = $selectedSortCol ?? 'data';
$selectedSortDir = $selectedSortDir ?? 'desc';
$sortIndicator = static function (string $column) use ($selectedSortCol, $selectedSortDir): string {
    if ($selectedSortCol !== $column) {
        return '↕';
    }
    return $selectedSortDir === 'asc' ? '↑' : '↓';
};
$hasActiveFilters = ($selectedEmpresa > 0) || ($selectedDepartamento > 0) || ($searchNome !== '');
?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-900">Biblioteca</h1>
    <div class="flex items-center gap-2">
      <?php if ($canManageManuais): ?>
        <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-brand-red px-3 text-sm font-medium text-white hover:brightness-95" href="index.php?route=manuais/create<?= $selectedEmpresa > 0 ? '&empresa_id=' . (int)$selectedEmpresa : '' ?>" title="Novo Item" aria-label="Novo Item">
          <span data-feather="plus" class="h-4 w-4"></span>
          <span>Novo Item</span>
        </a>
      <?php endif; ?>
      <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" href="javascript:history.back()" title="Voltar" aria-label="Voltar">
        <span data-feather="arrow-left" class="h-4 w-4"></span>
        <span>Voltar</span>
      </a>
    </div>
  </div>

  <?php if ($canManageManuais && $selectedEmpresa > 0): ?>
    <div class="rounded-lg border border-slate-200 bg-white p-4 mb-4 shadow-sm">
      <div class="flex items-center justify-between gap-3">
        <div>
          <div class="text-sm font-semibold text-slate-900">Portal público da biblioteca</div>
          <div class="text-xs text-slate-500 mt-0.5">Gere um link permanente para o cliente acessar somente os itens autorizados. O link continua funcionando até ser revogado.</div>
        </div>
        <form method="post" action="index.php?route=manuais/generatePortalLink">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="empresa_id" value="<?= (int)$selectedEmpresa ?>" />
          <input type="hidden" name="departamento_id" value="<?= (int)$selectedDepartamento ?>" />
          <input type="hidden" name="q" value="<?= htmlspecialchars($searchNome) ?>" />
          <button class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-brand-red px-3 text-sm font-medium text-white hover:brightness-95 whitespace-nowrap" type="submit">
            <span data-feather="link" class="h-4 w-4"></span>
            <span>Gerar link</span>
          </button>
        </form>
      </div>
      <?php if (!empty($portalLinks)): ?>
        <div class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
          <?php foreach ($portalLinks as $pt): ?>
            <div class="flex flex-col gap-2 py-2 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex flex-1 items-center gap-2 min-w-0">
                <input type="text" readonly
                       class="portal-link-input h-9 min-w-0 flex-1 rounded-md border border-slate-300 px-3 text-sm <?= $pt['ativo'] ? '' : 'text-slate-400 bg-slate-50' ?>"
                       value="<?= htmlspecialchars($pt['link']) ?>" />
                <button type="button" class="copy-portal-link-btn inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 whitespace-nowrap" title="Copiar">
                  <span data-feather="copy" class="h-4 w-4"></span>
                </button>
              </div>
              <div class="flex shrink-0 items-center gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 font-medium <?= $pt['ativo'] ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' ?>">
                  <?= htmlspecialchars($pt['status_label']) ?>
                </span>
                <span><?= htmlspecialchars((string)$pt['created_at']) ?></span>
                <?php if ($pt['ativo']): ?>
                  <form method="post" action="index.php?route=manuais/revokePortalLink" onsubmit="return confirm('Revogar este link? Ele deixará de funcionar imediatamente.');">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="empresa_id" value="<?= (int)$selectedEmpresa ?>" />
                    <input type="hidden" name="token_id" value="<?= (int)$pt['id'] ?>" />
                    <button type="submit" class="inline-flex h-8 items-center justify-center gap-1 rounded-md border border-red-200 bg-white px-2 text-xs font-medium text-red-600 hover:bg-red-50 whitespace-nowrap">
                      <span data-feather="slash" class="h-3.5 w-3.5"></span>
                      <span>Revogar</span>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="get" action="index.php" id="manualFiltersForm" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <input type="hidden" name="route" value="manuais/index" />
    <input type="hidden" name="sort_col" id="manualSortCol" value="<?= htmlspecialchars($selectedSortCol) ?>" />
    <input type="hidden" name="sort_dir" id="manualSortDir" value="<?= htmlspecialchars($selectedSortDir) ?>" />
    <div class="flex flex-wrap items-end gap-3">
      <div class="w-full sm:w-auto sm:flex-1 sm:min-w-[180px]">
        <label class="mb-1 block text-xs font-medium text-slate-600">Empresa</label>
        <select name="empresa_id" id="manualEmpresaFilter" class="h-9 w-full rounded-md border border-slate-300 px-3 text-sm">
          <option value="">-- Todas --</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $selectedEmpresa === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome_empresa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-full sm:w-auto sm:flex-1 sm:min-w-[180px]">
        <label class="mb-1 block text-xs font-medium text-slate-600">Departamento</label>
        <select name="departamento_id" id="manualDepartamentoFilter" class="h-9 w-full rounded-md border border-slate-300 px-3 text-sm">
          <option value="">-- Todos --</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= (int)$d['id'] ?>" data-empresa="<?= (int)$d['cliente_id'] ?>" <?= $selectedDepartamento === (int)$d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-full sm:w-auto sm:flex-1 sm:min-w-[200px]">
        <label class="mb-1 block text-xs font-medium text-slate-600">Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($searchNome) ?>" maxlength="255" class="h-9 w-full rounded-md border border-slate-300 px-3 text-sm" />
      </div>
      <div class="flex items-center gap-2">
        <button class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-brand-red px-3 text-sm font-medium text-white hover:brightness-95" type="submit">
          <span data-feather="filter" class="h-4 w-4"></span>
          <span>Filtrar</span>
        </button>
        <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" href="index.php?route=manuais/index">
          <span data-feather="x-circle" class="h-4 w-4"></span>
          <span>Limpar</span>
        </a>
      </div>
    </div>
  </form>

  <div class="bg-white shadow rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left border-b border-slate-200">
          <th class="w-[20%] px-3 py-3">
            <button type="button" class="manual-sort-trigger inline-flex items-center gap-1 text-xs font-semibold text-slate-600" data-col="nome">
              <span>Nome</span>
              <span class="text-[11px] leading-none text-slate-400"><?= $sortIndicator('nome') ?></span>
            </button>
          </th>
          <th class="w-[38%] px-3 py-3">
            <button type="button" class="manual-sort-trigger inline-flex items-center gap-1 text-xs font-semibold text-slate-600" data-col="descricao">
              <span>Descrição</span>
              <span class="text-[11px] leading-none text-slate-400"><?= $sortIndicator('descricao') ?></span>
            </button>
          </th>
          <th class="w-[14%] px-3 py-3">
            <button type="button" class="manual-sort-trigger inline-flex items-center gap-1 text-xs font-semibold text-slate-600" data-col="empresa">
              <span>Empresa</span>
              <span class="text-[11px] leading-none text-slate-400"><?= $sortIndicator('empresa') ?></span>
            </button>
          </th>
          <th class="w-[12%] px-3 py-3">
            <button type="button" class="manual-sort-trigger inline-flex items-center gap-1 text-xs font-semibold text-slate-600" data-col="departamento">
              <span>Departamento</span>
              <span class="text-[11px] leading-none text-slate-400"><?= $sortIndicator('departamento') ?></span>
            </button>
          </th>
          <th class="w-[9%] px-3 py-3">
            <button type="button" class="manual-sort-trigger inline-flex items-center gap-1 text-xs font-semibold text-slate-600" data-col="data">
              <span>Data</span>
              <span class="text-[11px] leading-none text-slate-400"><?= $sortIndicator('data') ?></span>
            </button>
          </th>
          <th class="w-[7%] px-3 py-3 text-right text-xs font-semibold text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td class="px-3 py-10" colspan="6">
              <div class="flex flex-col items-center justify-center gap-2 text-center">
                <span data-feather="inbox" class="h-6 w-6 text-slate-300"></span>
                <p class="text-sm text-slate-500">Nenhum documento encontrado para os filtros selecionados.</p>
                <?php if ($hasActiveFilters): ?>
                  <a href="index.php?route=manuais/index" class="mt-1 inline-flex h-8 items-center rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-600 hover:bg-slate-50">Limpar filtros</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $manual): ?>
            <tr class="border-b border-slate-100 transition-colors hover:bg-slate-50">
              <td class="px-3 py-3 align-top">
                <div class="line-clamp-2 text-sm font-semibold leading-5 text-slate-900" title="<?= htmlspecialchars($manual['nome']) ?>"><?= htmlspecialchars($manual['nome']) ?></div>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="line-clamp-2 max-w-md text-sm leading-5 text-slate-600"><?= htmlspecialchars((string)($manual['descricao'] ?? '—')) ?></div>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="max-w-[180px] truncate text-xs font-medium text-slate-600" title="<?= htmlspecialchars($manual['empresa_nome']) ?>"><?= htmlspecialchars($manual['empresa_nome']) ?></div>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="max-w-[160px] truncate text-xs font-medium text-slate-600" title="<?= htmlspecialchars($manual['departamento_nome']) ?>"><?= htmlspecialchars($manual['departamento_nome']) ?></div>
              </td>
              <td class="px-3 py-3 align-top whitespace-nowrap text-xs leading-5 text-slate-500">
                <?= htmlspecialchars(date('d/m/Y', strtotime((string)$manual['created_at']))) ?><br />
                <?= htmlspecialchars(date('H:i', strtotime((string)$manual['created_at']))) ?>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="flex items-center justify-end gap-1">
                  <a class="icon-action inline-flex h-10 w-10 sm:h-8 sm:w-8 items-center justify-center rounded-md text-brand-red" href="<?= $basePath ?>/biblioteca/download/<?= (int)$manual['id'] ?>" title="Baixar documento" aria-label="Baixar documento">
                    <span data-feather="download" class="h-4 w-4"></span>
                  </a>
                  <?php if ($canManageManuais): ?>
                    <a class="icon-action inline-flex h-10 w-10 sm:h-8 sm:w-8 items-center justify-center rounded-md text-brand-pink" href="index.php?route=manuais/edit&id=<?= (int)$manual['id'] ?>" title="Editar documento" aria-label="Editar documento">
                      <span data-feather="edit-2" class="h-4 w-4"></span>
                    </a>
                  <?php endif; ?>
                  <?php if ($canDeleteManuais): ?>
                    <button type="button"
                            class="icon-action inline-flex h-10 w-10 sm:h-8 sm:w-8 items-center justify-center rounded-md text-brand-brown"
                            data-manual-delete
                            data-id="<?= (int)$manual['id'] ?>"
                            data-nome="<?= htmlspecialchars($manual['nome']) ?>"
                            title="Excluir documento" aria-label="Excluir documento">
                      <span data-feather="trash-2" class="h-4 w-4"></span>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if ($canDeleteManuais): ?>
  <div id="manualDeleteModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded shadow max-w-lg w-full p-5">
      <div class="text-lg font-semibold text-brand-black">Confirmar exclusão</div>
      <div class="text-sm text-gray-700 mt-2">
        Você tem certeza que deseja excluir permanentemente o manual <span class="font-semibold" id="manualDeleteName"></span>?
      </div>
      <form method="post" action="index.php?route=manuais/delete" class="mt-4 flex items-center justify-end gap-2">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="id" id="manualDeleteId" value="" />
        <button type="button" class="px-4 py-2 rounded bg-gray-200 text-brand-brown" id="manualDeleteCancel">Cancelar</button>
        <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Excluir</button>
      </form>
    </div>
  </div>
<?php endif; ?>
<script>
  (function() {
    const clientesMap = <?= json_encode(array_values($clientes), JSON_UNESCAPED_UNICODE) ?>;
    const byId = {};
    (clientesMap || []).forEach(function(c) { byId[String(c.id)] = c; });

    document.querySelectorAll('.copy-portal-link-btn').forEach(function(copyBtn) {
      var wrap = copyBtn.closest('.flex');
      var linkInput = wrap ? wrap.querySelector('.portal-link-input') : null;
      if (!linkInput) return;
      copyBtn.addEventListener('click', function() {
        if (navigator && navigator.clipboard) {
          navigator.clipboard.writeText(linkInput.value || '');
        } else {
          linkInput.select();
          document.execCommand('copy');
        }
      });
    });

    const empresa = document.getElementById('manualEmpresaFilter');
    const departamento = document.getElementById('manualDepartamentoFilter');
    const filterForm = document.getElementById('manualFiltersForm');
    const sortCol = document.getElementById('manualSortCol');
    const sortDir = document.getElementById('manualSortDir');
    if (!empresa || !departamento || !filterForm || !sortCol || !sortDir) return;
    const sync = function() {
      const empresaId = empresa.value;
      const info = empresaId ? byId[String(empresaId)] : null;
      const allowed = {};
      if (empresaId) allowed[String(empresaId)] = true;
      if (info && Number(info.is_matriz || 0) !== 1 && Number(info.matriz_id || 0) > 0) {
        allowed[String(info.matriz_id)] = true;
      }
      Array.from(departamento.options).forEach(function(option, idx) {
        if (idx === 0) return;
        const depEmpresa = option.getAttribute('data-empresa');
        const matches = !empresaId || !!allowed[String(depEmpresa)];
        option.hidden = !matches;
        if (!matches && option.selected) {
          departamento.value = '';
        }
      });
    };
    empresa.addEventListener('change', sync);
    sync();
    document.querySelectorAll('.manual-sort-trigger').forEach(function(button) {
      button.addEventListener('click', function() {
        const column = button.getAttribute('data-col') || 'data';
        if (sortCol.value === column) {
          sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
          sortCol.value = column;
          sortDir.value = column === 'data' ? 'desc' : 'asc';
        }
        filterForm.submit();
      });
    });

    const modal = document.getElementById('manualDeleteModal');
    const nameEl = document.getElementById('manualDeleteName');
    const idEl = document.getElementById('manualDeleteId');
    const cancelBtn = document.getElementById('manualDeleteCancel');
    if (modal && nameEl && idEl) {
      const open = function(id, nome) {
        idEl.value = String(id || '');
        nameEl.textContent = String(nome || '');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      };
      const close = function() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };
      document.querySelectorAll('[data-manual-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          open(btn.getAttribute('data-id'), btn.getAttribute('data-nome'));
        });
      });
      if (cancelBtn) cancelBtn.addEventListener('click', close);
      modal.addEventListener('click', function(e) {
        if (e.target === modal) close();
      });
    }
  })();
</script>
