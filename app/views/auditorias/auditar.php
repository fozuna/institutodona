<?php use App\Core\Security; /** @var array $item */ /** @var array $respostas */ /** @var array $errors */ ?>
<div class="p-6 max-w-6xl">
    <div class="sticky top-2 z-20 bg-white border rounded shadow-sm px-4 py-3 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Avaliação da Auditoria</h1>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($item['nome_auditoria'] ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span id="autosaveStatus" class="text-xs text-gray-500">Autosave inativo</span>
            <button type="submit" form="finalizarForm" id="btnFinalizarTopo" class="px-4 py-2 rounded bg-brand-red text-white">Finalizar Auditoria</button>
        </div>
    </div>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3 mb-3">
            <?= htmlspecialchars(implode(' ', array_values($errors))) ?>
        </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=auditorias/finalizar" id="finalizarForm" class="space-y-4">
        <input type="hidden" name="csrf" id="csrfField" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
        <input type="hidden" name="avaliacoes_json" id="avaliacoesJson" value="[]" />
        <?php foreach (($item['questoes'] ?? []) as $idx => $questao): ?>
            <?php $resp = $respostas[(int)$questao['id']] ?? []; ?>
            <div class="bg-white rounded shadow p-4">
                <div class="text-sm text-gray-500 mb-1">Questão <?= $idx + 1 ?></div>
                <div class="font-semibold mb-2"><?= nl2br(htmlspecialchars((string)$questao['pergunta'])) ?></div>
                <div class="text-sm mb-2"><strong>Referência esperada:</strong> <?= nl2br(htmlspecialchars((string)$questao['referencia_esperada'])) ?></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm">Conformidade</label>
                        <select class="border rounded p-2 w-full avaliacao-conformidade" data-questao-id="<?= (int)$questao['id'] ?>">
                            <option value="">Selecione</option>
                            <option value="conforme" <?= (($resp['conformidade'] ?? '') === 'conforme') ? 'selected' : '' ?>>Conforme</option>
                            <option value="nao_conforme" <?= (($resp['conformidade'] ?? '') === 'nao_conforme') ? 'selected' : '' ?>>Não conforme</option>
                            <option value="nao_aplica" <?= (($resp['conformidade'] ?? '') === 'nao_aplica') ? 'selected' : '' ?>>Não se aplica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Observações</label>
                        <textarea class="border rounded p-2 w-full avaliacao-observacoes" rows="3" maxlength="2000" data-questao-id="<?= (int)$questao['id'] ?>"><?= htmlspecialchars((string)($resp['observacoes'] ?? '')) ?></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-semibold">Anexos da Questão</label>
                        <div class="text-xs text-gray-500" data-anexo-status="<?= (int)$questao['id'] ?>"></div>
                    </div>
                    <input type="file" multiple class="border rounded p-2 w-full mt-2" data-anexo-input="<?= (int)$questao['id'] ?>" />
                    <div class="text-xs text-gray-500 mt-1">Limites: 10MB por arquivo, 50MB por questão.</div>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2" data-anexo-list="<?= (int)$questao['id'] ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="flex items-center justify-end gap-2">
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</a>
            <button type="submit" id="btnFinalizar" class="px-4 py-2 rounded bg-brand-red text-white">Finalizar Auditoria</button>
        </div>
    </form>
</div>
<script>
    (function(){
        const form = document.getElementById('finalizarForm');
        const avaliacoesJson = document.getElementById('avaliacoesJson');
        const autosaveStatus = document.getElementById('autosaveStatus');
        const csrfField = document.getElementById('csrfField');
        const btnFinalizar = document.getElementById('btnFinalizar');
        const btnFinalizarTopo = document.getElementById('btnFinalizarTopo');
        const auditoriaId = <?= (int)$item['id'] ?>;
        const allowed = new Set(['conforme','nao_conforme','nao_aplica']);

        const collectAvaliacoes = ()=>{
            const map = new Map();
            document.querySelectorAll('.avaliacao-conformidade').forEach((el)=>{
                const id = Number(el.getAttribute('data-questao-id'));
                map.set(id, { questao_id: id, conformidade: (el.value || '').trim(), observacoes: '' });
            });
            document.querySelectorAll('.avaliacao-observacoes').forEach((el)=>{
                const id = Number(el.getAttribute('data-questao-id'));
                const prev = map.get(id) || { questao_id: id, conformidade: '', observacoes: '' };
                prev.observacoes = (el.value || '').trim();
                map.set(id, prev);
            });
            return Array.from(map.values());
        };

        const updateHidden = ()=>{ avaliacoesJson.value = JSON.stringify(collectAvaliacoes()); };

        const autosave = ()=>{
            const payload = new URLSearchParams();
            payload.set('csrf', csrfField.value);
            payload.set('id', String(auditoriaId));
            payload.set('avaliacoes_json', JSON.stringify(collectAvaliacoes()));
            fetch('index.php?route=auditorias/api_autosave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: payload.toString(),
            })
            .then((r)=>r.json())
            .then((json)=>{
                if (json && json.success) {
                    autosaveStatus.textContent = 'Rascunho salvo às ' + new Date().toLocaleTimeString('pt-BR');
                } else {
                    autosaveStatus.textContent = 'Falha no autosave';
                }
            })
            .catch(()=>{ autosaveStatus.textContent = 'Falha no autosave'; });
        };

        form.addEventListener('submit', (e)=>{
            const avaliacoes = collectAvaliacoes();
            const incompletas = avaliacoes.filter((a)=>!allowed.has(a.conformidade));
            if (incompletas.length > 0) {
                e.preventDefault();
                alert('Preencha a conformidade de todas as questões antes de finalizar.');
                return;
            }
            avaliacoesJson.value = JSON.stringify(avaliacoes);
            btnFinalizar.disabled = true;
            btnFinalizarTopo.disabled = true;
            btnFinalizar.textContent = 'Finalizando...';
            btnFinalizarTopo.textContent = 'Finalizando...';
        });

        document.querySelectorAll('.avaliacao-conformidade, .avaliacao-observacoes').forEach((el)=>{
            el.addEventListener('change', updateHidden);
            el.addEventListener('input', updateHidden);
        });

        updateHidden();
        autosaveStatus.textContent = 'Autosave ativo (30s)';
        setInterval(autosave, 30000);

        const setAnexoStatus = (questaoId, message, isError=false)=>{
            const el = document.querySelector(`[data-anexo-status="${questaoId}"]`);
            if (!el) return;
            el.textContent = message;
            el.className = `text-xs ${isError ? 'text-red-600' : 'text-gray-500'}`;
        };

        const renderAnexos = (questaoId, items)=>{
            const list = document.querySelector(`[data-anexo-list="${questaoId}"]`);
            if (!list) return;
            list.innerHTML = '';
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'text-xs text-gray-500';
                empty.textContent = 'Nenhum anexo.';
                list.appendChild(empty);
                return;
            }
            items.forEach((it)=>{
                const card = document.createElement('a');
                card.href = `index.php?route=auditorias/download_anexo&id=${it.id}`;
                card.className = 'border rounded p-2 text-xs block hover:bg-gray-50';
                const name = it.name || ('arquivo_' + it.id);
                const size = it.size ? ` (${Math.round(it.size/1024)} KB)` : '';
                if (it.has_thumb) {
                    const img = document.createElement('img');
                    img.src = `index.php?route=auditorias/thumb_anexo&id=${it.id}`;
                    img.className = 'w-full h-20 object-cover rounded mb-1';
                    card.appendChild(img);
                }
                const t = document.createElement('div');
                t.textContent = name + size;
                card.appendChild(t);
                list.appendChild(card);
            });
        };

        const loadAnexos = (questaoId)=>{
            setAnexoStatus(questaoId, 'Carregando...');
            fetch(`index.php?route=auditorias/api_list_anexos&auditoria_id=${encodeURIComponent(auditoriaId)}&questao_id=${encodeURIComponent(questaoId)}`)
                .then((r)=>r.json())
                .then((json)=>{
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    renderAnexos(questaoId, items);
                    setAnexoStatus(questaoId, '');
                })
                .catch((err)=>{
                    console.error('[auditorias/auditar] falha ao listar anexos', err);
                    setAnexoStatus(questaoId, 'Erro ao carregar anexos.', true);
                });
        };

        document.querySelectorAll('[data-anexo-input]').forEach((input)=>{
            input.addEventListener('change', async ()=>{
                const questaoId = Number(input.getAttribute('data-anexo-input'));
                const files = Array.from(input.files || []);
                if (!files.length) return;
                setAnexoStatus(questaoId, 'Enviando anexos...');
                const fd = new FormData();
                fd.append('csrf', csrfField.value);
                fd.append('auditoria_id', String(auditoriaId));
                fd.append('questao_id', String(questaoId));
                files.forEach(f=>fd.append('files[]', f));
                try {
                    const res = await fetch('index.php?route=auditorias/api_upload_anexo', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json && json.success) {
                        loadAnexos(questaoId);
                        setAnexoStatus(questaoId, '');
                    } else {
                        setAnexoStatus(questaoId, (json && json.message) ? json.message : 'Falha ao enviar anexos.', true);
                    }
                } catch (err) {
                    console.error('[auditorias/auditar] falha ao enviar anexos', err);
                    setAnexoStatus(questaoId, 'Erro ao enviar anexos.', true);
                } finally {
                    input.value = '';
                }
            });
        });

        document.querySelectorAll('[data-anexo-list]').forEach((el)=>{
            const questaoId = Number(el.getAttribute('data-anexo-list'));
            if (questaoId) loadAnexos(questaoId);
        });
    })();
</script>
