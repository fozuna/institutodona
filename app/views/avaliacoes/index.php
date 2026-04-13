<?php /** @var array $items */ /** @var array $clientes */ ?>
<?php
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $baseUrl = rtrim(dirname($scriptName), '/\\');
  if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $generatedLink = $_SESSION['generated_public_link'] ?? null;
  unset($_SESSION['generated_public_link']);
  $generatedUrl = (string)($generatedLink['url'] ?? '');
  $generatedEmpresa = (string)($generatedLink['empresa'] ?? '');
  $generatedAvaliacaoId = (int)($generatedLink['avaliacao_id'] ?? 0);
  $generatedExpiracao = (string)($generatedLink['expiracao'] ?? '');
  $generatedPermanent = !empty($generatedLink['permanent']);
  $defaultSelectedAvaliacaoId = !empty($items) ? (int)($items[0]['id'] ?? 0) : 0;
  $csrfShareToken = \App\Core\Security::csrfToken();
  $shareText = $generatedEmpresa !== '' ? ('Olá! Segue o link da avaliação da empresa ' . $generatedEmpresa . ': ' . $generatedUrl) : ('Olá! Segue o link da avaliação: ' . $generatedUrl);
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
  <div class="flex flex-col gap-3 mb-4 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold">Avaliações</h1>
      <p class="text-sm text-gray-600">Crie um link público permanente a qualquer momento, mesmo sem avaliações prévias cadastradas.</p>
    </div>
    <div class="flex items-center gap-3">
      <form method="post" action="index.php?route=avaliacoes/gerar-link-cliente" id="form-gerar-link-publico" class="flex items-center gap-3">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="avaliacao_id" id="avaliacao-publica-id" value="<?= $defaultSelectedAvaliacaoId > 0 ? $defaultSelectedAvaliacaoId : '' ?>" />
        <button type="submit" id="btn-gerar-link-publico" class="px-3 py-2 rounded bg-brand-red text-white">Criar Link de Avaliação Pública</button>
      </form>
      <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=avaliacoes/create">Nova Avaliação</a>
    </div>
  </div>
  <?php if ($generatedUrl !== ''): ?>
    <div id="generated-link-panel" class="mb-4 rounded border border-green-200 bg-green-50 p-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
          <div class="text-sm font-semibold text-green-800">Link público gerado e pronto para compartilhamento</div>
          <div id="generated-link-feedback" class="text-sm text-green-700 mt-1">Copiando automaticamente para a área de transferência...</div>
          <div class="mt-2 text-xs text-gray-600">
            <?php if ($generatedEmpresa !== ''): ?>Empresa: <?= htmlspecialchars($generatedEmpresa) ?> · <?php endif; ?>
            <?= $generatedPermanent ? 'Link permanente sem expiração automática' : ('Expira em ' . ($generatedExpiracao !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($generatedExpiracao))) : '—')) ?>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <input id="generated-public-link" class="border rounded p-2 w-full bg-white text-sm" value="<?= htmlspecialchars($generatedUrl) ?>" readonly />
          </div>
        </div>
        <div class="flex flex-wrap gap-2 xl:justify-end">
          <button type="button" class="px-3 py-2 rounded bg-brand-red text-white btn-share-action" data-channel="copy" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">Copiar link</button>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown share-link-anchor" href="<?= htmlspecialchars($mailtoHref) ?>" data-channel="email" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">Enviar por e-mail</a>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown share-link-anchor" href="<?= htmlspecialchars($whatsHref) ?>" target="_blank" rel="noopener" data-channel="whatsapp" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">WhatsApp</a>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown share-link-anchor" href="<?= htmlspecialchars($linkedinHref) ?>" target="_blank" rel="noopener" data-channel="linkedin" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">LinkedIn</a>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown share-link-anchor" href="<?= htmlspecialchars($facebookHref) ?>" target="_blank" rel="noopener" data-channel="facebook" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">Facebook</a>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown share-link-anchor" href="<?= htmlspecialchars($generatedUrl) ?>" target="_blank" rel="noopener" data-channel="open" data-url="<?= htmlspecialchars($generatedUrl) ?>" data-avaliacao-id="<?= $generatedAvaliacaoId ?>">Abrir link</a>
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
          <th class="p-3">Status Cliente</th>
          <th class="p-3">Data envio</th>
          <th class="p-3">Data conclusão</th>
          <th class="p-3">Financeiro</th>
          <th class="p-3">Mercado</th>
          <th class="p-3">Pessoas</th>
          <th class="p-3">Processo</th>
          <th class="p-3">Link Cliente</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i):
          $nm = $i['cliente_nome'] ?? ($i['empresa_nome'] ?? '—');
          $isPotencial = (int)($i['cliente_id'] ?? 0) <= 0;
          $status = $i['publico_status'] ?? '';
          $statusMeta = $statusMap[$status] ?? null;
          $publicToken = (string)($i['publico_token'] ?? '');
          $expirado = !empty($i['publico_expiracao']) && strtotime((string)$i['publico_expiracao']) < time();
          $permanente = empty($i['publico_expiracao']);
          $publicLink = $publicToken !== '' ? ($scheme . '://' . $host . $baseUrl . '/public/avaliacao/' . $publicToken) : '';
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
            <td class="p-3"><?= htmlspecialchars((string)($i['publico_nome'] ?? '—')) ?></td>
            <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($i['created_at']))) ?></td>
            <td class="p-3">
              <?php if ($statusMeta): ?>
                <span class="text-xs px-2 py-1 rounded <?= $statusMeta['class'] ?>"><?= $statusMeta['label'] ?></span>
              <?php else: ?>
                <span class="text-xs px-2 py-1 rounded text-gray-700 bg-gray-100">Sem link</span>
              <?php endif; ?>
            </td>
            <td class="p-3"><?= !empty($i['publico_data_envio']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($i['publico_data_envio']))) : '—' ?></td>
            <td class="p-3"><?= !empty($i['publico_data_conclusao']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($i['publico_data_conclusao']))) : '—' ?></td>
            <td class="p-3"><?= (int)$i['nota_financeiro'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_mercado'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_pessoas'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_processo'] ?>/7</td>
            <td class="p-3">
              <?php if ($publicToken !== '' && !$expirado): ?>
                <div class="flex items-center gap-2">
                  <a class="text-brand-pink text-sm" href="<?= htmlspecialchars($publicLink) ?>" target="_blank" rel="noopener">Abrir</a>
                  <button type="button" class="text-xs px-2 py-1 rounded bg-gray-200 text-brand-brown btn-copy-link" data-link="<?= htmlspecialchars($publicLink) ?>" data-avaliacao-id="<?= (int)$i['id'] ?>">Copiar</button>
                </div>
                <div class="text-xs text-gray-500 mt-1"><?= $permanente ? 'Permanente' : 'Expira automaticamente' ?></div>
              <?php elseif ($publicToken !== '' && $expirado): ?>
                <span class="text-xs text-gray-500">Expirado</span>
              <?php else: ?>
                <span class="text-xs text-gray-500">Nenhum link ativo</span>
              <?php endif; ?>
            </td>
            <td class="p-3">
              <a class="text-brand-pink icon-action" href="index.php?route=avaliacoes/show&id=<?= (int)$i['id'] ?>" title="Ver" aria-label="Ver"><span data-feather="bar-chart-2"></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  (function(){
    const csrfShareToken = <?= json_encode($csrfShareToken, JSON_UNESCAPED_UNICODE) ?>;
    async function logShare(channel, url, avaliacaoId, success) {
      if (!avaliacaoId || !channel) return;
      const payload = new URLSearchParams();
      payload.set('csrf', csrfShareToken);
      payload.set('avaliacao_id', String(avaliacaoId));
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
    if (generatedLinkInput && feedbackEl) {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(generatedLinkInput.value).then(() => {
          feedbackEl.textContent = 'Link copiado automaticamente com sucesso.';
          if (generatedCopyBtn) {
            generatedCopyBtn.textContent = 'Copiado';
          }
          logShare('auto_copy', generatedLinkInput.value, <?= (int)$generatedAvaliacaoId ?>, true);
        }).catch(() => {
          feedbackEl.textContent = 'Não foi possível copiar automaticamente. Use as opções abaixo para compartilhar.';
          logShare('auto_copy', generatedLinkInput.value, <?= (int)$generatedAvaliacaoId ?>, false);
        });
      } else {
        feedbackEl.textContent = 'Não foi possível copiar automaticamente. Use as opções abaixo para compartilhar.';
        logShare('auto_copy', generatedLinkInput.value, <?= (int)$generatedAvaliacaoId ?>, false);
      }
    }
    const form = document.getElementById('form-gerar-link-publico');
    const hiddenId = document.getElementById('avaliacao-publica-id');
    const generateBtn = document.getElementById('btn-gerar-link-publico');
    const rows = document.querySelectorAll('.row-avaliacao');
    function selectRow(row) {
      rows.forEach((item) => {
        item.classList.remove('bg-red-50');
      });
      row.classList.add('bg-red-50');
      hiddenId.value = row.getAttribute('data-avaliacao-id') || '';
    }
    rows.forEach((row) => {
      row.addEventListener('click', (event) => {
        if (event.target.closest('a,button,form')) return;
        selectRow(row);
      });
    });
    const preselectedRow = hiddenId ? Array.from(rows).find((row) => (row.getAttribute('data-avaliacao-id') || '') === hiddenId.value) : null;
    if (preselectedRow) {
      selectRow(preselectedRow);
    } else if (rows.length > 0) {
      selectRow(rows[0]);
    }
    if (form && generateBtn) {
      form.addEventListener('submit', () => {
        generateBtn.textContent = 'Criando link...';
      });
    }
    document.querySelectorAll('.btn-copy-link').forEach((btn)=>{
      btn.addEventListener('click', async () => {
        const link = btn.getAttribute('data-link') || '';
        const avaliacaoId = Number(btn.getAttribute('data-avaliacao-id') || '0');
        if (!link) return;
        try {
          if (!(navigator.clipboard && navigator.clipboard.writeText)) {
            throw new Error('clipboard_unavailable');
          }
          await navigator.clipboard.writeText(link);
          btn.textContent = 'Copiado';
          logShare('copy_existing', link, avaliacaoId, true);
          setTimeout(()=>{ btn.textContent = 'Copiar'; }, 1500);
        } catch (e) {
          logShare('copy_existing', link, avaliacaoId, false);
        }
      });
    });
    document.querySelectorAll('.btn-share-action').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const link = btn.getAttribute('data-url') || '';
        const avaliacaoId = Number(btn.getAttribute('data-avaliacao-id') || '0');
        if (!link) {
          if (feedbackEl) feedbackEl.textContent = 'Nenhum link disponível para cópia.';
          return;
        }
        try {
          if (!(navigator.clipboard && navigator.clipboard.writeText)) {
            throw new Error('clipboard_unavailable');
          }
          await navigator.clipboard.writeText(link);
          btn.textContent = 'Copiado';
          if (feedbackEl) feedbackEl.textContent = 'Link copiado para a área de transferência.';
          logShare('copy', link, avaliacaoId, true);
          setTimeout(() => { btn.textContent = 'Copiar link'; }, 1800);
        } catch (e) {
          if (feedbackEl) feedbackEl.textContent = 'Falha ao copiar automaticamente. Tente novamente.';
          logShare('copy', link, avaliacaoId, false);
        }
      });
    });
    document.querySelectorAll('.share-link-anchor').forEach((anchor) => {
      anchor.addEventListener('click', () => {
        const channel = anchor.getAttribute('data-channel') || '';
        const url = anchor.getAttribute('data-url') || '';
        const avaliacaoId = Number(anchor.getAttribute('data-avaliacao-id') || '0');
        if (feedbackEl) feedbackEl.textContent = 'Opção de compartilhamento aberta com sucesso.';
        logShare(channel, url, avaliacaoId, true);
      });
    });
  })();
</script>
