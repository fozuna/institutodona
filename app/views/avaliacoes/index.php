<?php /** @var array $items */ /** @var array $clientes */ ?>
<?php
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $baseUrl = rtrim(dirname($scriptName), '/\\');
  if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $configuredPublicBaseUrl = trim((string)(getenv('PUBLIC_AVALIACOES_STATIC_URL') ?: getenv('PUBLIC_EVALUATION_BASE_URL') ?: ''));
  if ($configuredPublicBaseUrl !== '') {
    $publicBaseUrl = rtrim($configuredPublicBaseUrl, '/');
  } else {
    $publicBaseUrl = $scheme . '://' . $host . $baseUrl . '/public/avaliacoes.php';
  }
  $generatedLink = $_SESSION['generated_public_link'] ?? null;
  unset($_SESSION['generated_public_link']);
  $generatedUrl = (string)($generatedLink['url'] ?? $publicBaseUrl);
  $generatedEmpresa = (string)($generatedLink['empresa'] ?? '');
  $generatedAvaliacaoId = (int)($generatedLink['avaliacao_id'] ?? 0);
  $generatedPublicId = (int)($generatedLink['public_id'] ?? 0);
  $generatedExpiracao = '';
  $generatedPermanent = true;
  $defaultSelectedAvaliacaoId = !empty($items) ? (int)($items[0]['id'] ?? 0) : 0;
  $csrfToken = \App\Core\Security::csrfToken();
  $csrfShareToken = $csrfToken;
  $csrfDeleteToken = $csrfToken;
  $currentUser = $_SESSION['user'] ?? null;
  $canCreate = \App\Core\AccessControl::canAccessRoute('avaliacoes/create', 'GET', $currentUser);
  $canDelete = \App\Core\AccessControl::canAccessRoute('avaliacoes/delete-ajax', 'POST', $currentUser);
  $shareText = $generatedEmpresa !== '' ? ('Olá! Segue o formulário público da empresa ' . $generatedEmpresa . ': ' . $generatedUrl) : ('Olá! Segue o formulário público de avaliação: ' . $generatedUrl);
  $mailtoHref = $generatedUrl !== '' ? ('mailto:?subject=' . rawurlencode('Link da avaliação pública') . '&body=' . rawurlencode($shareText)) : '';
  $whatsHref = $generatedUrl !== '' ? ('https://wa.me/?text=' . rawurlencode($shareText)) : '';
  $linkedinHref = $generatedUrl !== '' ? ('https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($generatedUrl)) : '';
  $facebookHref = $generatedUrl !== '' ? ('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($generatedUrl)) : '';
  $statusMap = [
    'pendente' => ['label' => 'Pendente', 'class' => 'text-yellow-700 bg-yellow-100'],
    'iniciada' => ['label' => 'Iniciada', 'class' => 'text-blue-700 bg-blue-100'],
    'concluida' => ['label' => 'Concluída', 'class' => 'text-green-700 bg-green-100'],
  ];
?>
<div class="p-6">
  <div id="avaliacoes-ui-status" class="sr-only" role="status" aria-live="polite"></div>
  <div id="avaliacoes-toast" class="hidden mb-4 px-4 py-3 rounded relative" role="alert" aria-live="polite"></div>
  <div class="flex flex-col gap-3 mb-4 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold">Avaliações</h1>
      <p class="text-sm text-gray-600">O formulário público utiliza um endpoint fixo, permanente e sem geração dinâmica de links.</p>
    </div>
    <div class="flex flex-col gap-2 xl:min-w-[520px]">
      <label for="top-public-link" class="text-xs font-medium text-gray-600">Link público para compartilhar</label>
      <div class="flex items-center gap-2">
        <input id="top-public-link" class="border rounded p-2 w-full bg-white text-sm" value="<?= htmlspecialchars($publicBaseUrl) ?>" readonly />
        <button type="button"
                class="icon-btn icon-btn--lg icon-btn--muted btn-copy-link"
                data-link="<?= htmlspecialchars($publicBaseUrl) ?>"
                data-avaliacao-id="0"
                title="Copiar link público"
                aria-label="Copiar link público"><span data-feather="copy"></span><span class="sr-only">Copiar link público</span></button>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?= htmlspecialchars($publicBaseUrl) ?>"
         target="_blank"
         rel="noopener"
         class="icon-btn icon-btn--lg icon-btn--muted text-brand-red"
         title="Abrir formulário público"
         aria-label="Abrir formulário público"><span data-feather="external-link"></span><span class="sr-only">Abrir formulário público</span></a>
      <?php if ($canCreate): ?>
      <a class="icon-btn icon-btn--lg icon-btn--primary"
         href="index.php?route=avaliacoes/create"
         title="Nova avaliação"
         aria-label="Nova avaliação"><span data-feather="plus"></span><span class="sr-only">Nova avaliação</span></a>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($generatedUrl !== ''): ?>
    <div id="generated-link-panel" class="mb-4 rounded border border-green-200 bg-green-50 p-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
          <div class="text-sm font-semibold text-green-800">Formulário público fixo pronto para compartilhamento</div>
          <div id="generated-link-feedback" class="text-sm text-green-700 mt-1" role="status" aria-live="polite">Copiando automaticamente para a área de transferência...</div>
          <div class="mt-2 text-xs text-gray-600">
            <?php if ($generatedEmpresa !== ''): ?>Empresa: <?= htmlspecialchars($generatedEmpresa) ?> · <?php endif; ?>
            Endpoint fixo sem token e sem expiração
          </div>
          <div class="mt-3 flex items-center gap-2">
            <input id="generated-public-link" class="border rounded p-2 w-full bg-white text-sm" value="<?= htmlspecialchars($generatedUrl) ?>" readonly />
          </div>
        </div>
        <div class="flex flex-wrap gap-2 xl:justify-end">
          <button type="button"
                  class="icon-btn icon-btn--lg icon-btn--primary btn-share-action"
                  data-channel="copy"
                  data-url="<?= htmlspecialchars($generatedUrl) ?>"
                  data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
                  data-public-id="<?= $generatedPublicId ?>"
                  title="Copiar link"
                  aria-label="Copiar link"><span data-feather="copy"></span><span class="sr-only">Copiar link</span></button>
          <a class="icon-btn icon-btn--lg icon-btn--muted text-brand-brown share-link-anchor"
             href="<?= htmlspecialchars($mailtoHref) ?>"
             data-channel="email"
             data-url="<?= htmlspecialchars($generatedUrl) ?>"
             data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
             data-public-id="<?= $generatedPublicId ?>"
             title="Enviar por e-mail"
             aria-label="Enviar por e-mail"><span data-feather="mail"></span><span class="sr-only">Enviar por e-mail</span></a>
          <a class="icon-btn icon-btn--lg icon-btn--muted text-brand-brown share-link-anchor"
             href="<?= htmlspecialchars($whatsHref) ?>"
             target="_blank"
             rel="noopener"
             data-channel="whatsapp"
             data-url="<?= htmlspecialchars($generatedUrl) ?>"
             data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
             data-public-id="<?= $generatedPublicId ?>"
             title="Compartilhar no WhatsApp"
             aria-label="Compartilhar no WhatsApp"><span data-feather="message-circle"></span><span class="sr-only">Compartilhar no WhatsApp</span></a>
          <a class="icon-btn icon-btn--lg icon-btn--muted text-brand-brown share-link-anchor"
             href="<?= htmlspecialchars($linkedinHref) ?>"
             target="_blank"
             rel="noopener"
             data-channel="linkedin"
             data-url="<?= htmlspecialchars($generatedUrl) ?>"
             data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
             data-public-id="<?= $generatedPublicId ?>"
             title="Compartilhar no LinkedIn"
             aria-label="Compartilhar no LinkedIn"><span data-feather="linkedin"></span><span class="sr-only">Compartilhar no LinkedIn</span></a>
          <a class="icon-btn icon-btn--lg icon-btn--muted text-brand-brown share-link-anchor"
             href="<?= htmlspecialchars($facebookHref) ?>"
             target="_blank"
             rel="noopener"
             data-channel="facebook"
             data-url="<?= htmlspecialchars($generatedUrl) ?>"
             data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
             data-public-id="<?= $generatedPublicId ?>"
             title="Compartilhar no Facebook"
             aria-label="Compartilhar no Facebook"><span data-feather="facebook"></span><span class="sr-only">Compartilhar no Facebook</span></a>
          <a class="icon-btn icon-btn--lg icon-btn--muted text-brand-red share-link-anchor"
             href="<?= htmlspecialchars($generatedUrl) ?>"
             target="_blank"
             rel="noopener"
             data-channel="open"
             data-url="<?= htmlspecialchars($generatedUrl) ?>"
             data-avaliacao-id="<?= $generatedAvaliacaoId ?>"
             data-public-id="<?= $generatedPublicId ?>"
             title="Abrir link"
             aria-label="Abrir link"><span data-feather="external-link"></span><span class="sr-only">Abrir link</span></a>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <div class="bg-white shadow rounded overflow-x-auto">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Empresa</th>
          <th class="p-3">Nome Cliente</th>
          <th class="p-3">Data</th>
          <th class="p-3">EU</th>
          <th class="p-3">Liderança</th>
          <th class="p-3">Processo</th>
          <th class="p-3">Gestão</th>
          <th class="p-3">Formulário Público</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i):
          $nm = $i['cliente_nome'] ?? ($i['empresa_nome'] ?? '—');
          $isPotencial = (int)($i['cliente_id'] ?? 0) <= 0;
          $publicLink = $publicBaseUrl;
        ?>
          <tr class="border-b cursor-pointer row-avaliacao <?= (int)$i['id'] === $defaultSelectedAvaliacaoId ? 'bg-red-50' : '' ?>" data-avaliacao-id="<?= (int)$i['id'] ?>" data-empresa="<?= htmlspecialchars($nm) ?>">
            <td class="p-3">
              <div><?= htmlspecialchars($nm) ?></div>
              <?php if ($isPotencial): ?>
                <div class="mt-1"><span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800">Potencial cliente</span></div>
              <?php elseif (!empty($i['cliente_associado_em'])): ?>
                <div class="mt-1 text-xs text-gray-500">Associado em <?= htmlspecialchars(date('d/m/Y', strtotime($i['cliente_associado_em']))) ?></div>
              <?php endif; ?>
            </td>
            <td class="p-3"><?= htmlspecialchars((string)($i['nome'] ?? '—')) ?></td>
            <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($i['created_at']))) ?></td>
            <td class="p-3"><?= (int)$i['nota_financeiro'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_mercado'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_pessoas'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_processo'] ?>/7</td>
            <td class="p-3">
              <?php if ($publicLink !== ''): ?>
                <div class="flex items-center gap-2">
                  <a class="icon-btn icon-btn--muted text-brand-red"
                     href="<?= htmlspecialchars($publicLink) ?>"
                     target="_blank"
                     rel="noopener"
                     title="Abrir formulário público"
                     aria-label="Abrir formulário público"><span data-feather="external-link"></span><span class="sr-only">Abrir formulário público</span></a>
                  <button type="button"
                          class="icon-btn icon-btn--muted btn-copy-link"
                          data-link="<?= htmlspecialchars($publicLink) ?>"
                          data-avaliacao-id="<?= (int)$i['id'] ?>"
                          title="Copiar link público"
                          aria-label="Copiar link público"><span data-feather="copy"></span><span class="sr-only">Copiar link público</span></button>
                </div>
                <div class="text-xs text-gray-500 mt-1">Endpoint fixo</div>
              <?php else: ?>
                <span class="text-xs text-gray-500">Indisponível</span>
              <?php endif; ?>
            </td>
            <td class="p-3">
              <a class="text-brand-pink icon-action" href="index.php?route=avaliacoes/show&id=<?= (int)$i['id'] ?>" title="Ver" aria-label="Ver"><span data-feather="bar-chart-2"></span></a>
              <a class="text-brand-pink icon-action ml-2" href="index.php?route=avaliacoes/relatorio_pdf&id=<?= (int)$i['id'] ?>&download=1" title="Baixar PDF" aria-label="Baixar PDF"><span data-feather="download"></span></a>
              <?php if ($canDelete): ?>
                <button type="button"
                        class="text-brand-red icon-action ml-2 btn-delete-avaliacao"
                        data-avaliacao-id="<?= (int)$i['id'] ?>"
                        data-avaliacao-nome="<?= htmlspecialchars($nm) ?>"
                        title="Excluir"
                        aria-label="Excluir"><span data-feather="trash-2"></span><span class="sr-only">Excluir</span></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div id="avaliacaoDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="avaliacaoDeleteTitle">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden">
      <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
        <h3 id="avaliacaoDeleteTitle" class="text-lg font-medium text-gray-900">Confirmar exclusão</h3>
        <button type="button" class="text-gray-400 hover:text-gray-600" data-delete-close aria-label="Fechar">&times;</button>
      </div>
      <div class="p-6">
        <div id="avaliacaoDeleteMessage" class="text-sm text-gray-700"></div>
        <div class="flex justify-end pt-4 gap-2">
          <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors" data-delete-cancel>Cancelar</button>
          <button type="button" class="px-4 py-2 bg-brand-red text-white rounded hover:bg-red-700 transition-colors flex items-center gap-2" data-delete-confirm>
            <span data-delete-spinner class="hidden animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
            <span data-delete-confirm-icon class="w-4 h-4 inline-flex items-center justify-center"><span data-feather="trash-2"></span></span>
            <span data-delete-confirm-text>Excluir</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function(){
    const csrfShareToken = <?= json_encode($csrfShareToken, JSON_UNESCAPED_UNICODE) ?>;
    const csrfDeleteToken = <?= json_encode($csrfDeleteToken, JSON_UNESCAPED_UNICODE) ?>;
    async function logShare(channel, url, avaliacaoId, publicId, success) {
      if ((!avaliacaoId && !publicId) || !channel) return;
      const payload = new URLSearchParams();
      payload.set('csrf', csrfShareToken);
      payload.set('avaliacao_id', String(avaliacaoId));
      payload.set('public_id', String(publicId));
      payload.set('channel', channel);
      payload.set('url', url || '');
      payload.set('success', success ? '1' : '0');
      try {
        await fetch('index.php?route=avaliacoes/log-link-share', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString(),
          credentials: 'same-origin',
          keepalive: true
        });
      } catch (e) {}
    }
    const feedbackEl = document.getElementById('generated-link-feedback');
    const generatedLinkInput = document.getElementById('generated-public-link');
    const generatedPanel = document.getElementById('generated-link-panel');
    const generatedCopyBtn = generatedPanel ? generatedPanel.querySelector('[data-channel="copy"]') : null;
    const uiStatus = document.getElementById('avaliacoes-ui-status');
    const toastEl = document.getElementById('avaliacoes-toast');
    function status(msg) { if (uiStatus) uiStatus.textContent = msg || ''; }
    function toast(kind, message) {
      if (!toastEl) return;
      const isOk = kind === 'success';
      toastEl.className = 'mb-4 px-4 py-3 rounded relative ' + (isOk
        ? 'bg-green-100 border border-green-400 text-green-700'
        : 'bg-red-100 border border-red-400 text-red-700');
      toastEl.textContent = message || (isOk ? 'Sucesso.' : 'Erro.');
      toastEl.classList.remove('hidden');
      window.clearTimeout(toastEl.__hideTimer || 0);
      toastEl.__hideTimer = window.setTimeout(() => {
        toastEl.classList.add('hidden');
      }, isOk ? 2500 : 5000);
    }
    function setTemporaryTitle(el, title, ms) {
      if (!el) return;
      const prev = el.getAttribute('data-prev-title') || el.getAttribute('title') || '';
      el.setAttribute('data-prev-title', prev);
      el.setAttribute('title', title);
      window.setTimeout(() => {
        const restore = el.getAttribute('data-prev-title') || '';
        el.setAttribute('title', restore);
      }, ms || 1500);
    }
    function fallbackCopyText(text) {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.setAttribute('readonly', 'readonly');
      textarea.style.position = 'fixed';
      textarea.style.top = '-9999px';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      textarea.setSelectionRange(0, textarea.value.length);
      let ok = false;
      try {
        ok = document.execCommand('copy');
      } catch (e) {
        ok = false;
      }
      document.body.removeChild(textarea);
      return ok;
    }
    async function copyTextRobust(text) {
      if (navigator.clipboard && window.isSecureContext) {
        try {
          await navigator.clipboard.writeText(text);
          return true;
        } catch (e) {}
      }
      return fallbackCopyText(text);
    }
    if (generatedLinkInput && feedbackEl) {
      copyTextRobust(generatedLinkInput.value).then((copied) => {
        if (copied) {
          feedbackEl.textContent = 'Link copiado automaticamente com sucesso.';
          if (generatedCopyBtn) {
            setTemporaryTitle(generatedCopyBtn, 'Copiado', 1600);
          }
          status('Link copiado para a área de transferência.');
          logShare('auto_copy', generatedLinkInput.value, <?= (int)$generatedAvaliacaoId ?>, <?= (int)$generatedPublicId ?>, true);
        } else {
          feedbackEl.textContent = 'Não foi possível copiar automaticamente. Use as opções abaixo para compartilhar.';
          status('Falha ao copiar link automaticamente.');
          logShare('auto_copy', generatedLinkInput.value, <?= (int)$generatedAvaliacaoId ?>, <?= (int)$generatedPublicId ?>, false);
        }
      });
    }
    const rows = document.querySelectorAll('.row-avaliacao');
    function selectRow(row) {
      rows.forEach((item) => {
        item.classList.remove('bg-red-50');
      });
      row.classList.add('bg-red-50');
    }
    rows.forEach((row) => {
      row.addEventListener('click', (event) => {
        if (event.target.closest('a,button,form')) return;
        selectRow(row);
      });
    });
    if (rows.length > 0) {
      selectRow(rows[0]);
    }
    document.querySelectorAll('.btn-copy-link').forEach((btn)=>{
      btn.addEventListener('click', async () => {
        const link = btn.getAttribute('data-link') || '';
        const avaliacaoId = Number(btn.getAttribute('data-avaliacao-id') || '0');
        const publicId = Number(btn.getAttribute('data-public-id') || '0');
        if (!link) return;
        try {
          const copied = await copyTextRobust(link);
          if (!copied) {
            throw new Error('clipboard_unavailable');
          }
          setTemporaryTitle(btn, 'Copiado', 1500);
          status('Link copiado para a área de transferência.');
          logShare('copy_existing', link, avaliacaoId, publicId, true);
        } catch (e) {
          status('Não foi possível copiar o link.');
          logShare('copy_existing', link, avaliacaoId, publicId, false);
        }
      });
    });
    document.querySelectorAll('.btn-share-action').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const link = btn.getAttribute('data-url') || '';
        const avaliacaoId = Number(btn.getAttribute('data-avaliacao-id') || '0');
        const publicId = Number(btn.getAttribute('data-public-id') || '0');
        if (!link) {
          if (feedbackEl) feedbackEl.textContent = 'Nenhum link disponível para cópia.';
          status('Nenhum link disponível para cópia.');
          return;
        }
        try {
          const copied = await copyTextRobust(link);
          if (!copied) {
            throw new Error('clipboard_unavailable');
          }
          setTemporaryTitle(btn, 'Copiado', 1800);
          if (feedbackEl) feedbackEl.textContent = 'Link copiado para a área de transferência.';
          status('Link copiado para a área de transferência.');
          logShare('copy', link, avaliacaoId, publicId, true);
        } catch (e) {
          if (feedbackEl) feedbackEl.textContent = 'Falha na cópia automática. Selecione e copie manualmente o campo do link.';
          status('Falha na cópia automática.');
          logShare('copy', link, avaliacaoId, publicId, false);
        }
      });
    });
    document.querySelectorAll('.share-link-anchor').forEach((anchor) => {
      anchor.addEventListener('click', () => {
        const channel = anchor.getAttribute('data-channel') || '';
        const url = anchor.getAttribute('data-url') || '';
        const avaliacaoId = Number(anchor.getAttribute('data-avaliacao-id') || '0');
        const publicId = Number(anchor.getAttribute('data-public-id') || '0');
        if (feedbackEl) feedbackEl.textContent = 'Opção de compartilhamento aberta com sucesso.';
        logShare(channel, url, avaliacaoId, publicId, true);
      });
    });

    const deleteModal = document.getElementById('avaliacaoDeleteModal');
    const deleteMsg = document.getElementById('avaliacaoDeleteMessage');
    const deleteCancel = deleteModal ? deleteModal.querySelector('[data-delete-cancel]') : null;
    const deleteClose = deleteModal ? deleteModal.querySelector('[data-delete-close]') : null;
    const deleteConfirm = deleteModal ? deleteModal.querySelector('[data-delete-confirm]') : null;
    const deleteConfirmText = deleteModal ? deleteModal.querySelector('[data-delete-confirm-text]') : null;
    const deleteConfirmIcon = deleteModal ? deleteModal.querySelector('[data-delete-confirm-icon]') : null;
    const deleteSpinner = deleteModal ? deleteModal.querySelector('[data-delete-spinner]') : null;
    let pendingDelete = null;

    function openDeleteModal(ctx) {
      pendingDelete = ctx;
      if (deleteMsg) {
        const empresa = ctx.empresa || '—';
        const data = ctx.data || '';
        deleteMsg.textContent = 'Tem certeza que deseja excluir a avaliação de ' + empresa + (data ? (' (' + data + ')') : '') + '? Esta ação não pode ser desfeita.';
      }
      if (deleteModal) {
        deleteModal.classList.remove('hidden');
      }
      if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
      }
    }

    function closeDeleteModal() {
      if (!deleteModal) return;
      if (deleteConfirm) deleteConfirm.disabled = false;
      if (deleteCancel) deleteCancel.disabled = false;
      if (deleteClose) deleteClose.disabled = false;
      if (deleteConfirmText) deleteConfirmText.textContent = 'Excluir';
      if (deleteSpinner) deleteSpinner.classList.add('hidden');
      if (deleteConfirmIcon) deleteConfirmIcon.classList.remove('hidden');
      deleteModal.classList.add('hidden');
      pendingDelete = null;
    }

    function getRowDataFromButton(btn) {
      const row = btn.closest('tr');
      const empresa = btn.getAttribute('data-avaliacao-nome') || row?.getAttribute('data-empresa') || '';
      const dataCell = row ? row.querySelectorAll('td')[2] : null;
      const data = dataCell ? (dataCell.textContent || '').trim() : '';
      return { row, empresa, data };
    }

    document.querySelectorAll('.btn-delete-avaliacao').forEach((btn) => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const id = Number(btn.getAttribute('data-avaliacao-id') || '0');
        if (!id) return;
        const info = getRowDataFromButton(btn);
        openDeleteModal({ id, empresa: info.empresa, data: info.data, row: info.row, trigger: btn });
      });
    });

    async function confirmDelete() {
      if (!pendingDelete || !deleteConfirm) return;
      const id = Number(pendingDelete.id || 0);
      if (!id) return;

      const trigger = pendingDelete.trigger || null;
      if (trigger) {
        trigger.disabled = true;
        trigger.classList.add('opacity-60', 'cursor-not-allowed');
      }
      if (deleteConfirm) {
        deleteConfirm.disabled = true;
        deleteConfirm.classList.add('opacity-75', 'cursor-not-allowed');
        deleteConfirm.setAttribute('aria-label', 'Excluindo');
      }
      if (deleteCancel) deleteCancel.disabled = true;
      if (deleteClose) deleteClose.disabled = true;
      if (deleteSpinner) deleteSpinner.classList.remove('hidden');
      if (deleteConfirmIcon) deleteConfirmIcon.classList.add('hidden');

      const payload = new URLSearchParams();
      payload.set('csrf', csrfDeleteToken);
      payload.set('id', String(id));

      try {
        const response = await fetch('index.php?route=avaliacoes/delete-ajax', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString(),
          credentials: 'same-origin',
        });
        let data = null;
        try { data = await response.json(); } catch (e) { data = null; }
        if (!response.ok || !data || !data.ok) {
          const message = (data && data.message) ? data.message : 'Não foi possível excluir a avaliação. Tente novamente.';
          toast('error', message);
          status(message);
          return;
        }

        const row = pendingDelete.row || null;
        const wasSelected = row && row.classList.contains('bg-red-50');
        if (row && row.parentNode) {
          row.parentNode.removeChild(row);
        }
        toast('success', 'Avaliação excluída com sucesso.');
        status('Avaliação excluída com sucesso.');
        closeDeleteModal();

        if (wasSelected) {
          const remaining = document.querySelectorAll('.row-avaliacao');
          if (remaining.length > 0) {
            remaining.forEach((item) => item.classList.remove('bg-red-50'));
            remaining[0].classList.add('bg-red-50');
          }
        }
      } catch (e) {
        const message = 'Erro de conexão ao excluir a avaliação.';
        toast('error', message);
        status(message);
      } finally {
        if (deleteSpinner) deleteSpinner.classList.add('hidden');
        if (deleteConfirmIcon) deleteConfirmIcon.classList.remove('hidden');
        if (deleteConfirm) {
          deleteConfirm.disabled = false;
          deleteConfirm.classList.remove('opacity-75', 'cursor-not-allowed');
          deleteConfirm.setAttribute('aria-label', 'Excluir');
        }
        if (deleteCancel) deleteCancel.disabled = false;
        if (deleteClose) deleteClose.disabled = false;
        if (trigger) {
          trigger.disabled = false;
          trigger.classList.remove('opacity-60', 'cursor-not-allowed');
        }
      }
    }

    if (deleteConfirm) deleteConfirm.addEventListener('click', confirmDelete);
    if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);
    if (deleteClose) deleteClose.addEventListener('click', closeDeleteModal);
    if (deleteModal) {
      deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) closeDeleteModal();
      });
    }
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && deleteModal && !deleteModal.classList.contains('hidden')) {
        closeDeleteModal();
      }
    });
  })();
</script>
