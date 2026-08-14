<?php /** @var array $items */ /** @var string $searchNome */ ?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-900">Biblioteca — Atas</h1>
    <div class="flex items-center gap-2">
      <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-brand-red px-3 text-sm font-medium text-white hover:brightness-95" href="index.php?route=atas/create" title="Nova Ata" aria-label="Nova Ata">
        <span data-feather="plus" class="h-4 w-4"></span>
        <span>Nova Ata</span>
      </a>
      <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" href="javascript:history.back()" title="Voltar" aria-label="Voltar">
        <span data-feather="arrow-left" class="h-4 w-4"></span>
        <span>Voltar</span>
      </a>
    </div>
  </div>

  <div class="mb-4 flex items-center gap-1 border-b border-slate-200">
    <a class="px-3 py-2 text-sm font-medium text-slate-500 hover:text-slate-800" href="index.php?route=manuais/index">Manuais</a>
    <a class="px-3 py-2 text-sm font-medium text-brand-red border-b-2 border-brand-red -mb-px" href="index.php?route=atas/index">Atas</a>
  </div>

  <div class="text-xs text-slate-500 mb-4">Acervo interno do Instituto. Não aparece na Biblioteca do cliente nem no Portal público.</div>

  <form method="get" action="index.php" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <input type="hidden" name="route" value="atas/index" />
    <div class="flex flex-wrap items-end gap-3">
      <div class="w-full sm:w-auto sm:flex-1 sm:min-w-[220px]">
        <label class="mb-1 block text-xs font-medium text-slate-600">Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($searchNome) ?>" maxlength="255" class="h-9 w-full rounded-md border border-slate-300 px-3 text-sm" />
      </div>
      <div class="flex items-center gap-2">
        <button class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-brand-red px-3 text-sm font-medium text-white hover:brightness-95" type="submit">
          <span data-feather="filter" class="h-4 w-4"></span>
          <span>Filtrar</span>
        </button>
        <a class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" href="index.php?route=atas/index">
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
          <th class="w-[28%] px-3 py-3 text-xs font-semibold text-slate-600">Nome</th>
          <th class="w-[38%] px-3 py-3 text-xs font-semibold text-slate-600">Descrição</th>
          <th class="w-[12%] px-3 py-3 text-xs font-semibold text-slate-600">Enviado por</th>
          <th class="w-[10%] px-3 py-3 text-xs font-semibold text-slate-600">Data</th>
          <th class="w-[12%] px-3 py-3 text-right text-xs font-semibold text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td class="px-3 py-10" colspan="5">
              <div class="flex flex-col items-center justify-center gap-2 text-center">
                <span data-feather="inbox" class="h-6 w-6 text-slate-300"></span>
                <p class="text-sm text-slate-500">Nenhuma ata cadastrada.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $ata): ?>
            <tr class="border-b border-slate-100 transition-colors hover:bg-slate-50">
              <td class="px-3 py-3 align-top">
                <div class="line-clamp-2 text-sm font-semibold leading-5 text-slate-900" title="<?= htmlspecialchars($ata['nome']) ?>"><?= htmlspecialchars($ata['nome']) ?></div>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="line-clamp-2 max-w-md text-sm leading-5 text-slate-600"><?= htmlspecialchars((string)($ata['descricao'] ?? '—')) ?></div>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="max-w-[160px] truncate text-xs font-medium text-slate-600"><?= htmlspecialchars((string)($ata['usuario_nome'] ?? '—')) ?></div>
              </td>
              <td class="px-3 py-3 align-top whitespace-nowrap text-xs leading-5 text-slate-500">
                <?= htmlspecialchars(date('d/m/Y', strtotime((string)$ata['created_at']))) ?>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="flex items-center justify-end gap-1">
                  <a class="icon-action inline-flex h-10 w-10 sm:h-8 sm:w-8 items-center justify-center rounded-md text-brand-red" href="index.php?route=atas/download&id=<?= (int)$ata['id'] ?>" title="Baixar ata" aria-label="Baixar ata">
                    <span data-feather="download" class="h-4 w-4"></span>
                  </a>
                  <button type="button"
                          class="icon-action inline-flex h-10 w-10 sm:h-8 sm:w-8 items-center justify-center rounded-md text-brand-brown"
                          data-ata-delete
                          data-id="<?= (int)$ata['id'] ?>"
                          data-nome="<?= htmlspecialchars($ata['nome']) ?>"
                          title="Excluir ata" aria-label="Excluir ata">
                    <span data-feather="trash-2" class="h-4 w-4"></span>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="ataDeleteModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 p-4">
  <div class="bg-white rounded shadow max-w-lg w-full p-5">
    <div class="text-lg font-semibold text-brand-black">Confirmar exclusão</div>
    <div class="text-sm text-gray-700 mt-2">
      Você tem certeza que deseja excluir permanentemente a ata <span class="font-semibold" id="ataDeleteName"></span>?
    </div>
    <form method="post" action="index.php?route=atas/delete" class="mt-4 flex items-center justify-end gap-2">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id" id="ataDeleteId" value="" />
      <button type="button" class="px-4 py-2 rounded bg-gray-200 text-brand-brown" id="ataDeleteCancel">Cancelar</button>
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Excluir</button>
    </form>
  </div>
</div>
<script>
  (function () {
    const modal = document.getElementById('ataDeleteModal');
    const nameEl = document.getElementById('ataDeleteName');
    const idEl = document.getElementById('ataDeleteId');
    const cancelBtn = document.getElementById('ataDeleteCancel');
    if (!modal || !nameEl || !idEl) return;
    const open = function (id, nome) {
      idEl.value = String(id || '');
      nameEl.textContent = String(nome || '');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    };
    const close = function () {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    };
    document.querySelectorAll('[data-ata-delete]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        open(btn.getAttribute('data-id'), btn.getAttribute('data-nome'));
      });
    });
    if (cancelBtn) cancelBtn.addEventListener('click', close);
    modal.addEventListener('click', function (e) {
      if (e.target === modal) close();
    });
  })();
</script>
