<?php /** @var array|null $item */ /** @var array $clientesAssociacao */ /** @var array|null $publicLinkData */ /** @var string $publicLinkUrl */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Avaliação</h1>
    <div class="flex items-center gap-2">
      <?php if ($item): ?>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="<?= htmlspecialchars($publicLinkUrl) ?>" target="_blank" rel="noopener">Abrir Formulário Público</a>
        <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=avaliacoes/relatorio_pdf&id=<?= (int)$item['id'] ?>" target="_blank" rel="noopener">Exportar PDF</a>
        <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=avaliacoes/relatorio_pdf&id=<?= (int)$item['id'] ?>&download=1">Baixar PDF</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=avaliacoes/planoacao&id=<?= (int)$item['id'] ?>">Plano de Ação</a>
      <?php endif; ?>
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>
  <?php if (!$item): ?>
    <div class="bg-white shadow rounded p-4">Registro não encontrado.</div>
  <?php else:
    $labels = ['Financeiro' => (int)$item['nota_financeiro'], 'Mercado' => (int)$item['nota_mercado'], 'Pessoas' => (int)$item['nota_pessoas'], 'Processo' => (int)$item['nota_processo']];
    $max = 7;
    $total = array_sum($labels);
    $pct = fn($n) => round(($n / $max) * 100);
    $empresa = $item['cliente_id'] ? ($item['cliente_nome'] ?? '') : ($item['empresa_nome'] ?? '');
    $isPotencial = (int)($item['cliente_id'] ?? 0) <= 0;
  ?>
  <?php if (!empty($publicLinkUrl)): ?>
    <div class="bg-white shadow rounded p-4 mb-4">
      <div class="font-semibold mb-2">Formulário público fixo</div>
      <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-center">
        <input class="border rounded p-2 w-full bg-gray-50" value="<?= htmlspecialchars($publicLinkUrl) ?>" readonly />
        <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown text-center" href="<?= htmlspecialchars($publicLinkUrl) ?>" target="_blank" rel="noopener">Abrir formulário</a>
      </div>
      <div class="text-xs text-gray-600 mt-2">Endpoint permanente, sem token e sem expiração.</div>
    </div>
  <?php endif; ?>
  <div class="bg-white shadow rounded p-4 mb-4">
    <div class="mb-2 text-sm text-gray-600">Empresa</div>
    <div class="font-semibold"><?= htmlspecialchars($empresa ?: '—') ?></div>
    <div class="text-sm text-gray-600 mt-1">Data: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at']))) ?></div>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
      <?php if ($isPotencial): ?>
        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800">Potencial cliente</span>
      <?php else: ?>
        <span class="px-2 py-1 rounded bg-green-100 text-green-800">Cliente associado</span>
      <?php endif; ?>
      <?php if (!empty($item['cliente_associado_em'])): ?>
        <span class="px-2 py-1 rounded bg-gray-100 text-gray-700">Associado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['cliente_associado_em']))) ?></span>
      <?php endif; ?>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4 text-sm">
      <div><span class="text-gray-600">Nome:</span> <span class="font-medium"><?= htmlspecialchars($item['nome'] ?? ($item['contato'] ?? '—')) ?></span></div>
      <?php if (!empty($item['cliente_id']) && !empty($item['empresa_nome'])): ?>
        <div><span class="text-gray-600">Empresa informada no cadastro:</span> <span class="font-medium"><?= htmlspecialchars((string)$item['empresa_nome']) ?></span></div>
      <?php endif; ?>
      <div><span class="text-gray-600">WhatsApp:</span> <span class="font-medium"><?= htmlspecialchars($item['whatsapp'] ?? '—') ?></span></div>
      <div><span class="text-gray-600">E-mail:</span> <span class="font-medium"><?= htmlspecialchars($item['email'] ?? '—') ?></span></div>
      <div><span class="text-gray-600">Funcionários:</span> <span class="font-medium"><?= isset($item['numero_funcionarios']) ? (int)$item['numero_funcionarios'] : '—' ?></span></div>
      <div><span class="text-gray-600">Líderes:</span> <span class="font-medium"><?= isset($item['numero_lideres']) ? (int)$item['numero_lideres'] : '—' ?></span></div>
      <div><span class="text-gray-600">Faturamento anual:</span> <span class="font-medium">R$ <?= isset($item['faturamento_medio_anual']) ? number_format((float)$item['faturamento_medio_anual'], 0, ',', '.') : '—' ?></span></div>
      <div><span class="text-gray-600">Tomador de decisão:</span> <span class="font-medium"><?= isset($item['tomador_decisao']) ? ((int)$item['tomador_decisao'] === 1 ? 'Sim' : 'Não') : '—' ?></span></div>
    </div>
  </div>
  <?php if ($isPotencial): ?>
    <div class="bg-white shadow rounded p-4 mb-4">
      <div class="font-semibold mb-2">Associar a um cliente efetivo</div>
      <p class="text-sm text-gray-600 mb-3">Quando este potencial cliente se tornar efetivo, associe a avaliação a um cadastro existente sem perder o histórico já registrado.</p>
      <form method="post" action="index.php?route=avaliacoes/associar-cliente" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="avaliacao_id" value="<?= (int)$item['id'] ?>" />
        <div>
          <label class="block text-sm mb-1">Cliente para associação</label>
          <select name="cliente_id" class="border rounded p-2 w-full" required>
            <option value="">Selecione um cliente</option>
            <?php foreach (($clientesAssociacao ?? []) as $cliente): ?>
              <option value="<?= (int)$cliente['id'] ?>"><?= htmlspecialchars((string)$cliente['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Associar cliente</button>
      </form>
    </div>
  <?php endif; ?>
  <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
    <?php foreach ($labels as $name => $score): ?>
      <div class="bg-white shadow rounded p-4">
        <div class="font-semibold mb-2"><?= $name ?></div>
        <div class="w-full h-3 bg-gray-200 rounded mb-1">
          <div class="h-3 bg-brand-red rounded" style="width: <?= $pct($score) ?>%"></div>
        </div>
        <div class="text-xs text-gray-600 text-right"><?= (int)$score ?>/7</div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php
    $reals = [];
    foreach (['realidade_financeiro','realidade_mercado','realidade_pessoas','realidade_processo'] as $rk) {
      if (isset($item[$rk]) && $item[$rk] !== null) { $reals[] = (int)$item[$rk]; }
    }
    $realPct = $reals ? round(array_sum($reals) / count($reals)) : round(($total / 28) * 100);
  ?>
  <div class="bg-white shadow rounded p-4 mt-4">
    <div class="font-semibold mb-2">Nota total dos 4 pilares</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
      <div>
        <div class="text-xs text-gray-600 mb-1">Mundo ideal (100%)</div>
        <div class="border rounded p-2 text-center">28</div>
      </div>
      <div>
        <div class="text-xs text-gray-600 mb-1">Resultado (total)</div>
        <div class="border rounded p-2 text-center"><?= (int)$total ?></div>
      </div>
      <div>
        <div class="text-xs text-gray-600 mb-1">Realidade do cliente (%)</div>
        <div class="border rounded p-2 text-center"><?= (int)$realPct ?>%</div>
      </div>
    </div>
    <div class="w-full h-3 bg-gray-200 rounded">
      <div class="h-3 bg-brand-brown rounded" style="width: <?= round(($total / 28) * 100) ?>%"></div>
    </div>
  </div>
  <?php
    $qs = \App\Core\AvaliacaoQuestionario::pilares();
    $resp = json_decode($item['respostas_json'] ?? '{}', true) ?: [];
  ?>
  <div class="bg-white shadow rounded p-4 mt-4">
    <div class="font-semibold mb-3">Itens pontuados e não pontuados</div>
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <?php foreach (['financeiro'=>'Financeiro','mercado'=>'Mercado','pessoas'=>'Pessoas','processo'=>'Processo'] as $key=>$title): ?>
        <div>
          <div class="text-sm font-semibold mb-2"><?= $title ?></div>
          <ul class="space-y-1 text-sm">
            <?php
              $selected = array_map('intval', $resp[$key] ?? []);
              foreach ($qs[$key] as $idx => $q) {
                $on = in_array($idx+1, $selected, true);
                echo '<li>'.($on ? '<span class="font-semibold">'.$q.'</span>' : '<span class="text-brand-red">'.$q.'</span>').'</li>';
              }
            ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
