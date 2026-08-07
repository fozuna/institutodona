<?php /** @var array|null $item */ ?>
<?php use App\Core\Security; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Cliente') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=clientes/update" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <div>
                <label class="block text-sm">Nome da Empresa</label>
                <input name="nome_empresa" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome_empresa']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">CNPJ</label>
                <input name="CNPJ" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['CNPJ']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">Contato</label>
                <input name="contato" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['contato']) ?>" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Tipo da Unidade</label>
                    <?php $isMatriz = (int)($item['is_matriz'] ?? 1) === 1; ?>
                    <select name="tipo_unidade" class="border rounded p-2 w-full">
                        <option value="matriz" <?= $isMatriz ? 'selected' : '' ?>>Matriz</option>
                        <option value="filial" <?= !$isMatriz ? 'selected' : '' ?>>Filial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm">Matriz (quando Filial)</label>
                    <select name="matriz_id" class="border rounded p-2 w-full">
                        <option value="">—</option>
                        <?php foreach ((new \App\Models\ClienteModel())->matrizes() as $m): ?>
                            <option value="<?= (int)$m['id'] ?>" <?= ((int)($item['matriz_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nome_empresa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm">Logo (png, jpg, webp, svg)</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" />
                <?php if (!empty($item['logo_path'])): ?>
                  <div class="mt-2">
                    <img src="<?= htmlspecialchars($item['logo_path']) ?>" alt="Logo atual" class="h-10 w-auto" />
                  </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm">Status do cliente</label>
                <?php $curAtivo = isset($item['ativo']) ? (int)$item['ativo'] : 1; ?>
                <select name="ativo" id="clienteAtivo" class="border rounded p-2 w-48" data-initial="<?= $curAtivo ?>">
                    <option value="1" <?= $curAtivo === 1 ? 'selected' : '' ?>>Ativo</option>
                    <option value="0" <?= $curAtivo !== 1 ? 'selected' : '' ?>>Inativo</option>
                </select>
                <div class="text-xs text-gray-500 mt-1">A inativação exige justificativa e pode ser bloqueada por tarefas, reuniões ou auditorias em aberto vinculadas ao cliente. Cliente inativo deixa de aparecer nos formulários de novo lançamento.</div>
            </div>
            <div id="clienteStatusReasonWrap" class="hidden">
                <label class="block text-sm">Justificativa (obrigatória para ativar/inativar)</label>
                <textarea name="status_reason" id="clienteStatusReason" class="border rounded p-2 w-full" rows="3" placeholder="Descreva o motivo (mínimo 5 caracteres)."></textarea>
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
    const ativo = document.getElementById('clienteAtivo');
    const reasonWrap = document.getElementById('clienteStatusReasonWrap');
    const reason = document.getElementById('clienteStatusReason');
    if (!ativo || !reasonWrap || !reason) return;

    const toggleReason = () => {
      const initial = String(ativo.getAttribute('data-initial') || '1');
      const current = String(ativo.value || '1');
      const show = current !== initial;
      reasonWrap.classList.toggle('hidden', !show);
      reason.required = show;
      if (!show) reason.value = '';
    };
    ativo.addEventListener('change', toggleReason);
    toggleReason();
  })();
</script>
