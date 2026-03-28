<?php /** @var array $item */ /** @var array $respostas */ /** @var bool $canManage */ ?>
<div class="p-6 max-w-6xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($item['nome_auditoria'] ?? '') ?></h1>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($item['cliente_nome'] ?? '') ?> · <?= htmlspecialchars($item['setor_nome'] ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Voltar</a>
            <?php if (!empty($canManage) && (($item['status'] ?? '') !== 'Realizada')): ?>
                <a href="index.php?route=auditorias/auditar&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-brand-red text-white">Auditar</a>
            <?php endif; ?>
            <a href="index.php?route=auditorias/relatorio_pdf&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-brand-pink text-white" target="_blank">PDF</a>
        </div>
    </div>
    <div class="bg-white rounded shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div><span class="text-xs text-gray-500">Data agendada</span><div class="font-semibold"><?= htmlspecialchars(date('d/m/Y', strtotime((string)$item['data_auditoria']))) ?></div></div>
        <div><span class="text-xs text-gray-500">Status</span><div class="font-semibold"><?= htmlspecialchars($item['status'] ?? '') ?></div></div>
        <div><span class="text-xs text-gray-500">Realizada em</span><div class="font-semibold"><?= !empty($item['realizada_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$item['realizada_at']))) : '-' ?></div></div>
        <div><span class="text-xs text-gray-500">Total de questões</span><div class="font-semibold"><?= count($item['questoes'] ?? []) ?></div></div>
    </div>
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
            </div>
        <?php endforeach; ?>
    </div>
</div>
