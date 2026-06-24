<?php use App\Core\Security; /** @var array $item */ /** @var array $clientes */ /** @var array $setores */ /** @var array $errors */ /** @var array $respostas */ ?>
<?php $questoesServer = $item['questoes'] ?? []; ?>
<?php
    $respostasMap = [];
    foreach (($respostas ?? []) as $resp) {
        $respostasMap[(int)($resp['questao_id'] ?? 0)] = $resp;
    }
?>
<div class="p-6 max-w-6xl">
    <div class="sticky top-2 z-20 bg-white border rounded shadow-sm px-4 py-3 mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Editar Auditoria</h1>
        <button type="submit" form="auditoriaEditForm" id="btnSalvarTopo" name="save_mode" value="full" class="px-4 py-2 rounded bg-brand-red text-white">Salvar Alterações</button>
    </div>
    <?php if (($item['status'] ?? '') === 'Realizada'): ?>
        <div class="mb-4 rounded border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            Esta auditoria está finalizada. Alterações serão registradas no histórico.
        </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=auditorias/update" id="auditoriaEditForm" class="bg-white shadow rounded p-4 space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
        <input type="hidden" name="questoes_json" id="questoesJson" value="<?= htmlspecialchars(json_encode($questoesServer, JSON_UNESCAPED_UNICODE)) ?>" />
        <input type="hidden" name="avaliacoes_json" id="avaliacoesJson" value="<?= htmlspecialchars(json_encode(array_values($respostasMap), JSON_UNESCAPED_UNICODE)) ?>" />
        <input type="hidden" name="prev_updated_at" value="<?= htmlspecialchars((string)($item['updated_at'] ?? '')) ?>" />
        <input type="hidden" name="prev_lock_version" value="<?= (int)($item['lock_version'] ?? 0) ?>" />
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4">
                <label class="block text-sm">Seleção de Empresa</label>
                <select name="cliente_id" id="clienteSelect" class="border rounded p-2 w-full" required <?= (($item['status'] ?? '') === 'Realizada') ? 'disabled' : '' ?>>
                    <option value="">Selecione</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$item['cliente_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome_empresa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (($item['status'] ?? '') === 'Realizada'): ?>
                    <input type="hidden" name="cliente_id" value="<?= (int)$item['cliente_id'] ?>" />
                <?php endif; ?>
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
        <?php if (($item['status'] ?? '') === 'Realizada'): ?>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Conformidade, Observações e Anexos</h2>
                    <span class="text-sm text-gray-500">Alterações nesta área também serão registradas no histórico.</span>
                </div>
                <div id="avaliacoesContainer" class="space-y-4">
                    <?php foreach ($questoesServer as $idx => $questao): ?>
                        <?php
                            $qid = (int)($questao['id'] ?? 0);
                            $resp = $respostasMap[$qid] ?? ['conformidade' => 'pendente', 'observacoes' => ''];
                        ?>
                        <div class="border rounded p-4 bg-gray-50" data-avaliacao-card="<?= $qid ?>">
                            <div class="text-sm text-gray-500">Questão <?= $idx + 1 ?></div>
                            <div class="font-semibold mb-3"><?= htmlspecialchars((string)($questao['pergunta'] ?? '')) ?></div>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-3">
                                    <label class="block text-sm">Conformidade</label>
                                    <select class="border rounded p-2 w-full js-conformidade">
                                        <?php foreach (['pendente' => 'Pendente', 'conforme' => 'Conforme', 'nao_conforme' => 'Não conforme', 'nao_aplica' => 'Não se aplica'] as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= (($resp['conformidade'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="md:col-span-9">
                                    <label class="block text-sm">Observações</label>
                                    <textarea class="border rounded p-2 w-full js-observacoes" rows="3" maxlength="2000"><?= htmlspecialchars((string)($resp['observacoes'] ?? '')) ?></textarea>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-medium">Anexos</div>
                                    <label class="px-3 py-2 rounded bg-brand-red text-white cursor-pointer text-sm">
                                        <input type="file" class="hidden js-upload-anexo" accept=".pdf,.doc,.docx,image/*" />
                                        Adicionar anexo
                                    </label>
                                </div>
                                <div class="text-xs text-gray-500 mb-2">Permitidos: pdf, doc, docx e imagens, até 10MB.</div>
                                <div class="js-anexos-list text-sm text-gray-600">Carregando anexos...</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
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
        const avaliacoesJson = document.getElementById('avaliacoesJson');
        const avaliacoesContainer = document.getElementById('avaliacoesContainer');
        const form = document.getElementById('auditoriaEditForm');
        const btnSalvar = document.getElementById('btnSalvar');
        const btnSalvarTopo = document.getElementById('btnSalvarTopo');
        const btnCancelar = document.getElementById('btnCancelar');
        let dirty = false;
        const clienteDebugStatus = document.getElementById('clienteDebugStatus');
        const backendErrors = <?= json_encode($errors, JSON_UNESCAPED_UNICODE) ?>;
        let questoes = [];
        let avaliacoes = [];
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
                questoes = [{ responsavel_nome: '', responsavel_ids: [], responsavel_labels: [], pergunta: '', referencia_esperada: '', processos: [] }];
            }
        };

        const syncHidden = ()=>{ questoesJson.value = JSON.stringify(questoes); dirty = true; };
        const normalizeResponsavelTokens = (raw)=>Array.from(new Set(
            String(raw || '')
                .split(',')
                .map((v)=>v.trim())
                .filter(Boolean)
                .map((v)=>v.replace(/\s+/g, ' '))
        ));
        const ensureResponsaveisShape = (idx)=>{
            questoes[idx].responsavel_ids = Array.isArray(questoes[idx].responsavel_ids) ? questoes[idx].responsavel_ids : [];
            questoes[idx].responsavel_labels = Array.isArray(questoes[idx].responsavel_labels) ? questoes[idx].responsavel_labels : [];
            if (!questoes[idx].responsavel_labels.length && (questoes[idx].responsavel_nome || '').trim()) {
                questoes[idx].responsavel_labels = normalizeResponsavelTokens(questoes[idx].responsavel_nome);
            }
            if (questoes[idx].responsavel_labels.length > questoes[idx].responsavel_ids.length) {
                questoes[idx].responsavel_ids = questoes[idx].responsavel_ids.concat(Array(questoes[idx].responsavel_labels.length - questoes[idx].responsavel_ids.length).fill(0));
            } else if (questoes[idx].responsavel_ids.length > questoes[idx].responsavel_labels.length) {
                questoes[idx].responsavel_ids = questoes[idx].responsavel_ids.slice(0, questoes[idx].responsavel_labels.length);
            }
        };
        const syncResponsavelNome = (idx)=>{ ensureResponsaveisShape(idx); questoes[idx].responsavel_nome = questoes[idx].responsavel_labels.join(', '); syncHidden(); };
        const hasResponsavelPreenchido = (idx)=>{
            ensureResponsaveisShape(idx);
            return questoes[idx].responsavel_labels.some((label)=>String(label || '').trim() !== '');
        };
        const addResponsavelItem = (idx, item)=>{
            ensureResponsaveisShape(idx);
            const nome = String(item?.nome || '').trim().replace(/\s+/g, ' ');
            const id = Number(item?.id || 0);
            if (!nome) {
                return false;
            }
            const exists = questoes[idx].responsavel_labels.some((label, pos)=>{
                const sameLabel = String(label || '').trim().toLowerCase() === nome.toLowerCase();
                const sameId = id > 0 && Number(questoes[idx].responsavel_ids[pos] || 0) === id;
                return sameLabel || sameId;
            });
            if (exists) {
                return false;
            }
            questoes[idx].responsavel_labels.push(nome);
            questoes[idx].responsavel_ids.push(id > 0 ? id : 0);
            syncResponsavelNome(idx);
            return true;
        };
        const addResponsaveisFromInput = (idx, raw)=>{
            const tokens = normalizeResponsavelTokens(raw);
            const sugestoes = Array.isArray(responsavelSugestoesPorQuestao.get(idx)) ? responsavelSugestoesPorQuestao.get(idx) : [];
            let added = 0;
            tokens.forEach((token)=>{
                const exactMatch = sugestoes.find((item)=>String(item?.nome || '').trim().toLowerCase() === token.toLowerCase());
                if (addResponsavelItem(idx, exactMatch ? { id: Number(exactMatch.id || 0), nome: String(exactMatch.nome || '') } : { id: 0, nome: token })) {
                    added++;
                }
            });
            return added;
        };
        const syncAvaliacoes = ()=>{
            if (!avaliacoesJson || !avaliacoesContainer) return;
            avaliacoes = Array.from(avaliacoesContainer.querySelectorAll('[data-avaliacao-card]')).map((card)=>{
                const questaoId = Number(card.getAttribute('data-avaliacao-card') || '0');
                const conformidade = card.querySelector('.js-conformidade')?.value || 'pendente';
                const observacoes = card.querySelector('.js-observacoes')?.value || '';
                return { questao_id: questaoId, conformidade, observacoes };
            }).filter((item)=>item.questao_id > 0);
            avaliacoesJson.value = JSON.stringify(avaliacoes);
            dirty = true;
        };

        const refreshAnexos = (questaoId, listEl)=>{
            if (!listEl || !questaoId) return;
            listEl.textContent = 'Carregando anexos...';
            fetch('index.php?route=auditorias/api_list_anexos&auditoria_id=' + encodeURIComponent(<?= (int)$item['id'] ?>) + '&questao_id=' + encodeURIComponent(questaoId))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = Array.isArray(json.items) ? json.items : [];
                    if (!items.length) {
                        listEl.innerHTML = '<div class="text-gray-500">Nenhum anexo enviado.</div>';
                        return;
                    }
                    listEl.innerHTML = items.map((file)=>`
                        <div class="border rounded bg-white p-2 mb-2 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <input type="text" class="border rounded p-1 w-full mb-1 js-file-name" value="${String(file.name || '').replace(/"/g, '&quot;')}" data-file-id="${file.id}" />
                                <input type="text" class="border rounded p-1 w-full js-file-desc" value="${String(file.descricao || '').replace(/"/g, '&quot;')}" data-file-id="${file.id}" placeholder="Descrição do anexo" />
                                <div class="text-xs text-gray-500 mt-1">${Math.round((Number(file.size || 0) / 1024) * 10) / 10} KB</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a class="px-2 py-1 rounded bg-gray-200 text-brand-brown text-xs" href="index.php?route=auditorias/download_anexo&id=${file.id}" target="_blank">Abrir</a>
                                <button type="button" class="px-2 py-1 rounded bg-blue-600 text-white text-xs js-save-file" data-file-id="${file.id}">Salvar</button>
                                <button type="button" class="px-2 py-1 rounded bg-red-600 text-white text-xs js-del-file" data-file-id="${file.id}">Excluir</button>
                            </div>
                        </div>`).join('');
                    listEl.querySelectorAll('.js-save-file').forEach((btn)=>btn.addEventListener('click', ()=>{
                        const fileId = btn.getAttribute('data-file-id');
                        const name = listEl.querySelector(`.js-file-name[data-file-id="${fileId}"]`)?.value || '';
                        const descricao = listEl.querySelector(`.js-file-desc[data-file-id="${fileId}"]`)?.value || '';
                        const fd = new FormData();
                        fd.append('csrf', <?= json_encode(Security::csrfToken()) ?>);
                        fd.append('id', fileId);
                        fd.append('name', name);
                        fd.append('descricao', descricao);
                        fetch('index.php?route=auditorias/api_update_anexo', { method: 'POST', body: fd })
                            .then((r)=>r.json())
                            .then((json)=>{ if (!json.success) throw new Error(json.message || 'Erro'); })
                            .then(()=>refreshAnexos(questaoId, listEl))
                            .catch((err)=>alert(err.message || 'Erro ao salvar metadados do anexo.'));
                    }));
                    listEl.querySelectorAll('.js-del-file').forEach((btn)=>btn.addEventListener('click', ()=>{
                        if (!confirm('Excluir este anexo?')) return;
                        const fd = new FormData();
                        fd.append('csrf', <?= json_encode(Security::csrfToken()) ?>);
                        fd.append('id', btn.getAttribute('data-file-id'));
                        fetch('index.php?route=auditorias/api_delete_anexo', { method: 'POST', body: fd })
                            .then((r)=>r.json())
                            .then((json)=>{ if (!json.success) throw new Error(json.message || 'Erro'); })
                            .then(()=>refreshAnexos(questaoId, listEl))
                            .catch((err)=>alert(err.message || 'Erro ao excluir anexo.'));
                    }));
                })
                .catch(()=>{ listEl.innerHTML = '<div class="text-red-600">Falha ao carregar anexos.</div>'; });
        };

        const initAvaliacoesUI = ()=>{
            if (!avaliacoesContainer) return;
            avaliacoesContainer.querySelectorAll('[data-avaliacao-card]').forEach((card)=>{
                const questaoId = Number(card.getAttribute('data-avaliacao-card') || '0');
                card.querySelector('.js-conformidade')?.addEventListener('change', syncAvaliacoes);
                card.querySelector('.js-observacoes')?.addEventListener('input', syncAvaliacoes);
                const listEl = card.querySelector('.js-anexos-list');
                refreshAnexos(questaoId, listEl);
                card.querySelector('.js-upload-anexo')?.addEventListener('change', (e)=>{
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (!['pdf','doc','docx','png','jpg','jpeg','gif','webp'].includes(ext)) {
                        alert('Tipo de arquivo inválido.');
                        e.target.value = '';
                        return;
                    }
                    if (file.size > (10 * 1024 * 1024)) {
                        alert('Arquivo excede o limite de 10MB.');
                        e.target.value = '';
                        return;
                    }
                    const fd = new FormData();
                    fd.append('csrf', <?= json_encode(Security::csrfToken()) ?>);
                    fd.append('auditoria_id', <?= (int)$item['id'] ?>);
                    fd.append('questao_id', String(questaoId));
                    fd.append('arquivo', file);
                    fetch('index.php?route=auditorias/api_upload_anexo', { method: 'POST', body: fd })
                        .then((r)=>r.json())
                        .then((json)=>{ if (!json.success) throw new Error(json.message || 'Erro'); })
                        .then(()=>{ e.target.value = ''; refreshAnexos(questaoId, listEl); })
                        .catch((err)=>alert(err.message || 'Erro ao enviar anexo.'));
                });
            });
            syncAvaliacoes();
        };

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
                ensureResponsaveisShape(index);
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
                            <label class="block text-xs">Responsáveis</label>
                            <div class="flex gap-2">
                                <input type="text" class="border rounded p-2 w-full" data-responsavel-search="${index}" autocomplete="off" placeholder="Buscar colaborador ou digitar nomes externos" />
                                <button type="button" class="px-2 py-1 rounded bg-brand-pink text-white text-xs" data-add-responsavel="${index}">Adicionar</button>
                            </div>
                            <div class="hidden absolute z-10 w-full mt-1 bg-white border rounded shadow max-h-52 overflow-auto" data-responsavel-menu="${index}"></div>
                            <div class="mt-2 flex flex-wrap gap-2" data-selected-responsaveis="${index}"></div>
                            <div class="text-xs text-gray-500 mt-1" data-responsavel-status="${index}"></div>
                            ${erroResponsavel ? `<div class="text-xs text-red-600 mt-1">${erroResponsavel}</div>` : ''}
                        </div>
                        <div class="md:col-span-8">
                            <label class="block text-xs">Pergunta de Auditoria</label>
                            <textarea class="border rounded p-2 w-full" rows="3" data-pergunta="${index}">${q.pergunta || ''}</textarea>
                            <div class="text-xs text-gray-500" data-pergunta-count="${index}">${(q.pergunta || '').length}/1000</div>
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
            questoesContainer.querySelectorAll('[data-add-responsavel]').forEach((el)=>el.addEventListener('click', ()=>{
                const idx = Number(el.getAttribute('data-add-responsavel'));
                const input = questoesContainer.querySelector(`[data-responsavel-search="${idx}"]`);
                const value = (input?.value || '').trim();
                if (!value) return;
                const added = addResponsaveisFromInput(idx, value);
                if (input) input.value = '';
                renderQuestoes();
                setResponsavelStatus(idx, added > 0 ? 'Responsável(eis) adicionados.' : 'Responsável já informado.');
            }));
            questoesContainer.querySelectorAll('[data-responsavel-search]').forEach((el)=>{
                el.addEventListener('input', ()=>{
                    const idx = Number(el.getAttribute('data-responsavel-search'));
                    const key = `q_${idx}`;
                    if (responsavelDebounce.has(key)) {
                        clearTimeout(responsavelDebounce.get(key));
                    }
                    responsavelDebounce.set(key, setTimeout(()=>{
                        fetchSugestoesResponsavel(idx, el.value || '');
                    }, 250));
                });
                el.addEventListener('blur', ()=>{
                    const idx = Number(el.getAttribute('data-responsavel-search'));
                    setTimeout(()=>{
                        const menu = questoesContainer.querySelector(`[data-responsavel-menu="${idx}"]`);
                        if (menu) menu.classList.add('hidden');
                    }, 150);
                });
                el.addEventListener('keydown', (ev)=>{
                    if (ev.key !== 'Enter') return;
                    ev.preventDefault();
                    const idx = Number(el.getAttribute('data-responsavel-search'));
                    const value = (el.value || '').trim();
                    if (!value) return;
                    const added = addResponsaveisFromInput(idx, value);
                    el.value = '';
                    renderQuestoes();
                    setResponsavelStatus(idx, added > 0 ? 'Responsável(eis) adicionados.' : 'Responsável já informado.');
                });
            });
            questoesContainer.querySelectorAll('[data-pergunta]').forEach((el)=>el.addEventListener('input', ()=>{
                const idx = Number(el.getAttribute('data-pergunta'));
                questoes[idx].pergunta = el.value;
                const countEl = questoesContainer.querySelector(`[data-pergunta-count="${idx}"]`);
                if (countEl) {
                    countEl.textContent = `${(el.value || '').length}/1000`;
                }
                syncHidden();
            }));
            questoesContainer.querySelectorAll('[data-referencia]').forEach((el)=>el.addEventListener('input', ()=>{ questoes[Number(el.getAttribute('data-referencia'))].referencia_esperada = el.value; syncHidden(); }));
            questoes.forEach((_, index)=>{
                const selected = questoesContainer.querySelector(`[data-selected-responsaveis="${index}"]`);
                if (!selected) return;
                selected.innerHTML = '';
                ensureResponsaveisShape(index);
                questoes[index].responsavel_labels.forEach((label, pos)=>{
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'px-2 py-1 rounded bg-gray-200 text-brand-brown text-xs';
                    chip.textContent = label + ' x';
                    chip.addEventListener('click', ()=>{
                        questoes[index].responsavel_labels.splice(pos, 1);
                        questoes[index].responsavel_ids.splice(pos, 1);
                        syncResponsavelNome(index);
                        renderQuestoes();
                    });
                    selected.appendChild(chip);
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

        const applySugestoesResponsavel = (idx, items, autoAddFirst=false)=>{
            const menu = questoesContainer.querySelector(`[data-responsavel-menu="${idx}"]`);
            if (menu) menu.innerHTML = '';
            const validItems = [];
            items.forEach((item)=>{
                const id = Number(item?.id || 0);
                const nome = (item && item.nome) ? String(item.nome).trim() : '';
                if (!nome || id <= 0) return;
                validItems.push({ id, nome });
                if (menu) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'block w-full text-left px-3 py-2 hover:bg-gray-100';
                    btn.textContent = nome;
                    btn.addEventListener('mousedown', (e)=>{
                        e.preventDefault();
                        addResponsavelItem(idx, { id, nome });
                        const input = questoesContainer.querySelector(`[data-responsavel-search="${idx}"]`);
                        if (input) input.value = '';
                        renderQuestoes();
                    });
                    menu.appendChild(btn);
                }
            });
            if (autoAddFirst && menu && menu.firstElementChild) {
                menu.firstElementChild.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                return;
            }
            responsavelSugestoesPorQuestao.set(idx, validItems);
            setResponsavelStatus(idx, validItems.length ? `${validItems.length} sugestão(ões) encontrada(s)` : 'Nenhum colaborador encontrado. Você pode adicionar o nome manualmente.');
            if (menu) {
                if (validItems.length) menu.classList.remove('hidden');
                else menu.classList.add('hidden');
            }
        };

        const fetchSugestoesResponsavel = (idx, query, autoAddFirst=false)=>{
            const clienteId = clienteSelect.value;
            const q = (query || '').trim();
            if (!clienteId) {
                setResponsavelStatus(idx, 'Selecione a empresa para buscar responsáveis.');
                return;
            }
            if (q.length < 2) {
                responsavelSugestoesPorQuestao.set(idx, []);
                setResponsavelStatus(idx, q.length ? 'Clique em Adicionar para usar texto livre ou digite mais para buscar colaboradores.' : 'Digite um nome externo ou busque um colaborador.');
                return;
            }
            fetch('index.php?route=auditorias/api_colaboradores&cliente_id=' + encodeURIComponent(clienteId) + '&q=' + encodeURIComponent(q))
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    applySugestoesResponsavel(idx, items, autoAddFirst);
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
            questoes.push({ responsavel_nome: '', responsavel_ids: [], responsavel_labels: [], pergunta: '', referencia_esperada: '', processos: [] });
            renderQuestoes();
        });
        clienteSelect?.addEventListener('change', ()=>{
            loadSetores();
            questoes.forEach((questao)=>{
                questao.responsavel_ids = [];
                questao.responsavel_labels = [];
                questao.responsavel_nome = '';
            });
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
                const input = questoesContainer.querySelector(`[data-responsavel-search="${i}"]`);
                if (input && (input.value || '').trim()) {
                    addResponsaveisFromInput(i, input.value || '');
                    input.value = '';
                }
                if (!hasResponsavelPreenchido(i)) { e.preventDefault(); alert(`Questão ${i + 1}: selecione pelo menos 1 responsável.`); return; }
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
        initAvaliacoesUI();
        document.addEventListener('click', (e)=>{
            const t = e.target;
            if (!(t instanceof HTMLElement)) return;
            if (t.closest('[data-responsavel-menu]') || t.closest('[data-responsavel-search]')) return;
            questoesContainer.querySelectorAll('[data-responsavel-menu]').forEach((m)=>m.classList.add('hidden'));
        });
    })();
</script>
