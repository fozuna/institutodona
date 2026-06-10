<?php use App\Core\Security; /** @var array|null $item */ /** @var array $departamentos */ /** @var array $setores */ /** @var array $funcoes */ /** @var int $cliente */ ?>
<?php $backUrl = ((int)($cliente ?? 0) > 0) ? ('index.php?route=clientes/show&id=' . (int)$cliente) : 'index.php?route=clientes/index'; ?>
<?php
  $selectedDepartamentoId = (int)($selected_departamento_id ?? 0);
  $selectedSetorId = (int)($selected_setor_id ?? 0);
  $setorToDep = [];
  foreach (($setores ?? []) as $s) {
      $setorToDep[(int)($s['id'] ?? 0)] = (int)($s['departamento_id'] ?? 0);
  }
?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Colaborador') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=colaboradores/update" class="space-y-4" data-colaboradores-form>
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">E-mail</label>
                <input name="email" class="border rounded p-2 w-full" type="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>" />
            </div>
            <div>
                <label class="block text-sm">Departamento</label>
                <select id="colabDepartamentoId" class="border rounded p-2 w-full">
                    <option value="0">Todos</option>
                    <?php foreach (($departamentos ?? []) as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= ((int)$d['id'] === $selectedDepartamentoId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($d['nome'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Setor</label>
                <select id="colabSetorId" class="border rounded p-2 w-full">
                    <option value="0">Todos</option>
                    <?php foreach (($setores ?? []) as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"
                                data-departamento-id="<?= (int)($s['departamento_id'] ?? 0) ?>"
                                <?= ((int)$s['id'] === $selectedSetorId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($s['nome'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Função</label>
                <select name="funcao_id" id="colabFuncaoId" class="border rounded p-2 w-full" required>
                    <option value="0">Selecione</option>
                    <?php foreach (($funcoes ?? []) as $f): ?>
                        <?php $depId = (int)($setorToDep[(int)($f['setor_id'] ?? 0)] ?? 0); ?>
                        <option value="<?= (int)$f['id'] ?>"
                                data-setor-id="<?= (int)($f['setor_id'] ?? 0) ?>"
                                data-departamento-id="<?= $depId ?>"
                                <?= ((int)$f['id'] === (int)($item['funcao_id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($f['nome'] ?? '')) ?> (<?= htmlspecialchars((string)($f['setor'] ?? '')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Líder</label>
                <select name="lider" class="border rounded p-2 w-40">
                    <option value="não" <?= (($item['lider'] ?? 'não') === 'não') ? 'selected' : '' ?>>Não</option>
                    <option value="sim" <?= (($item['lider'] ?? 'não') === 'sim') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Status do cadastro</label>
                <?php $curAtivo = isset($item['ativo']) ? (int)$item['ativo'] : 1; ?>
                <select name="ativo" id="colabAtivo" class="border rounded p-2 w-48" data-initial="<?= $curAtivo ?>">
                    <option value="1" <?= $curAtivo === 1 ? 'selected' : '' ?>>Ativo</option>
                    <option value="0" <?= $curAtivo !== 1 ? 'selected' : '' ?>>Inativo</option>
                </select>
                <div class="text-xs text-gray-500 mt-1">A inativação exige justificativa e pode ser bloqueada por vínculos pendentes.</div>
            </div>
            <div id="colabStatusReasonWrap" class="hidden">
                <label class="block text-sm">Justificativa (obrigatória para ativar/inativar)</label>
                <textarea name="status_reason" id="colabStatusReason" class="border rounded p-2 w-full" rows="3" placeholder="Descreva o motivo (mínimo 5 caracteres)."></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
                <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
  (function () {
    const form = document.querySelector('[data-colaboradores-form]');
    if (!form) return;
    const dep = document.getElementById('colabDepartamentoId');
    const setor = document.getElementById('colabSetorId');
    const funcao = document.getElementById('colabFuncaoId');
    const ativo = document.getElementById('colabAtivo');
    const reasonWrap = document.getElementById('colabStatusReasonWrap');
    const reason = document.getElementById('colabStatusReason');
    if (!dep || !setor || !funcao) return;

    const normalize = (v) => {
      const n = parseInt(String(v || '0'), 10);
      return Number.isFinite(n) ? n : 0;
    };

    const filterBy = (select, attr, value) => {
      const val = normalize(value);
      Array.from(select.options).forEach((opt) => {
        const isPlaceholder = opt.value === '0' || opt.value === '';
        const optVal = normalize(opt.getAttribute(attr) || '0');
        const visible = isPlaceholder || val === 0 || optVal === val;
        opt.hidden = !visible;
        opt.disabled = !visible;
      });
      const current = normalize(select.value);
      if (current > 0) {
        const currentOpt = select.querySelector('option[value="' + current + '"]');
        if (currentOpt && (currentOpt.hidden || currentOpt.disabled)) {
          select.value = '0';
        }
      }
    };

    const filterFuncoes = () => {
      const depId = normalize(dep.value);
      const setorId = normalize(setor.value);
      Array.from(funcao.options).forEach((opt) => {
        const isPlaceholder = opt.value === '0' || opt.value === '';
        const optDep = normalize(opt.getAttribute('data-departamento-id') || '0');
        const optSet = normalize(opt.getAttribute('data-setor-id') || '0');
        const matchDep = depId === 0 || optDep === depId;
        const matchSet = setorId === 0 || optSet === setorId;
        const visible = isPlaceholder || (matchDep && matchSet);
        opt.hidden = !visible;
        opt.disabled = !visible;
      });
      const cur = normalize(funcao.value);
      if (cur > 0) {
        const currentOpt = funcao.querySelector('option[value="' + cur + '"]');
        if (currentOpt && (currentOpt.hidden || currentOpt.disabled)) {
          funcao.value = '0';
        }
      }
    };

    dep.addEventListener('change', () => {
      filterBy(setor, 'data-departamento-id', dep.value);
      setor.value = '0';
      filterFuncoes();
      funcao.value = '0';
    });

    setor.addEventListener('change', () => {
      filterFuncoes();
    });

    filterBy(setor, 'data-departamento-id', dep.value);
    filterFuncoes();

    const toggleReason = () => {
      if (!ativo || !reasonWrap || !reason) return;
      const initial = String(ativo.getAttribute('data-initial') || '1');
      const current = String(ativo.value || '1');
      const show = current !== initial;
      reasonWrap.classList.toggle('hidden', !show);
      reason.required = show;
      if (!show) reason.value = '';
    };
    if (ativo) ativo.addEventListener('change', toggleReason);
    toggleReason();
  })();
</script>
