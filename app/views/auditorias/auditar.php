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
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Observações</label>
                        <textarea class="border rounded p-2 w-full avaliacao-observacoes" rows="3" maxlength="2000" data-questao-id="<?= (int)$questao['id'] ?>"><?= htmlspecialchars((string)($resp['observacoes'] ?? '')) ?></textarea>
                    </div>
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
            const incompletas = avaliacoes.filter((a)=>a.conformidade !== 'conforme' && a.conformidade !== 'nao_conforme');
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
    })();
</script>
