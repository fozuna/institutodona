<?php use App\Core\Security; /** @var array $item */ /** @var array $clientes */ /** @var array $responsaveis */ /** @var array $setores */ /** @var array $errors */ ?>
<div class="p-6 max-w-5xl">
    <h1 class="text-2xl font-bold mb-4">Editar Auditoria</h1>
    <form method="post" action="index.php?route=auditorias/update" id="auditoriaEditForm" class="bg-white shadow rounded p-4 space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-6">
                <label class="block text-sm">Seleção de Empresa</label>
                <select name="cliente_id" id="clienteSelect" class="border rounded p-2 w-full" required>
                    <option value="">Selecione</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$item['cliente_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome_empresa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['cliente_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['cliente_id']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-6">
                <label class="block text-sm">Setor</label>
                <select name="setor_id" id="setorSelect" class="border rounded p-2 w-full" required>
                    <option value="">Selecione</option>
                    <?php foreach ($setores as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= ((int)$item['setor_id'] === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['setor_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['setor_id']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm">Responsável</label>
                <select name="responsavel_id" id="responsavelSelect" class="border rounded p-2 w-full" required>
                    <option value="">Selecione</option>
                    <?php foreach ($responsaveis as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$item['responsavel_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['responsavel_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['responsavel_id']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm">Data</label>
                <input name="data_auditoria" id="dataAuditoria" class="border rounded p-2 w-full" value="<?= htmlspecialchars(date('d/m/Y', strtotime($item['data_auditoria']))) ?>" placeholder="DD/MM/YYYY" required />
                <?php if (!empty($errors['data_auditoria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['data_auditoria']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-12">
                <label class="block text-sm">Pergunta de Auditoria</label>
                <textarea name="pergunta" id="perguntaField" class="border rounded p-2 w-full" rows="3" minlength="10" maxlength="500" required><?= htmlspecialchars($item['pergunta'] ?? '') ?></textarea>
                <div class="text-xs text-gray-500"><span id="perguntaCount">0</span>/500</div>
                <?php if (!empty($errors['pergunta'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['pergunta']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-12">
                <label class="block text-sm">Objetivo da Pergunta</label>
                <textarea name="objetivo" id="objetivoField" class="border rounded p-2 w-full" rows="4" minlength="20" required><?= htmlspecialchars($item['objetivo'] ?? '') ?></textarea>
                <div class="text-xs text-gray-500"><span id="objetivoCount">0</span> caracteres</div>
                <?php if (!empty($errors['objetivo'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['objetivo']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-12">
                <label class="block text-sm">Referência Esperada</label>
                <textarea name="referencia_esperada" class="border rounded p-2 w-full" rows="4" required><?= htmlspecialchars($item['referencia_esperada'] ?? '') ?></textarea>
                <?php if (!empty($errors['referencia_esperada'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['referencia_esperada']) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</a>
            <button type="submit" id="btnSalvar" class="px-4 py-2 rounded bg-brand-red text-white">Salvar</button>
        </div>
    </form>
</div>
<script>
    (function(){
        const clienteSelect = document.getElementById('clienteSelect');
        const setorSelect = document.getElementById('setorSelect');
        const responsavelSelect = document.getElementById('responsavelSelect');
        const perguntaField = document.getElementById('perguntaField');
        const objetivoField = document.getElementById('objetivoField');
        const perguntaCount = document.getElementById('perguntaCount');
        const objetivoCount = document.getElementById('objetivoCount');
        const form = document.getElementById('auditoriaEditForm');
        const btnSalvar = document.getElementById('btnSalvar');
        const loadResponsaveis = ()=>{
            const setorId = setorSelect.value;
            const clienteId = clienteSelect.value;
            responsavelSelect.innerHTML = '<option value="">Carregando...</option>';
            if (!setorId) {
                responsavelSelect.innerHTML = '<option value="">Selecione</option>';
                return;
            }
            fetch('index.php?route=auditorias/api_responsaveis&setor_id=' + encodeURIComponent(setorId) + '&cliente_id=' + encodeURIComponent(clienteId || ''))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && json.items) ? json.items : [];
                    responsavelSelect.innerHTML = '<option value="">Selecione</option>';
                    items.forEach((s)=>{
                        const op = document.createElement('option');
                        op.value = s.id;
                        op.textContent = s.nome;
                        responsavelSelect.appendChild(op);
                    });
                })
                .catch(()=>{
                    responsavelSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                });
        };
        clienteSelect?.addEventListener('change', ()=>{
            const clienteId = clienteSelect.value;
            setorSelect.innerHTML = '<option value="">Carregando...</option>';
            responsavelSelect.innerHTML = '<option value="">Selecione o setor</option>';
            fetch('index.php?route=auditorias/api_setores&cliente_id=' + encodeURIComponent(clienteId))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && json.items) ? json.items : [];
                    setorSelect.innerHTML = '<option value="">Selecione</option>';
                    items.forEach((s)=>{
                        const op = document.createElement('option');
                        op.value = s.id;
                        op.textContent = s.nome;
                        setorSelect.appendChild(op);
                    });
                })
                .catch(()=>{
                    setorSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                });
        });
        setorSelect?.addEventListener('change', loadResponsaveis);
        const updateCounters = ()=>{
            perguntaCount.textContent = (perguntaField.value || '').length;
            objetivoCount.textContent = (objetivoField.value || '').length;
        };
        perguntaField?.addEventListener('input', updateCounters);
        objetivoField?.addEventListener('input', updateCounters);
        updateCounters();
        form?.addEventListener('submit', (e)=>{
            const d = (document.getElementById('dataAuditoria').value || '').trim();
            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(d)) {
                e.preventDefault();
                alert('Data inválida. Use DD/MM/YYYY.');
                return;
            }
            btnSalvar.disabled = true;
            btnSalvar.textContent = 'Salvando...';
        });
    })();
</script>
