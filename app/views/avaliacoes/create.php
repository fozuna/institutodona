<?php /** @var int $cliente */ /** @var array $clientes */ /** @var array $values */ /** @var array $errors */ ?>
<div class="p-6">
  <?php
    $values = $values ?? [];
    $errors = $errors ?? [];
    $fieldValue = static function(string $key, $default = '') use ($values) {
      return htmlspecialchars((string)($values[$key] ?? $default));
    };
    $selectedCliente = (int)($values['cliente_id'] ?? $cliente ?? 0);
    $modoCadastro = (string)($values['modo_cadastro'] ?? ($selectedCliente > 0 ? 'existente' : 'potencial'));
    $clienteTravado = $selectedCliente > 0 && $cliente > 0;
  ?>
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Nova Avaliação</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <form method="post" action="index.php?route=avaliacoes/store" class="space-y-6">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="md:col-span-2 xl:col-span-4">
        <label class="block text-sm mb-2">Tipo de cadastro</label>
        <?php if ($clienteTravado): ?>
          <input type="hidden" name="modo_cadastro" value="existente" />
          <div class="rounded border border-brand-red bg-red-50 px-4 py-3 text-sm text-brand-brown">Esta avaliação será vinculada ao cliente já selecionado.</div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="rounded border p-3 cursor-pointer">
              <input type="radio" name="modo_cadastro" value="potencial" class="mr-2" <?= $modoCadastro !== 'existente' ? 'checked' : '' ?> />
              <span class="font-medium">Potencial cliente / cadastro manual</span>
              <span class="block text-xs text-gray-600 mt-1">Permite salvar a avaliação sem empresa já cadastrada no sistema.</span>
            </label>
            <label class="rounded border p-3 cursor-pointer">
              <input type="radio" name="modo_cadastro" value="existente" class="mr-2" <?= $modoCadastro === 'existente' ? 'checked' : '' ?> />
              <span class="font-medium">Cliente existente</span>
              <span class="block text-xs text-gray-600 mt-1">Vincula a avaliação diretamente a um cadastro já existente.</span>
            </label>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Empresa</label>
        <?php if ($clienteTravado): ?>
          <input type="hidden" name="cliente_id" value="<?= (int)$selectedCliente ?>" />
          <input name="empresa_nome" class="border rounded p-2 w-full bg-gray-100" value="<?= $fieldValue('empresa_nome', (function() use ($clientes, $selectedCliente){ foreach ($clientes as $cl){ if ((int)$cl['id'] === (int)$selectedCliente){ return (string)$cl['nome_empresa']; } } return ''; })()) ?>" readonly required />
        <?php else: ?>
          <input name="empresa_nome" id="empresa_nome" class="border rounded p-2 w-full <?= $modoCadastro === 'existente' ? 'bg-gray-100' : '' ?>" value="<?= $fieldValue('empresa_nome') ?>" required <?= $modoCadastro === 'existente' ? 'readonly' : '' ?> />
        <?php endif; ?>
        <?php if (!empty($errors['empresa_nome'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['empresa_nome']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Nome</label>
        <input name="nome" class="border rounded p-2 w-full" value="<?= $fieldValue('nome') ?>" required />
        <?php if (!empty($errors['nome'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['nome']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">WhatsApp</label>
        <input name="whatsapp" inputmode="numeric" pattern="\d{10,13}" class="border rounded p-2 w-full" value="<?= $fieldValue('whatsapp') ?>" required />
        <?php if (!empty($errors['whatsapp'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['whatsapp']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">E-mail</label>
        <input type="email" name="email" class="border rounded p-2 w-full" value="<?= $fieldValue('email') ?>" required />
        <?php if (!empty($errors['email'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Número de funcionários</label>
        <input type="number" min="1" step="1" name="numero_funcionarios" class="border rounded p-2 w-full" value="<?= $fieldValue('numero_funcionarios') ?>" required />
        <?php if (!empty($errors['numero_funcionarios'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['numero_funcionarios']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Número de líderes</label>
        <input type="number" min="1" step="1" name="numero_lideres" class="border rounded p-2 w-full" value="<?= $fieldValue('numero_lideres') ?>" required />
        <?php if (!empty($errors['numero_lideres'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['numero_lideres']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Faturamento médio anual (R$)</label>
        <input type="number" min="1" step="1" name="faturamento_medio_anual" class="border rounded p-2 w-full" value="<?= $fieldValue('faturamento_medio_anual') ?>" required />
        <?php if (!empty($errors['faturamento_medio_anual'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['faturamento_medio_anual']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">É tomador de decisão?</label>
        <select name="tomador_decisao" class="border rounded p-2 w-full" required>
          <option value="">Selecione</option>
          <option value="1" <?= ($values['tomador_decisao'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option>
          <option value="0" <?= ($values['tomador_decisao'] ?? '') === '0' ? 'selected' : '' ?>>Não</option>
        </select>
        <?php if (!empty($errors['tomador_decisao'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['tomador_decisao']) ?></p><?php endif; ?>
      </div>
      <div class="md:col-span-2 xl:col-span-4">
        <label class="block text-sm">Cliente existente</label>
        <select name="cliente_id" id="cliente_id" class="border rounded p-2 w-full" <?= $clienteTravado ? 'disabled' : '' ?>>
          <option value="">—</option>
          <?php foreach ($clientes as $cl): ?>
            <option value="<?= (int)$cl['id'] ?>" data-empresa="<?= htmlspecialchars((string)$cl['nome_empresa']) ?>" <?= $selectedCliente === (int)$cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-600 mt-1">Se preferir, deixe sem vínculo e siga como potencial cliente.</p>
        <?php if (!empty($errors['cliente_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['cliente_id']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php
      $qs = \App\Core\AvaliacaoQuestionario::pilares();
      $selectedFinanceiro = array_map('intval', $_POST['financeiro'] ?? []);
      $selectedMercado = array_map('intval', $_POST['mercado'] ?? []);
      $selectedPessoas = array_map('intval', $_POST['pessoas'] ?? []);
      $selectedProcesso = array_map('intval', $_POST['processo'] ?? []);
      $selectedMap = [
        'financeiro' => $selectedFinanceiro,
        'mercado' => $selectedMercado,
        'pessoas' => $selectedPessoas,
        'processo' => $selectedProcesso,
      ];
    ?>
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <?php foreach ($qs as $pillar => $questions): ?>
        <div class="bg-white shadow rounded">
          <div class="px-4 py-3 border-b flex items-center justify-between">
            <div class="font-semibold uppercase"><?= $pillar ?></div>
            <span class="text-brand-brown">✔</span>
          </div>
          <div class="p-0">
            <table class="min-w-full text-sm">
              <tbody>
              <?php foreach ($questions as $idx => $q): ?>
                <tr class="border-b">
                  <td class="p-2 w-8"><input type="checkbox" name="<?= $pillar ?>[]" value="<?= $idx+1 ?>" data-pillar="<?= $pillar ?>" <?= in_array($idx + 1, $selectedMap[$pillar] ?? [], true) ? 'checked' : '' ?> /></td>
                  <td class="p-2"><?= htmlspecialchars($q) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr>
                <td class="p-2 font-semibold" colspan="2">Nota:</td>
              </tr>
              <tr>
                <td class="p-2" colspan="2"><input type="number" name="nota_<?= $pillar ?>" id="nota_<?= $pillar ?>" min="0" max="7" class="border rounded p-2 w-full select-none" readonly tabindex="-1" /></td>
              </tr>
              <tr>
                <td class="p-2 font-semibold" colspan="2">Mundo ideal (100%)</td>
              </tr>
              <tr>
                <td class="p-2" colspan="2"><input type="number" value="7" class="border rounded p-2 w-full bg-gray-100 select-none" readonly tabindex="-1" /></td>
              </tr>
              <tr>
                <td class="p-2 font-semibold" colspan="2">Qual sua realidade? (%)</td>
              </tr>
              <tr>
                <td class="p-2" colspan="2"><input type="number" name="realidade_<?= $pillar ?>" id="realidade_<?= $pillar ?>" min="0" max="100" class="border rounded p-2 w-full select-none" readonly tabindex="-1" /></td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
      <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
    </div>
    <script>
      (function(){
        const totals = {
          financeiro: 7,
          mercado: 7,
          pessoas: 7,
          processo: 7
        };
        const whatsappEl = document.querySelector('input[name="whatsapp"]');
        const modoEls = document.querySelectorAll('input[name="modo_cadastro"]');
        const clienteEl = document.getElementById('cliente_id');
        const empresaEl = document.getElementById('empresa_nome');
        if (whatsappEl) {
          whatsappEl.addEventListener('input', () => {
            whatsappEl.value = (whatsappEl.value || '').replace(/\D+/g, '');
          });
        }
        function toggleModoCadastro() {
          if (!modoEls.length || !clienteEl || !empresaEl) return;
          const modo = Array.from(modoEls).find((el) => el.checked)?.value || 'potencial';
          const isExistente = modo === 'existente';
          clienteEl.required = isExistente;
          clienteEl.disabled = false;
          empresaEl.readOnly = isExistente;
          empresaEl.classList.toggle('bg-gray-100', isExistente);
          if (isExistente) {
            const selected = clienteEl.options[clienteEl.selectedIndex];
            empresaEl.value = selected ? (selected.getAttribute('data-empresa') || '') : '';
          } else {
            clienteEl.value = '';
            if (empresaEl.value === '') {
              empresaEl.readOnly = false;
            }
          }
        }
        if (modoEls.length) {
          modoEls.forEach((el) => el.addEventListener('change', toggleModoCadastro));
        }
        if (clienteEl && empresaEl) {
          clienteEl.addEventListener('change', () => {
            const selected = clienteEl.options[clienteEl.selectedIndex];
            if (selected && selected.value) {
              empresaEl.value = selected.getAttribute('data-empresa') || '';
            }
          });
        }
        toggleModoCadastro();
        function recalc(p) {
          const checks = document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]');
          let count = 0;
          checks.forEach(c=>{ if (c.checked) count++; });
          const nota = document.getElementById('nota_'+p);
          const real = document.getElementById('realidade_'+p);
          if (nota) nota.value = count;
          if (real) real.value = Math.round((count / (totals[p]||1)) * 100);
        }
        ['financeiro','mercado','pessoas','processo'].forEach(p=>{
          document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]').forEach(el=>{
            el.addEventListener('change', ()=>recalc(p));
          });
          recalc(p);
        });
      })();
    </script>
  </form>
</div>
