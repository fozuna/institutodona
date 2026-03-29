<?php use App\Core\Security; /** @var array $item */ /** @var array $clientes */ /** @var array $setores */ /** @var array $errors */ ?>
<?php $questoesServer = $item['questoes'] ?? []; ?>
<div class="p-6 max-w-6xl">
    <div class="sticky top-2 z-20 bg-white border rounded shadow-sm px-4 py-3 mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Editar Auditoria</h1>
        <button type="submit" form="auditoriaEditForm" id="btnSalvarTopo" name="save_mode" value="full" class="px-4 py-2 rounded bg-brand-red text-white">Salvar Alterações</button>
    </div>
    <form method="post" action="index.php?route=auditorias/update" id="auditoriaEditForm" class="bg-white shadow rounded p-4 space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
        <input type="hidden" name="questoes_json" id="questoesJson" value="<?= htmlspecialchars(json_encode($questoesServer, JSON_UNESCAPED_UNICODE)) ?>" />
        <input type="hidden" name="prev_updated_at" value="<?= htmlspecialchars((string)($item['updated_at'] ?? '')) ?>" />
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4">
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
                <p id="clienteDebugStatus" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div class="md:col-span-4">
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
                <label class="block text-sm">Data de Realização</label>
                <input name="data_auditoria" id="dataAuditoria" class="border rounded p-2 w-full" value="<?= htmlspecialchars(date('d/m/Y', strtotime((string)$item['data_auditoria']))) ?>" placeholder="DD/MM/YYYY" required />
                <?php if (!empty($errors['data_auditoria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['data_auditoria']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-12">
                <label class="block text-sm">Nome da Auditoria</label>
                <input type="text" name="nome_auditoria" id="nomeAuditoria" class="border rounded p-2 w-full" minlength="5" maxlength="180" required value="<?= htmlspecialchars($item['nome_auditoria'] ?? '') ?>" />
                <?php if (!empty($errors['nome_auditoria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['nome_auditoria']) ?></p><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($errors['questoes'])): ?><p class="text-sm text-red-600"><?= htmlspecialchars($errors['questoes']) ?></p><?php endif; ?>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Questões da Auditoria</h2>
            <button type="button" id="btnAddQuestao" class="px-3 py-2 rounded bg-brand-pink text-white">Adicionar Questão</button>
        </div>
        <div id="questoesContainer" class="space-y-4"></div>
        <div class="flex items-center gap-2 justify-end">
            <a href="index.php?route=auditorias/index" id="btnCancelar" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</a>
            <button type="submit" id="btnSalvarParcial" name="save_mode" value="partial" class="px-4 py-2 rounded bg-yellow-500 text-white">Salvar Parcial</button>
            <button type="submit" id="btnSalvar" name="save_mode" value="full" class="px-4 py-2 rounded bg-brand-red text-white">Salvar Alterações</button>
        </div>
    </form>
</div>
<script>
    (function(){
        const clienteSelect = document.getElementById('clienteSelect');
        const setorSelect = document.getElementById('setorSelect');
        const dataAuditoria = document.getElementById('dataAuditoria');
        const questoesContainer = document.getElementById('questoesContainer');
        const questoesJson = document.getElementById('questoesJson');
        const form = document.getElementById('auditoriaEditForm');
        const btnSalvar = document.getElementById('btnSalvar');
        const btnSalvarTopo = document.getElementById('btnSalvarTopo');
        const btnCancelar = document.getElementById('btnCancelar');
        let dirty = false;
        const clienteDebugStatus = document.getElementById('clienteDebugStatus');
        const backendErrors = <?= json_encode($errors, JSON_UNESCAPED_UNICODE) ?>;
        let questoes = [];
        const responsavelSugestoesPorQuestao = new Map();
        const responsavelDebounce = new Map();

        const parseInitialQuestoes = ()=>{
            try {
                const parsed = JSON.parse(questoesJson.value || '[]');
                if (Array.isArray(parsed)) {
                    questoes = parsed;
                }
            } catch (e) {
                questoes = [];
            }
            if (!questoes.length) {
                questoes = [{ responsavel_nome: '', pergunta: '', referencia_esperada: '', processos: [] }];
            }
        };

        const syncHidden = ()=>{ questoesJson.value = JSON.stringify(questoes); dirty = true; };

        const openProcessEditor = (index)=>{
            const atual = Array.isArray(questoes[index].processos) ? questoes[index].processos : [];
            const raw = window.prompt('Informe os processos separados por vírgula:', atual.join(', '));
            if (raw === null) return;
            questoes[index].processos = Array.from(new Set(raw.split(',').map((v)=>v.trim()).filter(Boolean)));
            renderQuestoes();
        };

        const renderQuestoes = ()=>{
            questoesContainer.innerHTML = '';
            questoes.forEach((q, index)=>{
                const card = document.createElement('div');
                card.className = 'border rounded p-4 bg-gray-50';
                const processos = Array.isArray(q.processos) ? q.processos : [];
                const erroResponsavel = backendErrors[`questao_${index + 1}_responsavel_nome`] || '';
                card.innerHTML = `
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold">Questão ${index + 1}</h3>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-2 py-1 rounded bg-blue-600 text-white text-xs" data-processo="${index}">Inserir Processo</button>
                            <button type="button" class="px-2 py-1 rounded bg-gray-300 text-brand-brown text-xs" data-remove="${index}">Remover</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs">Responsável</label>
                            <input type="text" class="border rounded p-2 w-full" data-responsavel="${index}" list="responsavelList_${index}" autocomplete="off" value="${(q.responsavel_nome || '').replace(/"/g, '&quot;')}" />
                            <datalist id="responsavelList_${index}"></datalist>
                            <div class="text-xs text-gray-500 mt-1" data-responsavel-status="${index}"></div>
                            ${erroResponsavel ? `<div class="text-xs text-red-600 mt-1">${erroResponsavel}</div>` : ''}
                        </div>
                        <div class="md:col-span-8">
                            <label class="block text-xs">Pergunta de Auditoria</label>
                            <textarea class="border rounded p-2 w-full" rows="3" data-pergunta="${index}">${q.pergunta || ''}</textarea>
                            <div class="text-xs text-gray-500">${(q.pergunta || '').length}/1000</div>
                        </div>
                        <div class="md:col-span-12">
                            <label class="block text-xs">Referência Esperada</label>
                            <textarea class="border rounded p-2 w-full" rows="3" data-referencia="${index}">${q.referencia_esperada || ''}</textarea>
                        </div>
                        <div class="md:col-span-12 text-xs text-gray-600">
                            <strong>Processos vinculados:</strong> ${processos.length ? processos.join(', ') : 'nenhum'}
                        </div>
                    </div>`;
                questoesContainer.appendChild(card);
            });
            questoesContainer.querySelectorAll('[data-remove]').forEach((el)=>el.addEventListener('click', ()=>{
                if (questoes.length === 1) return;
                questoes.splice(Number(el.getAttribute('data-remove')), 1);
                renderQuestoes();
            }));
            questoesContainer.querySelectorAll('[data-processo]').forEach((el)=>el.addEventListener('click', ()=>openProcessEditor(Number(el.getAttribute('data-processo')))));
            questoesContainer.querySelectorAll('[data-responsavel]').forEach((el)=>el.addEventListener('input', ()=>{
                const idx = Number(el.getAttribute('data-responsavel'));
                questoes[idx].responsavel_nome = el.value;
                syncHidden();
                const key = `q_${idx}`;
                if (responsavelDebounce.has(key)) {
                    clearTimeout(responsavelDebounce.get(key));
                }
                responsavelDebounce.set(key, setTimeout(()=>{
                    fetchSugestoesResponsavel(idx, el.value || '');
                }, 250));
            }));
            questoesContainer.querySelectorAll('[data-responsavel]').forEach((el)=>el.addEventListener('blur', ()=>{
                const idx = Number(el.getAttribute('data-responsavel'));
                if ((el.value || '').trim().length >= 2) {
                    fetchSugestoesResponsavel(idx, el.value || '');
                }
            }));
            questoesContainer.querySelectorAll('[data-pergunta]').forEach((el)=>el.addEventListener('input', ()=>{ questoes[Number(el.getAttribute('data-pergunta'))].pergunta = el.value; syncHidden(); renderQuestoes(); }));
            questoesContainer.querySelectorAll('[data-referencia]').forEach((el)=>el.addEventListener('input', ()=>{ questoes[Number(el.getAttribute('data-referencia'))].referencia_esperada = el.value; syncHidden(); }));
            syncHidden();
        };

        const setResponsavelStatus = (idx, message, isError=false)=>{
            const el = questoesContainer.querySelector(`[data-responsavel-status="${idx}"]`);
            if (!el) return;
            el.textContent = message;
            el.className = `text-xs mt-1 ${isError ? 'text-red-600' : 'text-gray-500'}`;
        };

        const applySugestoesResponsavel = (idx, items)=>{
            const list = document.getElementById(`responsavelList_${idx}`);
            if (!list) return;
            list.innerHTML = '';
            const nomes = [];
            items.forEach((item)=>{
                const nome = (item && item.nome) ? String(item.nome).trim() : '';
                if (!nome) return;
                nomes.push(nome);
                const op = document.createElement('option');
                op.value = nome;
                list.appendChild(op);
            });
            responsavelSugestoesPorQuestao.set(idx, nomes);
            setResponsavelStatus(idx, nomes.length ? `${nomes.length} sugestão(ões) encontrada(s)` : 'Nenhum colaborador encontrado');
        };

        const fetchSugestoesResponsavel = (idx, query)=>{
            const clienteId = clienteSelect.value;
            const setorId = setorSelect.value;
            const q = (query || '').trim();
            if (!clienteId || !setorId) {
                setResponsavelStatus(idx, 'Selecione empresa e setor para validar responsável.');
                return;
            }
            if (q.length < 2) {
                setResponsavelStatus(idx, 'Digite ao menos 2 caracteres para sugerir colaboradores.');
                return;
            }
            fetch('index.php?route=auditorias/api_colaboradores&cliente_id=' + encodeURIComponent(clienteId) + '&setor_id=' + encodeURIComponent(setorId) + '&q=' + encodeURIComponent(q))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    applySugestoesResponsavel(idx, items);
                })
                .catch((err)=>{
                    console.error('[auditorias/edit] falha ao carregar sugestões de responsável', err);
                    setResponsavelStatus(idx, 'Erro ao consultar colaboradores.', true);
                });
        };

        const applyDateMask = ()=>{
            let v = (dataAuditoria.value || '').replace(/\D/g, '').slice(0, 8);
            if (v.length >= 5) v = v.slice(0,2) + '/' + v.slice(2,4) + '/' + v.slice(4);
            else if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
            dataAuditoria.value = v;
        };

        const loadSetores = ()=>{
            const clienteId = clienteSelect.value;
            setorSelect.innerHTML = '<option value="">Carregando...</option>';
            if (!clienteId) {
                setorSelect.innerHTML = '<option value="">Selecione</option>';
                return;
            }
            fetch('index.php?route=auditorias/api_setores&cliente_id=' + encodeURIComponent(clienteId))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = Array.isArray(json.items) ? json.items : [];
                    setorSelect.innerHTML = '<option value="">Selecione</option>';
                    items.forEach((s)=>{
                        const op = document.createElement('option');
                        op.value = s.id;
                        op.textContent = s.nome;
                        setorSelect.appendChild(op);
                    });
                })
                .catch(()=>{ setorSelect.innerHTML = '<option value="">Erro ao carregar</option>'; });
        };

        const applyClientes = (items)=>{
            const selected = clienteSelect.value;
            clienteSelect.innerHTML = '<option value="">Selecione</option>';
            items.forEach((c)=>{
                const op = document.createElement('option');
                op.value = String(c.id);
                op.textContent = c.nome_empresa || ('Empresa #' + c.id);
                if (selected && selected === String(c.id)) {
                    op.selected = true;
                }
                clienteSelect.appendChild(op);
            });
            if (clienteDebugStatus) {
                clienteDebugStatus.textContent = `Empresas carregadas: ${items.length}`;
            }
        };

        const loadClientesIfEmpty = ()=>{
            const totalOptions = clienteSelect.querySelectorAll('option').length;
            if (totalOptions > 1) {
                if (clienteDebugStatus) {
                    clienteDebugStatus.textContent = `Empresas carregadas: ${totalOptions - 1}`;
                }
                return Promise.resolve();
            }
            console.warn('[auditorias/edit] dropdown de empresas vazio na renderização inicial; acionando fallback API');
            if (clienteDebugStatus) {
                clienteDebugStatus.textContent = 'Carregando empresas...';
            }
            return fetch('index.php?route=auditorias/api_clientes')
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    applyClientes(items);
                    if (!items.length && clienteDebugStatus) {
                        clienteDebugStatus.textContent = 'Nenhuma empresa encontrada para o usuário.';
                    }
                })
                .catch((err)=>{
                    console.error('[auditorias/edit] falha ao carregar empresas via API', err);
                    if (clienteDebugStatus) {
                        clienteDebugStatus.textContent = 'Erro ao carregar empresas.';
                    }
                });
        };

        document.getElementById('btnAddQuestao')?.addEventListener('click', ()=>{
            questoes.push({ responsavel_nome: '', pergunta: '', referencia_esperada: '', processos: [] });
            renderQuestoes();
        });
        clienteSelect?.addEventListener('change', ()=>{
            loadSetores();
            responsavelSugestoesPorQuestao.clear();
            renderQuestoes();
        });
        setorSelect?.addEventListener('change', ()=>{
            responsavelSugestoesPorQuestao.clear();
            renderQuestoes();
        });
        dataAuditoria?.addEventListener('input', ()=>{ applyDateMask(); dirty = true; });
        window.addEventListener('beforeunload', (e)=>{ if (dirty) { e.preventDefault(); e.returnValue = ''; } });
        document.getElementById('nomeAuditoria')?.addEventListener('input', ()=>{ dirty = true; });
        btnCancelar?.addEventListener('click', (e)=>{ if (dirty && !confirm('Descartar alterações não salvas?')) { e.preventDefault(); } });
        form?.addEventListener('submit', (e)=>{
            const dateStr = (dataAuditoria.value || '').trim();
            const nome = (document.getElementById('nomeAuditoria').value || '').trim();
            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) {
                e.preventDefault();
                alert('Informe uma data válida no formato DD/MM/YYYY.');
                return;
            }
            if (nome.length < 5 || nome.length > 180) {
                e.preventDefault();
                alert('O nome da auditoria deve ter entre 5 e 180 caracteres.');
                return;
            }
            // Em modo parcial, permitimos salvar mesmo sem questões
            for (let i = 0; i < questoes.length; i++) {
                const q = questoes[i];
                if (!q.pergunta || q.pergunta.trim().length < 10) { e.preventDefault(); alert(`Questão ${i + 1}: pergunta deve ter no mínimo 10 caracteres.`); return; }
                if (!q.referencia_esperada || q.referencia_esperada.trim().length < 3) { e.preventDefault(); alert(`Questão ${i + 1}: referência esperada obrigatória.`); return; }
            }
            syncHidden();
            btnSalvar.disabled = true;
            btnSalvarTopo.disabled = true;
            btnSalvar.textContent = 'Salvando...';
            btnSalvarTopo.textContent = 'Salvando...';
            dirty = false;
        });

        parseInitialQuestoes();
        renderQuestoes();
        loadClientesIfEmpty();
    })();
</script>
