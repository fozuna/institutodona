<?php /** @var array $items */ /** @var array $order */ /** @var bool $isClientOrderable */ /** @var string|null $flashSuccess */ /** @var string|null $flashError */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Cronogramas</h1>
    <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=cronograma/create">Novo Cronograma</a>
  </div>
  <?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800 text-sm"><?= htmlspecialchars((string)$flashSuccess) ?></div>
  <?php endif; ?>
  <?php if (!empty($flashError)): ?>
    <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm"><?= htmlspecialchars((string)$flashError) ?></div>
  <?php endif; ?>
  <input type="hidden" id="cronogramaIndexCsrf" value="<?= \App\Core\Security::csrfToken() ?>" />
  <div id="cronogramaIndexToast" class="hidden mb-4 p-3 rounded border text-sm"></div>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <?php
            $order = $order ?? ['column' => 'cliente', 'direction' => 'asc'];
            $sortColumn = $order['column'] ?? 'cliente';
            $sortDirection = $order['direction'] ?? 'asc';
            $baseParams = $_GET;
            $baseParams['route'] = 'cronograma/index';
          ?>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'cliente' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'cliente', 'dir' => $nextDir]);
              $active = $sortColumn === 'cliente';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Cliente
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'nome' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'nome', 'dir' => $nextDir]);
              $active = $sortColumn === 'nome';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Nome
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'ano' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'ano', 'dir' => $nextDir]);
              $active = $sortColumn === 'ano';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Ano
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $c): ?>
          <tr class="border-b">
            <td class="p-3"><?= htmlspecialchars($c['cliente'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['nome'] ?? '') ?></td>
            <td class="p-3"><?= (int)$c['ano'] ?></td>
            <td class="p-3">
              <div class="flex items-center gap-2">
                <a class="icon-btn" title="Visualizar detalhes" aria-label="Visualizar detalhes" href="index.php?route=cronograma/show&id=<?= (int)$c['id'] ?>"><span data-feather="eye"></span></a>
                <a class="icon-btn" title="Editar" aria-label="Editar" href="index.php?route=cronograma/edit&id=<?= (int)$c['id'] ?>"><span data-feather="edit-2"></span></a>
                <button type="button" class="icon-btn" title="Duplicar" aria-label="Duplicar" data-cron-action="duplicate" data-id="<?= (int)$c['id'] ?>" data-nome="<?= htmlspecialchars((string)($c['nome'] ?? 'Cronograma')) ?>"><span data-feather="copy"></span></button>
                <button type="button" class="icon-btn" title="Excluir" aria-label="Excluir" data-cron-action="delete" data-id="<?= (int)$c['id'] ?>" data-nome="<?= htmlspecialchars((string)($c['nome'] ?? 'Cronograma')) ?>"><span data-feather="trash-2"></span></button>
                <button
                  type="button"
                  class="px-3 py-2 rounded text-xs border <?= !empty($c['ativo']) ? 'bg-green-50 border-green-200 text-green-800' : 'bg-gray-50 border-gray-200 text-gray-700' ?>"
                  title="Ativar/Inativar"
                  aria-label="Ativar/Inativar"
                  data-cron-action="toggle"
                  data-id="<?= (int)$c['id'] ?>"
                  data-ativo="<?= !empty($c['ativo']) ? '1' : '0' ?>"
                ><?= !empty($c['ativo']) ? 'Ativo' : 'Inativo' ?></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  (function () {
    const csrf = document.getElementById('cronogramaIndexCsrf');
    const toast = document.getElementById('cronogramaIndexToast');

    function showToast(ok, msg) {
      if (!toast) return;
      toast.classList.remove('hidden');
      toast.className = 'mb-4 p-3 rounded border text-sm ' + (ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800');
      toast.textContent = msg || (ok ? 'Ação executada com sucesso.' : 'Falha ao executar ação.');
    }

    async function post(route, body) {
      const params = new URLSearchParams();
      Object.keys(body).forEach((k) => params.append(k, String(body[k])));
      const res = await fetch('index.php?route=' + route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
        body: params.toString()
      });
      const payload = await res.json().catch(() => null);
      if (!payload || !payload.ok) {
        throw new Error((payload && payload.message) ? payload.message : 'Erro na requisição.');
      }
      return payload;
    }

    document.addEventListener('click', async (e) => {
      const btn = e.target && e.target.closest ? e.target.closest('[data-cron-action]') : null;
      if (!btn) return;
      const action = btn.getAttribute('data-cron-action');
      const id = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (!id || !csrf) return;
      const nome = btn.getAttribute('data-nome') || 'Cronograma';

      try {
        if (action === 'delete') {
          if (!confirm('Excluir o cronograma "' + nome + '"? Esta ação também remove os eventos vinculados.')) return;
          const payload = await post('cronograma/delete', { csrf: csrf.value, id });
          showToast(true, payload.message);
          const row = btn.closest('tr');
          if (row) row.remove();
          if (window.feather && window.feather.replace) window.feather.replace();
          return;
        }
        if (action === 'duplicate') {
          const payload = await post('cronograma/duplicate', { csrf: csrf.value, id });
          showToast(true, payload.message);
          if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
          } else {
            window.location.reload();
          }
          return;
        }
        if (action === 'toggle') {
          const payload = await post('cronograma/toggleAtivo', { csrf: csrf.value, id });
          showToast(true, payload.message);
          const ativo = String(payload.ativo || '0') === '1';
          btn.dataset.ativo = ativo ? '1' : '0';
          btn.textContent = ativo ? 'Ativo' : 'Inativo';
          btn.className = 'px-3 py-2 rounded text-xs border ' + (ativo ? 'bg-green-50 border-green-200 text-green-800' : 'bg-gray-50 border-gray-200 text-gray-700');
        }
      } catch (err) {
        showToast(false, err && err.message ? err.message : 'Erro ao executar ação.');
      }
    });
  })();
</script>
