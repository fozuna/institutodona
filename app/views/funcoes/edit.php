<?php use App\Core\Security; /** @var array|null $item */ /** @var array $setores */ /** @var int $cliente */ /** @var array $mapDepartamentos */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Função') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=funcoes/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
            <div class="rounded border border-gray-200 bg-gray-50 p-4">
                <div class="text-sm font-semibold text-brand-brown">Contexto organizacional</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-sm">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Departamento vinculado</label>
                        <div class="border rounded bg-white p-2" data-departamento-display>—</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Setor vinculado</label>
                        <div class="border rounded bg-white p-2" data-setor-display>—</div>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">Setor</label>
                <select name="setor_id" class="border rounded p-2 w-full" required data-setor-select>
                    <?php foreach ($setores as $s): ?>
                        <option
                            value="<?= (int)$s['id'] ?>"
                            data-setor-nome="<?= htmlspecialchars($s['nome']) ?>"
                            data-departamento-nome="<?= htmlspecialchars(($s['departamento'] ?? ($mapDepartamentos[(int)($s['departamento_id'] ?? 0)] ?? ''))) ?>"
                            <?= ((int)$s['id'] === (int)$item['setor_id']) ? 'selected' : '' ?>
                        ><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
                <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
  (function () {
    const select = document.querySelector('[data-setor-select]');
    const setorDisplay = document.querySelector('[data-setor-display]');
    const depDisplay = document.querySelector('[data-departamento-display]');
    if (!select || !setorDisplay || !depDisplay) return;
    const apply = () => {
      const opt = select.options[select.selectedIndex];
      const setor = opt ? (opt.getAttribute('data-setor-nome') || opt.textContent || '') : '';
      const dep = opt ? (opt.getAttribute('data-departamento-nome') || '') : '';
      setorDisplay.textContent = (setor || '—').trim();
      depDisplay.textContent = (dep || '—').trim();
    };
    select.addEventListener('change', apply);
    apply();
  })();
</script>
