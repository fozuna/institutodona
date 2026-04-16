<?php
$values = $values ?? [];
$errors = $errors ?? [];
$record = $record ?? null;
$qs = $qs ?? [];
$selectedMap = $selectedMap ?? [];
$rateLimited = !empty($rateLimited);
$invalidToken = !empty($invalidToken);
$expiredToken = !empty($expiredToken);
$alreadyDone = !empty($alreadyDone);
$formAction = $formAction ?? '';
$formError = $formError ?? '';
$submitted = !empty($submitted);
$pdfUrl = $pdfUrl ?? '';
$isStaticPublic = !empty($isStaticPublic);
$contextEmpresa = $contextEmpresa ?? '';
$pillarLabels = ['eu' => 'EU', 'lideranca' => 'LIDERANÇA', 'processo' => 'PROCESSO', 'gestao' => 'GESTÃO'];
$identifier = $record['slug'] ?? $record['token'] ?? ($_GET['slug'] ?? $_GET['token'] ?? $_POST['slug'] ?? $_POST['token'] ?? '');
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = rtrim(dirname(dirname(dirname($scriptName))), '/\\');
if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
$assetsUrl = $baseUrl . '/assets';
$fieldValue = static function(string $key, $default = '') use ($values) {
  return htmlspecialchars((string)($values[$key] ?? $default));
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Avaliação Empresarial</title>
  <meta name="robots" content="noindex,nofollow,noarchive" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= htmlspecialchars($assetsUrl) ?>/css/theme.css" />
</head>
<body class="bg-brand-gray-50 text-brand-black">
  <div class="min-h-screen md:flex">
    <section class="w-full md:basis-[35%] min-h-screen bg-[#102040] text-white flex items-center">
      <div class="w-full p-10 md:p-10 lg:p-12">
        <img src="<?= htmlspecialchars($assetsUrl) ?>/img/logobco.png" alt="Logo da empresa" class="h-12 w-auto mb-8" />
        <div class="max-w-md">
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-white">Avaliação Empresarial</p>
          <h1 class="mt-4 text-3xl md:text-4xl font-bold leading-tight">Diagnóstico empresarial estruturado para sua empresa.</h1>
          <p class="mt-6 max-w-xl text-base leading-8 text-white/95">Preencha em duas etapas com total autonomia. Este formulário público é fixo, permanente e pode ser reutilizado sem autenticação.</p>
          <?php if ($contextEmpresa !== ''): ?>
            <p class="mt-4 max-w-xl text-sm leading-7 text-white/80">Contexto deste domínio: <?= htmlspecialchars($contextEmpresa) ?></p>
          <?php endif; ?>
          <div class="mt-10 grid grid-cols-1 gap-3 text-sm">
            <div class="rounded border border-white/25 bg-white/10 px-4 py-3">
              <div class="font-semibold">Etapa 1</div>
              <div class="text-white">Dados iniciais da empresa e do responsável.</div>
            </div>
            <div class="rounded border border-white/25 bg-white/10 px-4 py-3">
              <div class="font-semibold">Etapa 2</div>
              <div class="text-white">Questionário sobre a saúde da gestão da sua empresa.</div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="w-full md:basis-[65%] min-h-screen bg-[#eeeeee] flex items-center justify-center">
      <div class="w-full p-10">
        <div class="w-full max-w-5xl mx-auto bg-white shadow rounded border p-10">
          <?php if ($rateLimited): ?>
            <h2 class="text-2xl font-bold mb-3 text-brand-brown">Muitas tentativas</h2>
            <p class="text-base leading-7 text-gray-700">Aguarde alguns minutos e tente novamente.</p>
          <?php elseif ($invalidToken || $expiredToken): ?>
            <h2 class="text-2xl font-bold mb-3 text-brand-brown">Link inválido</h2>
            <p class="text-base leading-7 text-gray-700">Este link público não foi encontrado. Verifique o endereço informado.</p>
          <?php elseif ($alreadyDone): ?>
            <h2 class="text-2xl font-bold mb-3 text-brand-brown">Avaliação já finalizada</h2>
            <p class="text-base leading-7 text-gray-700 mb-4">Esta avaliação já foi concluída. Obrigado pela participação.</p>
            <?php if (!empty($record['data_conclusao'])): ?>
              <p class="text-sm text-gray-600">Concluída em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($record['data_conclusao']))) ?></p>
            <?php endif; ?>
          <?php elseif ((int)($step ?? 1) === 1): ?>
            <div class="mb-8">
              <p class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-brown">Etapa 1 de 2</p>
              <h2 class="mt-2 text-2xl font-bold text-brand-brown">Dados iniciais</h2>
              <p class="mt-2 text-base leading-7 text-gray-600">Preencha as informações abaixo para iniciar a avaliação.</p>
            </div>
            <?php if ($submitted): ?>
              <div class="mb-6 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                Avaliação enviada com sucesso. Este link continua ativo e pode ser utilizado novamente.
                <?php if ($pdfUrl !== ''): ?>
                  <a class="ml-2 inline-flex items-center rounded bg-brand-red px-3 py-1 text-white" href="<?= htmlspecialchars($pdfUrl) ?>">Baixar PDF</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ($formError !== ''): ?>
              <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($formError) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($formAction) ?>" class="space-y-6" autocomplete="off">
              <input type="hidden" name="action" value="start" />
              <?php if (!$isStaticPublic): ?>
                <input type="hidden" name="identifier" value="<?= htmlspecialchars((string)$identifier) ?>" />
              <?php endif; ?>
              <input type="text" name="public_fake_user" value="" autocomplete="username" tabindex="-1" class="hidden" aria-hidden="true" />
              <input type="password" name="public_fake_pass" value="" autocomplete="new-password" tabindex="-1" class="hidden" aria-hidden="true" />
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-medium mb-2">Nome</label>
                  <input name="public_nome" autocomplete="off" autocapitalize="words" spellcheck="false" class="border rounded p-3 w-full" value="<?= $fieldValue('nome') ?>" required />
                  <?php if (!empty($errors['nome'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['nome']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">Empresa</label>
                  <input name="public_empresa" autocomplete="off" autocapitalize="words" spellcheck="false" class="border rounded p-3 w-full" value="<?= $fieldValue('empresa') ?>" required />
                  <?php if (!empty($errors['empresa'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['empresa']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">WhatsApp</label>
                  <input name="public_whatsapp" autocomplete="off" inputmode="numeric" pattern="\d{10,15}" class="border rounded p-3 w-full" value="<?= $fieldValue('whatsapp') ?>" required />
                  <?php if (!empty($errors['whatsapp'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['whatsapp']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">E-mail</label>
                  <input type="email" name="public_email" autocomplete="off" autocapitalize="none" spellcheck="false" class="border rounded p-3 w-full" value="<?= $fieldValue('email') ?>" required />
                  <?php if (!empty($errors['email'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">Nº funcionários</label>
                  <input type="number" min="1" step="1" name="public_numero_funcionarios" autocomplete="off" class="border rounded p-3 w-full" value="<?= $fieldValue('numero_funcionarios') ?>" required />
                  <?php if (!empty($errors['numero_funcionarios'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['numero_funcionarios']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">Nº líderes</label>
                  <input type="number" min="1" step="1" name="public_numero_lideres" autocomplete="off" class="border rounded p-3 w-full" value="<?= $fieldValue('numero_lideres') ?>" required />
                  <?php if (!empty($errors['numero_lideres'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['numero_lideres']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">Faturamento anual</label>
                  <input type="number" min="1" step="1" name="public_faturamento_anual" autocomplete="off" class="border rounded p-3 w-full" value="<?= $fieldValue('faturamento_anual') ?>" required />
                  <?php if (!empty($errors['faturamento_anual'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['faturamento_anual']) ?></p><?php endif; ?>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-2">É tomador de decisão?</label>
                  <select name="public_tomador_decisao" class="border rounded p-3 w-full" required>
                    <option value="">Selecione</option>
                    <option value="1" <?= ($values['tomador_decisao'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option>
                    <option value="0" <?= ($values['tomador_decisao'] ?? '') === '0' ? 'selected' : '' ?>>Não</option>
                  </select>
                  <?php if (!empty($errors['tomador_decisao'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['tomador_decisao']) ?></p><?php endif; ?>
                </div>
              </div>
              <div class="flex flex-wrap gap-3 pt-2">
                <button class="px-5 py-3 rounded bg-orange-600 text-white font-medium hover:bg-orange-700 public-action-button" type="submit" data-public-action="continue" autocomplete="off">Continuar</button>
                <button class="px-5 py-3 rounded bg-gray-200 text-gray-800 font-medium hover:bg-gray-300" type="reset">Limpar formulário</button>
                <a class="px-5 py-3 rounded border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" href="<?= htmlspecialchars($publicUrl ?? $formAction) ?>">Cancelar</a>
              </div>
            </form>
          <?php else: ?>
            <div class="mb-8">
              <p class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-brown">Etapa 2 de 2</p>
              <h2 class="mt-2 text-2xl font-bold text-brand-brown">Questionário da avaliação</h2>
              <p class="mt-2 text-base leading-7 text-gray-600">Marque os itens aplicáveis e finalize sua avaliação.</p>
            </div>
            <?php if ($formError !== ''): ?>
              <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($formError) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($formAction) ?>" class="space-y-6" autocomplete="off">
              <input type="hidden" name="action" value="finish" />
              <?php if (!$isStaticPublic): ?>
                <input type="hidden" name="identifier" value="<?= htmlspecialchars((string)$identifier) ?>" />
              <?php endif; ?>
              <input type="hidden" name="public_nome" value="<?= $fieldValue('nome') ?>" />
              <input type="hidden" name="public_empresa" value="<?= $fieldValue('empresa') ?>" />
              <input type="hidden" name="public_whatsapp" value="<?= $fieldValue('whatsapp') ?>" />
              <input type="hidden" name="public_email" value="<?= $fieldValue('email') ?>" />
              <input type="hidden" name="public_numero_funcionarios" value="<?= $fieldValue('numero_funcionarios') ?>" />
              <input type="hidden" name="public_numero_lideres" value="<?= $fieldValue('numero_lideres') ?>" />
              <input type="hidden" name="public_faturamento_anual" value="<?= $fieldValue('faturamento_anual') ?>" />
              <input type="hidden" name="public_tomador_decisao" value="<?= $fieldValue('tomador_decisao') ?>" />
              <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-4 gap-6">
                <?php foreach ($qs as $pillar => $questions): ?>
                  <div class="bg-white border rounded">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                      <div class="font-semibold uppercase text-brand-brown"><?= htmlspecialchars($pillarLabels[$pillar] ?? strtoupper($pillar)) ?></div>
                      <span class="text-brand-brown">✔</span>
                    </div>
                    <div class="p-0">
                      <table class="min-w-full text-sm">
                        <tbody>
                        <?php foreach ($questions as $idx => $q): ?>
                          <tr class="border-b">
                            <td class="p-3 w-10 align-top"><input type="checkbox" name="<?= $pillar ?>[]" value="<?= $idx+1 ?>" data-pillar="<?= $pillar ?>" <?= in_array($idx + 1, $selectedMap[$pillar] ?? [], true) ? 'checked' : '' ?> /></td>
                            <td class="p-3 leading-6"><?= htmlspecialchars($q) ?></td>
                          </tr>
                        <?php endforeach; ?>
                        <tr>
                          <td class="p-3 font-semibold" colspan="2">Nota:</td>
                        </tr>
                        <tr>
                          <td class="p-3" colspan="2"><input type="number" name="nota_<?= $pillar ?>" id="nota_<?= $pillar ?>" min="0" max="7" class="border rounded p-3 w-full select-none" readonly tabindex="-1" /></td>
                        </tr>
                        <tr>
                          <td class="p-3 font-semibold" colspan="2">Mundo ideal (100%)</td>
                        </tr>
                        <tr>
                          <td class="p-3" colspan="2"><input type="number" value="7" class="border rounded p-3 w-full bg-gray-100 select-none" readonly tabindex="-1" /></td>
                        </tr>
                        <tr>
                          <td class="p-3 font-semibold" colspan="2">Qual sua realidade? (%)</td>
                        </tr>
                        <tr>
                          <td class="p-3" colspan="2"><input type="number" name="realidade_<?= $pillar ?>" id="realidade_<?= $pillar ?>" min="0" max="100" class="border rounded p-3 w-full select-none" readonly tabindex="-1" /></td>
                        </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="flex flex-wrap gap-3 pt-2">
                <button class="px-5 py-3 rounded bg-orange-600 text-white font-medium hover:bg-orange-700 public-action-button" type="submit" data-public-action="finish" autocomplete="off">Enviar avaliação</button>
                <button class="px-5 py-3 rounded bg-gray-200 text-gray-800 font-medium hover:bg-gray-300" type="reset">Limpar seleção</button>
                <a class="px-5 py-3 rounded border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" href="<?= htmlspecialchars($publicUrl ?? $formAction) ?>">Cancelar</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
  <script>
    (function(){
      function resetPublicActionButtons() {
        document.querySelectorAll('.public-action-button').forEach((button) => {
          button.disabled = false;
          button.removeAttribute('aria-disabled');
          button.removeAttribute('data-busy');
          button.classList.remove('opacity-60', 'cursor-not-allowed');
        });
      }

      function bindPublicActionButtons() {
        document.querySelectorAll('form').forEach((form) => {
          if (form.dataset.publicBound === '1') return;
          form.dataset.publicBound = '1';
          form.addEventListener('submit', () => {
            const submitButton = form.querySelector('.public-action-button');
            if (!submitButton) return;
            submitButton.disabled = false;
            submitButton.setAttribute('data-busy', '1');
            setTimeout(() => {
              resetPublicActionButtons();
            }, 4000);
          });
        });
      }

      resetPublicActionButtons();
      bindPublicActionButtons();
      window.addEventListener('pageshow', resetPublicActionButtons);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) resetPublicActionButtons();
      });

      const whatsappEl = document.querySelector('input[name="public_whatsapp"]');
      if (whatsappEl) {
        whatsappEl.addEventListener('input', () => {
          whatsappEl.value = (whatsappEl.value || '').replace(/\D+/g, '');
        });
      }
      const totals = <?= json_encode(array_map('count', $qs), JSON_UNESCAPED_UNICODE) ?>;
      function recalc(p) {
        const checks = document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]');
        let count = 0;
        checks.forEach(c=>{ if (c.checked) count++; });
        const nota = document.getElementById('nota_'+p);
        const real = document.getElementById('realidade_'+p);
        if (nota) nota.value = count;
        if (real) real.value = Math.round((count / (totals[p]||1)) * 100);
      }
      Object.keys(totals).forEach((p)=>{
        document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]').forEach((el)=>{
          el.addEventListener('change', ()=>recalc(p));
        });
        recalc(p);
      });
    })();
  </script>
</body>
</html>
