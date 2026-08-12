<?php use App\Core\DateHelper; use App\Core\Security; /** @var array $item */ /** @var array $respostas */ /** @var bool $canManage */ /** @var bool $canReopen */ /** @var bool $canCorrect */ ?>
<div class="p-6 max-w-6xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($item['nome_auditoria'] ?? '') ?></h1>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($item['cliente_nome'] ?? '') ?> · <?= htmlspecialchars($item['setor_nome'] ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Voltar</a>
            <?php if (!empty($canManage)): ?>
                <a href="index.php?route=auditorias/edit&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-brand-pink text-white">Editar</a>
            <?php endif; ?>
            <?php if (!empty($canManage) && (($item['status'] ?? '') !== 'Realizada')): ?>
                <a href="index.php?route=auditorias/auditar&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-brand-red text-white">Auditar</a>
            <?php endif; ?>
            <?php if (!empty($canCorrect) && (($item['status'] ?? '') === 'Realizada')): ?>
                <button type="button" id="btnOpenCorrigir" class="px-4 py-2 rounded border border-gray-400 text-brand-brown bg-white">Corrigir classificação</button>
            <?php endif; ?>
            <?php if (!empty($canReopen) && (($item['status'] ?? '') === 'Realizada')): ?>
                <button type="button" id="btnOpenReabrir" class="px-4 py-2 rounded bg-gray-700 text-white">Reabrir auditoria</button>
            <?php endif; ?>
            <a href="index.php?route=auditorias/relatorio_pdf&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-brand-pink text-white" target="_blank">PDF</a>
        </div>
    </div>
    <div class="bg-white rounded shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div><span class="text-xs text-gray-500">Data agendada</span><div class="font-semibold"><?= htmlspecialchars(DateHelper::formatDate((string)($item['data_auditoria'] ?? ''))) ?></div></div>
        <div><span class="text-xs text-gray-500">Status</span><div class="font-semibold"><?= htmlspecialchars($item['status'] ?? '') ?></div></div>
        <div><span class="text-xs text-gray-500">Realizada em</span><div class="font-semibold"><?= !empty($item['realizada_at']) ? htmlspecialchars(DateHelper::formatDateTime((string)$item['realizada_at'])) : '-' ?></div></div>
        <div><span class="text-xs text-gray-500">Total de questões</span><div class="font-semibold"><?= count($item['questoes'] ?? []) ?></div></div>
    </div>
    <?php if (!empty($item['responsaveis_nomes'])): ?>
    <div class="bg-white rounded shadow p-4 mb-4">
        <span class="text-xs text-gray-500">Responsáveis da auditoria</span>
        <div class="font-semibold"><?= htmlspecialchars((string)$item['responsaveis_nomes']) ?></div>
    </div>
    <?php endif; ?>
    <?php
        $totConforme = 0;
        $totNaoConforme = 0;
        $totNaoAplica = 0;
        foreach (($item['questoes'] ?? []) as $q) {
            $r = $respostas[(int)$q['id']] ?? [];
            $c = (string)($r['conformidade'] ?? '');
            if ($c === 'conforme') $totConforme++;
            elseif ($c === 'nao_conforme') $totNaoConforme++;
            elseif ($c === 'nao_aplica') $totNaoAplica++;
        }
        $split = \App\Models\AuditoriaModel::percentSplit($totConforme, $totNaoConforme);
        $pctConforme = number_format((float)$split['conforme'], 2, ',', '.');
        $pctNaoConforme = number_format((float)$split['nao_conforme'], 2, ',', '.');
        $semaforo = (string)($item['semaforo'] ?? '');
        $semClass = $semaforo === 'verde' ? 'bg-green-100 text-green-800' : ($semaforo === 'amarelo' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
    ?>
    <div class="bg-white rounded shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <span class="text-xs text-gray-500">Conforme</span>
            <div class="font-semibold"><?= $totConforme ?> (<?= $pctConforme ?>%)</div>
        </div>
        <div>
            <span class="text-xs text-gray-500">Não conforme</span>
            <div class="font-semibold"><?= $totNaoConforme ?> (<?= $pctNaoConforme ?>%)</div>
        </div>
        <div>
            <span class="text-xs text-gray-500">Não se aplica</span>
            <div class="font-semibold"><?= $totNaoAplica ?></div>
        </div>
        <div>
            <span class="text-xs text-gray-500">Semáforo</span>
            <div class="mt-1 inline-flex px-2 py-1 rounded text-xs font-semibold <?= $semClass ?>"><?= $semaforo !== '' ? htmlspecialchars(strtoupper($semaforo)) : '-' ?></div>
        </div>
    </div>
    <div class="space-y-3">
        <?php foreach (($item['questoes'] ?? []) as $idx => $questao): ?>
            <?php $resp = $respostas[(int)$questao['id']] ?? null; ?>
            <div class="bg-white rounded shadow p-4">
                <div class="text-sm text-gray-500 mb-1">Questão <?= $idx + 1 ?></div>
                <div class="font-semibold mb-2"><?= nl2br(htmlspecialchars((string)$questao['pergunta'])) ?></div>
                <div class="text-sm mb-1"><strong>Responsável:</strong> <?= htmlspecialchars((string)$questao['responsavel_nome']) ?></div>
                <div class="text-sm mb-1"><strong>Referência esperada:</strong> <?= nl2br(htmlspecialchars((string)$questao['referencia_esperada'])) ?></div>
                <?php $processos = is_array($questao['processos'] ?? null) ? $questao['processos'] : []; ?>
                <div class="text-sm mb-1"><strong>Processos:</strong> <?= !empty($processos) ? htmlspecialchars(implode(', ', $processos)) : 'Nenhum' ?></div>
                <div class="text-sm"><strong>Conformidade:</strong> <?= htmlspecialchars((string)($resp['conformidade'] ?? 'pendente')) ?></div>
                <?php if (!empty($resp['observacoes'])): ?>
                    <div class="text-sm mt-1"><strong>Observações:</strong> <?= nl2br(htmlspecialchars((string)$resp['observacoes'])) ?></div>
                <?php endif; ?>
                <?php if (($item['status'] ?? '') === 'Realizada'): ?>
                <div class="mt-3">
                    <div class="text-sm font-semibold mb-2">Anexos</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2" data-gallery="<?= (int)$questao['id'] ?>"></div>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php if (!empty($canReopen) && (($item['status'] ?? '') === 'Realizada')): ?>
<div id="modalReabrir" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold mb-3">Reabrir auditoria</h2>
        <p class="text-sm text-gray-700 mb-4">Esta auditoria voltará para "Em Auditoria" e poderá ser editada novamente.</p>
        <form method="post" action="index.php?route=auditorias/reabrir" class="space-y-3">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <div>
                <label class="block text-sm" for="reabrirMotivo">Motivo da reabertura</label>
                <textarea id="reabrirMotivo" name="motivo" class="border rounded p-2 w-full" rows="3" maxlength="1000" required></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="cancelReabrir" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</button>
                <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white">Reabrir auditoria</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function(){
        const btnOpen = document.getElementById('btnOpenReabrir');
        const modal = document.getElementById('modalReabrir');
        const btnCancel = document.getElementById('cancelReabrir');
        if (!btnOpen || !modal) return;
        btnOpen.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const textarea = document.getElementById('reabrirMotivo');
            if (textarea) textarea.focus();
        });
        if (btnCancel) {
            btnCancel.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }
    })();
</script>
<?php endif; ?>
<?php if (!empty($canCorrect) && (($item['status'] ?? '') === 'Realizada')): ?>
<div id="modalCorrigirClassificacao" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold mb-3">Corrigir classificação</h2>
        <p class="text-sm text-gray-700 mb-4">A auditoria permanecerá finalizada. Apenas sua classificação será corrigida e a alteração ficará registrada no histórico.</p>
        <form method="post" action="index.php?route=auditorias/corrigir_classificacao" class="space-y-3">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="prev_lock_version" value="<?= (int)($item['lock_version'] ?? 0) ?>" />
            <div>
                <label class="block text-sm" for="corrigirDepartamentoSelect">Departamento</label>
                <select id="corrigirDepartamentoSelect" name="departamento_id" class="border rounded p-2 w-full" required>
                    <option value="">Carregando...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm" for="corrigirSetorSelect">Setor</label>
                <select id="corrigirSetorSelect" name="setor_id" class="border rounded p-2 w-full" required>
                    <option value="">Selecione o departamento primeiro</option>
                </select>
            </div>
            <div>
                <label class="block text-sm" for="corrigirMotivo">Motivo da correção</label>
                <textarea id="corrigirMotivo" name="motivo" class="border rounded p-2 w-full" rows="3" maxlength="1000" required></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="cancelCorrigir" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</button>
                <button type="submit" class="px-3 py-2 rounded bg-brand-pink text-white">Salvar correção</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function(){
        const btnOpen = document.getElementById('btnOpenCorrigir');
        const modal = document.getElementById('modalCorrigirClassificacao');
        const btnCancel = document.getElementById('cancelCorrigir');
        const depSelect = document.getElementById('corrigirDepartamentoSelect');
        const setSelect = document.getElementById('corrigirSetorSelect');
        if (!btnOpen || !modal || !depSelect || !setSelect) return;
        const clienteId = <?= (int)$item['cliente_id'] ?>;
        const setorAtualId = <?= (int)$item['setor_id'] ?>;
        let setoresData = null;

        function populateDepartamentos(items, selectedDepId) {
            const deps = new Map();
            items.forEach((it) => {
                if (!deps.has(it.departamento_id)) {
                    deps.set(it.departamento_id, it.departamento || ('Departamento ' + it.departamento_id));
                }
            });
            depSelect.innerHTML = '<option value="">Selecione</option>';
            deps.forEach((nome, id) => {
                const opt = document.createElement('option');
                opt.value = String(id);
                opt.textContent = nome;
                if (id === selectedDepId) opt.selected = true;
                depSelect.appendChild(opt);
            });
        }

        function populateSetores(items, departamentoId, selectedSetorId) {
            setSelect.innerHTML = '<option value="">Selecione</option>';
            items
                .filter((it) => it.departamento_id === departamentoId)
                .forEach((it) => {
                    const opt = document.createElement('option');
                    opt.value = String(it.id);
                    opt.textContent = it.nome;
                    if (selectedSetorId !== null && it.id === selectedSetorId) opt.selected = true;
                    setSelect.appendChild(opt);
                });
        }

        function openWithData(items) {
            const atual = items.find((it) => it.id === setorAtualId);
            const depAtualId = atual ? atual.departamento_id : null;
            populateDepartamentos(items, depAtualId);
            populateSetores(items, depAtualId, setorAtualId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function loadAndOpen() {
            if (setoresData) {
                openWithData(setoresData);
                return;
            }
            depSelect.innerHTML = '<option value="">Carregando...</option>';
            fetch('index.php?route=auditorias/api_setores&cliente_id=' + encodeURIComponent(clienteId))
                .then((r) => r.json())
                .then((json) => {
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    setoresData = items.map((it) => ({
                        id: parseInt(it.id, 10),
                        nome: it.nome,
                        departamento_id: parseInt(it.departamento_id, 10),
                        departamento: it.departamento,
                    }));
                    openWithData(setoresData);
                })
                .catch(() => {
                    setoresData = [];
                    depSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                });
        }

        btnOpen.addEventListener('click', loadAndOpen);
        depSelect.addEventListener('change', () => {
            const depId = depSelect.value ? parseInt(depSelect.value, 10) : null;
            populateSetores(setoresData || [], depId, null);
        });
        if (btnCancel) {
            btnCancel.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }
    })();
</script>
<?php endif; ?>
<?php if (($item['status'] ?? '') === 'Realizada'): ?>
<div id="imgOverlay" class="fixed inset-0 bg-black/85 hidden items-center justify-center z-[60]" role="dialog" aria-modal="true" aria-label="Visualizador de anexo">
    <button type="button" id="ovClose" class="absolute top-4 right-4 px-3 py-2 rounded bg-white/20 text-white">Fechar ✕</button>
    <button type="button" id="ovPrev" class="absolute left-3 md:left-6 px-3 py-2 rounded bg-white/20 text-white">◀</button>
    <button type="button" id="ovNext" class="absolute right-3 md:right-6 px-3 py-2 rounded bg-white/20 text-white">▶</button>
    <div id="ovPanel" tabindex="-1" class="w-[95vw] h-[90vh] flex flex-col items-center justify-center gap-3">
        <div class="flex items-center gap-2">
            <button type="button" id="ovZoomOut" class="px-3 py-2 rounded bg-white/20 text-white">-</button>
            <button type="button" id="ovZoomIn" class="px-3 py-2 rounded bg-white/20 text-white">+</button>
            <a id="ovDownload" href="#" target="_blank" class="px-3 py-2 rounded bg-white/20 text-white">Download</a>
        </div>
        <div class="text-white text-xs" id="ovCaption"></div>
        <img id="ovImg" alt="Imagem ampliada" class="max-w-full max-h-[80vh] object-contain rounded shadow-xl" />
        <iframe id="ovFrame" title="Visualização de anexo" class="hidden w-full h-[80vh] rounded bg-white"></iframe>
    </div>
</div>
<script>
    (function(){
        const auditoriaId = <?= (int)$item['id'] ?>;
        const allowedImage = (name = '')=>/\.(jpg|jpeg|png|gif|webp)$/i.test(String(name));
        const allowedPdf = (name = '')=>/\.pdf$/i.test(String(name));
        const allImages = [];
        let current = 0;
        let scale = 1;
        let lastFocus = null;
        const overlay = document.getElementById('imgOverlay');
        const ovPanel = document.getElementById('ovPanel');
        const ovImg = document.getElementById('ovImg');
        const ovFrame = document.getElementById('ovFrame');
        const ovCaption = document.getElementById('ovCaption');
        const ovDownload = document.getElementById('ovDownload');
        const ovClose = document.getElementById('ovClose');
        const ovPrev = document.getElementById('ovPrev');
        const ovNext = document.getElementById('ovNext');
        const ovZoomIn = document.getElementById('ovZoomIn');
        const ovZoomOut = document.getElementById('ovZoomOut');

        const applyZoom = ()=>{ ovImg.style.transform = `scale(${scale})`; };
        const openAt = (idx)=>{
            if (!allImages.length) return;
            current = Math.max(0, Math.min(idx, allImages.length - 1));
            const it = allImages[current];
            scale = 1;
            ovImg.classList.add('hidden');
            ovFrame.classList.add('hidden');
            if (it.type === 'pdf') {
                ovFrame.src = it.full;
                ovFrame.classList.remove('hidden');
            } else if (it.type === 'other') {
                ovFrame.src = it.full;
                ovFrame.classList.remove('hidden');
            } else {
                ovImg.src = it.full;
                ovImg.alt = it.name || 'imagem';
                ovImg.classList.remove('hidden');
            }
            ovCaption.textContent = `${current + 1}/${allImages.length} · ${it.name || ''}`;
            ovDownload.href = it.download;
            lastFocus = document.activeElement;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            applyZoom();
            ovPanel.focus();
        };
        const closeOverlay = ()=>{
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            ovFrame.src = 'about:blank';
            if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
        };
        const nav = (dir)=>{
            if (!allImages.length) return;
            let idx = current + dir;
            if (idx < 0) idx = allImages.length - 1;
            if (idx >= allImages.length) idx = 0;
            openAt(idx);
        };
        ovClose?.addEventListener('click', closeOverlay);
        ovPrev?.addEventListener('click', ()=>nav(-1));
        ovNext?.addEventListener('click', ()=>nav(1));
        ovZoomIn?.addEventListener('click', ()=>{ scale = Math.min(4, scale + 0.25); applyZoom(); });
        ovZoomOut?.addEventListener('click', ()=>{ scale = Math.max(0.5, scale - 0.25); applyZoom(); });
        document.addEventListener('keydown', (e)=>{
            if (overlay.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeOverlay();
            if (e.key === 'ArrowLeft') nav(-1);
            if (e.key === 'ArrowRight') nav(1);
            if (e.key === 'Tab') {
                const focusables = [ovClose, ovPrev, ovNext, ovZoomOut, ovZoomIn, ovDownload].filter(Boolean);
                const first = focusables[0], last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
        overlay.addEventListener('click', (e)=>{ if (e.target === overlay) closeOverlay(); });

        document.querySelectorAll('[data-gallery]').forEach((el)=>{
            const qid = Number(el.getAttribute('data-gallery'));
            const loading = document.createElement('div');
            loading.className = 'text-xs text-gray-500';
            loading.textContent = 'Carregando imagens...';
            el.appendChild(loading);
            fetch(`index.php?route=auditorias/api_list_anexos&auditoria_id=${auditoriaId}&questao_id=${qid}`)
                .then(r=>r.json())
                .then(json=>{
                    el.innerHTML = '';
                    const items = (json && Array.isArray(json.items)) ? json.items : [];
                    if (!items.length) {
                        const empty = document.createElement('div');
                        empty.className = 'text-xs text-gray-500';
                        empty.textContent = 'Sem anexos.';
                        el.appendChild(empty);
                        return;
                    }
                    items.forEach(it=>{
                        const name = it.name || ('arquivo_'+it.id);
                        const download = `index.php?route=auditorias/download_anexo&id=${it.id}`;
                        const full = `index.php?route=auditorias/view_anexo&id=${it.id}`;
                        const card = document.createElement('button');
                        card.type = 'button';
                        card.className = 'border rounded p-2 text-xs block text-left hover:bg-gray-50 w-full';
                        const mime = String(it.mime || '').toLowerCase();
                        const isImage = allowedImage(name) || mime.startsWith('image/');
                        const isPdf = allowedPdf(name) || mime === 'application/pdf';
                        if (isImage) {
                            const img = document.createElement('img');
                            img.loading = 'lazy';
                            img.src = it.has_thumb ? `index.php?route=auditorias/thumb_anexo&id=${it.id}` : full;
                            img.className = 'w-full h-24 md:h-28 object-cover rounded mb-1 bg-gray-100';
                            img.alt = name;
                            img.onerror = ()=>{ img.src = full; };
                            card.appendChild(img);
                            const index = allImages.length;
                            allImages.push({ name, full, download, type: 'image' });
                            card.addEventListener('click', ()=>openAt(index));
                        } else if (isPdf) {
                            const pdfThumb = document.createElement('div');
                            pdfThumb.className = 'w-full h-24 md:h-28 rounded mb-1 bg-gray-100 flex items-center justify-center text-sm text-gray-600';
                            pdfThumb.textContent = 'PDF';
                            card.appendChild(pdfThumb);
                            const index = allImages.length;
                            allImages.push({ name, full, download, type: 'pdf' });
                            card.addEventListener('click', ()=>openAt(index));
                        } else {
                            const fileThumb = document.createElement('div');
                            fileThumb.className = 'w-full h-24 md:h-28 rounded mb-1 bg-gray-100 flex items-center justify-center text-sm text-gray-600';
                            fileThumb.textContent = 'ARQUIVO';
                            card.appendChild(fileThumb);
                            const index = allImages.length;
                            allImages.push({ name, full, download, type: 'other' });
                            card.addEventListener('click', ()=>openAt(index));
                        }
                        const t = document.createElement('div');
                        t.textContent = name + (it.size ? ` (${Math.round(it.size/1024)} KB)` : '');
                        card.appendChild(t);
                        el.appendChild(card);
                    });
                })
                .catch(()=>{
                    el.innerHTML = '';
                    const err = document.createElement('div');
                    err.className = 'text-xs text-red-600';
                    err.textContent = 'Erro ao carregar anexos.';
                    el.appendChild(err);
                });
        });
    })();
</script>
<?php endif; ?>
