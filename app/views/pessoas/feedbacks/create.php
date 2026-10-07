<?php use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var ?array $colaborador */ /** @var array $clientes */ /** @var int $selectedEmpresa */ /** @var ?array $feedbackOld */
$csrf = \App\Core\Security::csrfToken();
$tipo = $feedbackOld['tipo'] ?? 'positivo';
$valores = $feedbackOld ?? [];
$pessoasSubnavAtivo = 'feedbacks';
$voltarErro = 'index.php?route=pessoas/feedbackCreateForm'
    . ($colaborador ? '&colaborador_id=' . (int)$colaborador['id'] : ($selectedEmpresa > 0 ? '&empresa_id=' . $selectedEmpresa : ''));
?>
<div class="p-4 md:p-6 space-y-5 max-w-3xl mx-auto">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black">Novo Feedback</h1>
      <p class="text-sm text-gray-600">Registro histórico de gestão, no modelo SBI (Situação, Comportamento, Impacto).</p>
    </div>
    <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/feedbacks">Voltar</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <form method="post" action="index.php?route=pessoas/feedbackCreate" class="bg-white shadow rounded-xl p-4 md:p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= $csrf ?>" />
    <input type="hidden" name="voltar_para_erro" value="<?= htmlspecialchars($voltarErro) ?>" />

    <?php if ($colaborador): ?>
      <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
      <div class="rounded-lg bg-gray-50 p-3 text-sm">
        Colaborador: <strong><?= htmlspecialchars($colaborador['nome']) ?></strong>
        · <a class="text-brand-pink" href="index.php?route=pessoas/feedbackCreateForm">trocar colaborador</a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="fbEmpresa">Empresa <span class="text-red-600">*</span></label>
          <select id="fbEmpresa" class="border border-gray-300 rounded-lg p-2 w-full" <?= count($clientes) <= 1 ? 'disabled' : '' ?>>
            <option value="">Selecione</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $selectedEmpresa ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="relative">
          <label class="block text-sm font-medium text-gray-700 mb-1" for="fbColabBusca">Colaborador <span class="text-red-600">*</span></label>
          <input id="fbColabBusca" type="text" class="border border-gray-300 rounded-lg p-2 w-full" placeholder="Buscar por nome" autocomplete="off" <?= $selectedEmpresa > 0 ? '' : 'disabled' ?> />
          <div id="fbColabMenu" class="hidden absolute z-10 w-full mt-1 bg-white border rounded shadow max-h-56 overflow-auto"></div>
        </div>
      </div>
      <input type="hidden" name="colaborador_id" id="fbColaboradorId" value="" />
      <p id="fbColabSelecionado" class="text-sm text-gray-600"></p>
    <?php endif; ?>

    <?php require __DIR__ . '/_form_campos.php'; ?>

    <div class="flex gap-2">
      <button type="submit" id="fbSubmit" class="px-4 py-3 rounded-lg bg-brand-red text-white font-semibold" <?= $colaborador ? '' : 'disabled' ?>>Salvar Feedback</button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/feedbacks">Cancelar</a>
    </div>
  </form>
</div>

<?php if (!$colaborador): ?>
<script>
  (function () {
    var empresaSel = document.getElementById('fbEmpresa');
    var busca = document.getElementById('fbColabBusca');
    var menu = document.getElementById('fbColabMenu');
    var hiddenId = document.getElementById('fbColaboradorId');
    var selecionado = document.getElementById('fbColabSelecionado');
    var submitBtn = document.getElementById('fbSubmit');
    var debounceTimer = null;

    function limparSelecao() {
      hiddenId.value = '';
      selecionado.textContent = '';
      submitBtn.disabled = true;
    }
    function escolher(item) {
      hiddenId.value = item.id;
      selecionado.innerHTML = 'Selecionado: <strong>' + item.nome.replace(/</g, '&lt;') + '</strong> · <a href="#" id="fbTrocar" class="text-brand-pink">trocar</a>';
      submitBtn.disabled = false;
      menu.classList.add('hidden');
      busca.value = item.nome;
      var trocar = document.getElementById('fbTrocar');
      if (trocar) {
        trocar.addEventListener('click', function (ev) { ev.preventDefault(); limparSelecao(); busca.value = ''; busca.focus(); });
      }
    }
    function buscar(q) {
      var empresaId = empresaSel.value;
      if (!empresaId) { return; }
      fetch('index.php?route=colaboradores/search&cliente=' + encodeURIComponent(empresaId) + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (items) {
          items = Array.isArray(items) ? items : [];
          if (!items.length) {
            menu.innerHTML = '<div class="p-2 text-sm text-gray-500">Nenhum colaborador encontrado.</div>';
          } else {
            menu.innerHTML = '';
            items.slice(0, 30).forEach(function (item) {
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'block w-full text-left px-3 py-2 text-sm hover:bg-gray-100';
              btn.textContent = item.nome;
              btn.addEventListener('click', function () { escolher(item); });
              menu.appendChild(btn);
            });
          }
          menu.classList.remove('hidden');
        })
        .catch(function () { /* silencioso: busca é um auxílio, não bloqueia o formulário */ });
    }

    empresaSel.addEventListener('change', function () {
      limparSelecao();
      busca.value = '';
      busca.disabled = !empresaSel.value;
      menu.classList.add('hidden');
    });
    busca.addEventListener('input', function () {
      limparSelecao();
      if (debounceTimer) { clearTimeout(debounceTimer); }
      debounceTimer = setTimeout(function () { buscar(busca.value || ''); }, 250);
    });
    busca.addEventListener('focus', function () { if (menu.innerHTML) { menu.classList.remove('hidden'); } });
    document.addEventListener('click', function (ev) {
      if (!menu.contains(ev.target) && ev.target !== busca) { menu.classList.add('hidden'); }
    });

    if (empresaSel.value) { busca.disabled = false; }
  })();
</script>
<?php endif; ?>
