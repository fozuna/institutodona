<?php use App\Core\Security; /** @var array $clientes */ /** @var array $setores */ /** @var int $selectedCliente */ /** @var int $selectedSetor */ /** @var array $values */ /** @var array $errors */ ?>
<?php
$questoesServer = [];
if (!empty($values['questoes']) && is_array($values['questoes'])) {
    $questoesServer = $values['questoes'];
}
?>
<div class="p-6 max-w-6xl">
    <div class="sticky top-2 z-20 bg-white border rounded shadow-sm px-4 py-3 mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Cadastrar Nova Auditoria</h1>
        <button type="submit" form="auditoriaForm" id="btnSalvarTopo" class="px-4 py-2 rounded bg-brand-red text-white">Cadastrar Auditoria</button>
    </div>
    <form method="post" action="index.php?route=auditorias/store" id="auditoriaForm" class="bg-white shadow rounded p-4 space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="questoes_json" id="questoesJson" value="<?= htmlspecialchars(json_encode($questoesServer, JSON_UNESCAPED_UNICODE)) ?>" />
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4">
                <label class="block text-sm">Seleção de Empresa</label>
                <select name="cliente_id" id="clienteSelect" class="border rounded p-2 w-full" required>
                    <option value="">Selecione</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)($values['cliente_id'] ?? $selectedCliente ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
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
                        <option value="<?= (int)$s['id'] ?>" <?= ((int)($values['setor_id'] ?? $selectedSetor ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['setor_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['setor_id']) ?></p><?php endif; ?>
                <p id="setorStatus" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm">Data de Realização</label>
                <input name="data_auditoria" id="dataAuditoria" class="border rounded p-2 w-full" value="<?= htmlspecialchars(isset($values['data_auditoria']) && $values['data_auditoria'] ? date('d/m/Y', strtotime($values['data_auditoria'])) : '') ?>" placeholder="DD/MM/YYYY" required />
                <?php if (!empty($errors['data_auditoria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['data_auditoria']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-12">
                <label class="block text-sm">Nome da Auditoria</label>
                <input type="text" name="nome_auditoria" id="nomeAuditoria" class="border rounded p-2 w-full" minlength="5" maxlength="180" required value="<?= htmlspecialchars($values['nome_auditoria'] ?? '') ?>" />
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
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</a>
            <button type="submit" id="btnSalvar" class="px-4 py-2 rounded bg-brand-red text-white">Cadastrar Auditoria</button>
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
        const form = document.getElementById('auditoriaForm');
        const btnSalvar = document.getElementById('btnSalvar');
        const btnSalvarTopo = document.getElementById('btnSalvarTopo');
        const clienteDebugStatus = document.getElementById('clienteDebugStatus');
        const setorStatus = document.getElementById('setorStatus');
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

        const syncHidden = ()=>{
            questoesJson.value = JSON.stringify(questoes);
        };

        const openProcessEditor = (index)=>{
            const atual = Array.isArray(questoes[index].processos) ? questoes[index].processos : [];
            const raw = window.prompt('Informe os processos separados por vírgula:', atual.join(', '));
            if (raw === null) return;
            const list = raw.split(',').map((v)=>v.trim()).filter(Boolean);
            questoes[index].processos = Array.from(new Set(list));
            renderQuestoes();
        };

        const renderQuestoes = ()=>{
            questoesContainer.innerHTML = '';
            questoes.forEach((q, index)=>{
                const card = document.createElement('div');
                card.className = 'border rounded p-4 bg-gray-50';
                const count = (q.pergunta || '').length;
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
                            <div class="relative">
                                <input type="text" class="border rounded p-2 w-full" data-responsavel="${index}" list="responsavelList_${index}" autocomplete="off" value="${(q.responsavel_nome || '').replace(/"/g, '&quot;')}" />
                                <div class="hidden absolute z-10 w-full mt-1 bg-white border rounded shadow max-h-52 overflow-auto" data-responsavel-menu="${index}"></div>
                            </div>
                            <datalist id="responsavelList_${index}"></datalist>
                            <div class="text-xs text-gray-500 mt-1" data-responsavel-status="${index}"></div>
                            ${erroResponsavel ? `<div class="text-xs text-red-600 mt-1">${erroResponsavel}</div>` : ''}
                        </div>
                        <div class="md:col-span-8">
                            <label class="block text-xs">Pergunta de Auditoria</label>
                            <textarea class="border rounded p-2 w-full" rows="3" data-pergunta="${index}">${q.pergunta || ''}</textarea>
                            <div class="text-xs text-gray-500">${count}/1000</div>
                        </div>
                        <div class="md:col-span-12">
                            <label class="block text-xs">Referência Esperada</label>
                            <textarea class="border rounded p-2 w-full" rows="3" data-referencia="${index}">${q.referencia_esperada || ''}</textarea>
                        </div>
                        <div class="md:col-span-12 text-xs text-gray-600">
                            <strong>Processos vinculados:</strong> ${processos.length ? processos.join(', ') : 'nenhum'}
                        </div>
                    </div>
                `;
                questoesContainer.appendChild(card);
            });
            questoesContainer.querySelectorAll('[data-remove]').forEach((el)=>{
                el.addEventListener('click', ()=>{
                    if (questoes.length === 1) return;
                    questoes.splice(Number(el.getAttribute('data-remove')), 1);
                    renderQuestoes();
                });
            });
            questoesContainer.querySelectorAll('[data-processo]').forEach((el)=>{
                el.addEventListener('click', ()=>openProcessEditor(Number(el.getAttribute('data-processo'))));
            });
            questoesContainer.querySelectorAll('[data-responsavel]').forEach((el)=>{
                el.addEventListener('input', ()=>{
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
                });
                el.addEventListener('focus', ()=>{
                    const idx = Number(el.getAttribute('data-responsavel'));
                    const menu = questoesContainer.querySelector(`[data-responsavel-menu="${idx}"]`);
                    const sugestoes = responsavelSugestoesPorQuestao.get(idx) || [];
                    if (menu && sugestoes.length) {
                        menu.classList.remove('hidden');
                    }
                });
                el.addEventListener('blur', ()=>{
                    const idx = Number(el.getAttribute('data-responsavel'));
                    if ((el.value || '').trim().length >= 2) {
                        fetchSugestoesResponsavel(idx, el.value || '');
                    }
                    setTimeout(()=>{
                        const menu = questoesContainer.querySelector(`[data-responsavel-menu="${idx}"]`);
                        if (menu) menu.classList.add('hidden');
                    }, 150);
                });
            });
            questoesContainer.querySelectorAll('[data-pergunta]').forEach((el)=>{
                el.addEventListener('input', ()=>{
                    questoes[Number(el.getAttribute('data-pergunta'))].pergunta = el.value;
                    syncHidden();
                    renderQuestoes();
                });
            });
            questoesContainer.querySelectorAll('[data-referencia]').forEach((el)=>{
                el.addEventListener('input', ()=>{
                    questoes[Number(el.getAttribute('data-referencia'))].referencia_esperada = el.value;
                    syncHidden();
                });
            });
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
            const menu = questoesContainer.querySelector(`[data-responsavel-menu="${idx}"]`);
            if (!list) return;
            list.innerHTML = '';
            if (menu) menu.innerHTML = '';
            const nomes = [];
            (items || []).slice(0, 10).forEach((item)=>{
                const nome = (item && item.nome) ? String(item.nome).trim() : '';
                if (!nome) return;
                nomes.push(nome);
                const op = document.createElement('option');
                op.value = nome;
                list.appendChild(op);
                if (menu) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'block w-full text-left px-3 py-2 hover:bg-gray-100';
                    btn.textContent = nome;
                    btn.addEventListener('mousedown', (e)=>{
                        e.preventDefault();
                        const input = questoesContainer.querySelector(`input[data-responsavel="${idx}"]`);
                        if (!input) return;
                        input.value = nome;
                        questoes[idx].responsavel_nome = nome;
                        syncHidden();
                        if (menu) menu.classList.add('hidden');
                        setResponsavelStatus(idx, 'Responsável selecionado.');
                    });
                    menu.appendChild(btn);
                }
            });
            responsavelSugestoesPorQuestao.set(idx, nomes);
            setResponsavelStatus(idx, nomes.length ? `${nomes.length} sugestão(ões) encontrada(s)` : 'Nenhum colaborador encontrado');
            if (menu) {
                if (nomes.length) menu.classList.remove('hidden');
                else menu.classList.add('hidden');
            }
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
                    console.error('[auditorias/create] falha ao carregar sugestões de responsável', err);
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
            setorSelect.disabled = true;
            setorSelect.innerHTML = '<option value="">Carregando...</option>';
            if (setorStatus) setorStatus.textContent = 'Carregando setores...';
            if (!clienteId) {
                setorSelect.innerHTML = '<option value="">Selecione</option>';
                setorSelect.disabled = false;
                if (setorStatus) setorStatus.textContent = '';
                return;
            }
            fetch('index.php?route=auditorias/api_setores&cliente_id=' + encodeURIComponent(clienteId))
                .then((r)=>r.json())
                .then((json)=>{
                    const ok = json && json.success !== false;
                    const items = ok && Array.isArray(json.items) ? json.items : [];
                    if (!items.length) {
                        setorSelect.innerHTML = '<option value="">Nenhum setor encontrado</option>';
                        setorSelect.disabled = true;
                        if (setorStatus) setorStatus.textContent = 'Nenhum setor encontrado para a empresa selecionada.';
                        return;
                    }
                    setorSelect.innerHTML = '<option value="">Selecione</option>';
                    items.forEach((s)=>{
                        const op = document.createElement('option');
                        op.value = s.id;
                        op.textContent = s.nome;
                        setorSelect.appendChild(op);
                    });
                    setorSelect.disabled = false;
                    if (setorStatus) setorStatus.textContent = '';
                })
                .catch((err)=>{
                    console.error('[auditorias/create] falha ao carregar setores', err);
                    setorSelect.innerHTML = '<option value="">Erro ao carregar setores</option>';
                    setorSelect.disabled = true;
                    if (setorStatus) setorStatus.textContent = 'Erro ao carregar setores.';
                });
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
            if (clienteDebugStatus) {
                clienteDebugStatus.textContent = 'Carregando empresas...';
            }
            console.warn('[auditorias/create] dropdown de empresas vazio na renderização inicial; acionando fallback API');
            return fetch('index.php?route=auditorias/api_clientes')
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    applyClientes(items);
                    if (!items.length) {
                        console.error('[auditorias/create] API retornou lista vazia de empresas');
                        if (clienteDebugStatus) {
                            clienteDebugStatus.textContent = 'Nenhuma empresa encontrada para o usuário.';
                        }
                    }
                })
                .catch((err)=>{
                    console.error('[auditorias/create] falha ao carregar empresas via API', err);
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
        clienteSelect?.addEventListener('input', ()=>{
            loadSetores();
            responsavelSugestoesPorQuestao.clear();
            renderQuestoes();
        });
        setorSelect?.addEventListener('change', ()=>{
            responsavelSugestoesPorQuestao.clear();
            renderQuestoes();
        });
        dataAuditoria?.addEventListener('input', applyDateMask);

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
            if (!questoes.length) {
                e.preventDefault();
                alert('Cadastre pelo menos uma questão.');
                return;
            }
            for (let i = 0; i < questoes.length; i++) {
                const q = questoes[i];
                if (!q.responsavel_nome || q.responsavel_nome.trim().length < 2) {
                    e.preventDefault();
                    alert(`Questão ${i + 1}: informe o responsável.`);
                    return;
                }
                const sugestoes = responsavelSugestoesPorQuestao.get(i) || [];
                if (sugestoes.length > 0) {
                    const okResponsavel = sugestoes.some((nome)=>nome.toLowerCase() === q.responsavel_nome.trim().toLowerCase());
                    if (!okResponsavel) {
                        e.preventDefault();
                        alert(`Questão ${i + 1}: selecione um responsável listado entre os colaboradores cadastrados.`);
                        return;
                    }
                }
                if (!q.pergunta || q.pergunta.trim().length < 10) {
                    e.preventDefault();
                    alert(`Questão ${i + 1}: pergunta deve ter no mínimo 10 caracteres.`);
                    return;
                }
                if (!q.referencia_esperada || q.referencia_esperada.trim().length < 3) {
                    e.preventDefault();
                    alert(`Questão ${i + 1}: referência esperada obrigatória.`);
                    return;
                }
            }
            syncHidden();
            btnSalvar.disabled = true;
            btnSalvarTopo.disabled = true;
            btnSalvar.textContent = 'Cadastrando...';
            btnSalvarTopo.textContent = 'Cadastrando...';
        });

        parseInitialQuestoes();
        renderQuestoes();
        document.addEventListener('click', (e)=>{
            const t = e.target;
            if (!(t instanceof HTMLElement)) return;
            if (t.closest('[data-responsavel-menu]') || t.closest('[data-responsavel]')) return;
            questoesContainer.querySelectorAll('[data-responsavel-menu]').forEach((m)=>m.classList.add('hidden'));
        });
        loadClientesIfEmpty().then(()=>{
            if (clienteSelect.value) {
                loadSetores();
            }
        });
    })();
</script>
