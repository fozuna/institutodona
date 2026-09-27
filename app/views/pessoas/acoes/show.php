<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $acao */
/** @var ?array $gap */
/** @var array $dev */
/** @var array $treinamentosDisponiveis */
/** @var array $links */
$acaoStatusLabels = PessoasGestaoConfig::acaoStatusLabels();
$acaoStatusClasses = PessoasGestaoConfig::acaoStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$csrf = \App\Core\Security::csrfToken();
$plano = $dev['plano'];
$treinamentos = $dev['treinamentos'];
$necessidades = $dev['necessidades'];
$ativa = in_array($acao['status'], ['pendente', 'em_andamento'], true);
$vinculados = array_map(static fn($t) => (int)$t['treinamento_id'], $treinamentos);
$opcoesTreinamento = array_values(array_filter($treinamentosDisponiveis, static fn($t) => !in_array((int)$t['id'], $vinculados, true)));
?>
<div class="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
  <?php $pessoasSubnavAtivo = 'acoes'; require __DIR__ . '/../_subnav.php'; ?>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black"><?= htmlspecialchars($acao['titulo']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($acao['colaborador_nome']) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($gap): ?><a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/gapShow&id=<?= (int)$gap['id'] ?>">Ver GAP</a><?php endif; ?>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$acao['colaborador_id'] ?>">Histórico do colaborador</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-xl p-4 md:p-6 space-y-2">
    <div class="flex flex-wrap items-center gap-2">
      <span class="px-2 py-1 rounded text-xs <?= $acaoStatusClasses[$acao['status']] ?? '' ?>"><?= htmlspecialchars($acaoStatusLabels[$acao['status']] ?? $acao['status']) ?></span>
      <span class="px-2 py-1 rounded text-xs bg-indigo-100 text-indigo-800"><?= htmlspecialchars($dev['estado']) ?></span>
    </div>
    <?php if (!empty($acao['descricao'])): ?><p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($acao['descricao'])) ?></p><?php endif; ?>
    <div class="text-xs text-gray-500">
      Responsável: <?= htmlspecialchars((string)($acao['responsavel_nome'] ?? 'Não definido')) ?>
      <?php if (!empty($acao['prazo'])): ?> · Prazo: <?= htmlspecialchars(DateHelper::formatDate((string)$acao['prazo'])) ?><?php endif; ?>
    </div>
    <p class="text-xs text-gray-400">Plano de Ação, Treinamento, esta Ação e o GAP têm ciclos independentes: nada é concluído automaticamente.</p>
  </div>

  <div class="bg-white shadow rounded-xl p-4 md:p-6 space-y-5">
    <h2 class="font-semibold">Desenvolvimento</h2>

    <!-- Plano de Ação -->
    <div>
      <div class="text-sm font-semibold text-brand-brown mb-1">Plano de Ação</div>
      <?php if ($plano): ?>
        <div class="border rounded p-3 text-sm flex flex-wrap items-center justify-between gap-2">
          <div>
            <div class="font-medium"><?= htmlspecialchars($plano['titulo']) ?></div>
            <div class="text-xs text-gray-500">Criado — <?= htmlspecialchars((string)$plano['plano_status']) ?><?= !empty($plano['plano_prazo']) ? ' · Prazo: ' . htmlspecialchars(DateHelper::formatDate((string)$plano['plano_prazo'])) : '' ?></div>
          </div>
          <?php if (!empty($links['plano'])): ?>
            <a class="text-brand-pink font-semibold text-sm" href="index.php?route=planoacao/show&id=<?= (int)$plano['plano_task_id'] ?>">Abrir Plano</a>
          <?php endif; ?>
        </div>
      <?php elseif ($ativa): ?>
        <details>
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Criar Plano de Ação</summary>
          <form method="post" action="index.php?route=pessoas/acaoEncaminharPlano" class="mt-2 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="acao_id" value="<?= (int)$acao['id'] ?>" />
            <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" value="<?= htmlspecialchars($acao['titulo']) ?>" required maxlength="255" />
            <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" maxlength="1500"><?= htmlspecialchars((string)($acao['descricao'] ?? '')) ?></textarea>
            <label class="block text-xs text-gray-600">Prazo</label>
            <input type="date" name="prazo" class="border rounded p-2 w-full text-sm" value="<?= htmlspecialchars((string)($acao['prazo'] ?? '')) ?>" />
            <p class="text-xs text-gray-500">O Plano é criado com o colaborador e a origem "Pilar de Pessoas"; o GAP não é copiado.</p>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar Plano de Ação</button>
          </form>
        </details>
      <?php else: ?>
        <div class="text-sm text-gray-500">Sem Plano de Ação.</div>
      <?php endif; ?>
    </div>

    <!-- Treinamentos -->
    <div>
      <div class="text-sm font-semibold text-brand-brown mb-1">Treinamento</div>
      <?php foreach ($treinamentos as $t): ?>
        <div class="border rounded p-3 text-sm flex flex-wrap items-center justify-between gap-2 mb-2">
          <div>
            <div class="font-medium"><?= htmlspecialchars($t['nome']) ?></div>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($t['situacao']['label']) ?><?= !empty($t['situacao']['certificado']) ? ' · Certificado emitido' : '' ?></div>
          </div>
          <?php if (!empty($links['treinamento'])): ?>
            <a class="text-brand-pink font-semibold text-sm" href="index.php?route=treinamentos/show&id=<?= (int)$t['treinamento_id'] ?>">Abrir Treinamento</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($ativa && !empty($opcoesTreinamento)): ?>
        <details>
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Encaminhar para Treinamento</summary>
          <form method="post" action="index.php?route=pessoas/acaoEncaminharTreinamento" class="mt-2 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="acao_id" value="<?= (int)$acao['id'] ?>" />
            <select name="treinamento_id" class="border rounded p-2 w-full text-sm" required>
              <option value="">Selecione um treinamento da empresa</option>
              <?php foreach ($opcoesTreinamento as $o): ?><option value="<?= (int)$o['id'] ?>"><?= htmlspecialchars($o['nome']) ?></option><?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500">O colaborador é incluído na lista do treinamento; sem agenda definida, fica "Aguardando agendamento".</p>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Vincular treinamento</button>
          </form>
        </details>
      <?php elseif (empty($treinamentos)): ?>
        <div class="text-sm text-gray-500">Nenhum treinamento vinculado.</div>
      <?php endif; ?>
    </div>

    <!-- Necessidades -->
    <div>
      <div class="text-sm font-semibold text-brand-brown mb-1">Necessidade de Treinamento</div>
      <?php foreach ($necessidades as $n): ?>
        <div class="border rounded p-3 text-sm mb-2">
          <div class="flex items-center justify-between gap-2">
            <span class="font-medium"><?= htmlspecialchars($n['titulo']) ?></span>
            <span class="px-2 py-0.5 rounded text-xs <?= ['pendente' => 'bg-amber-100 text-amber-800', 'atendida' => 'bg-green-100 text-green-700', 'cancelada' => 'bg-red-100 text-red-700'][$n['status']] ?? '' ?>"><?= htmlspecialchars(ucfirst($n['status'])) ?></span>
          </div>
          <?php if (!empty($n['descricao'])): ?><p class="text-gray-600 text-xs mt-1"><?= nl2br(htmlspecialchars($n['descricao'])) ?></p><?php endif; ?>
          <div class="text-xs text-gray-500 mt-1">Prioridade: <?= htmlspecialchars($prioridadeLabels[$n['prioridade']] ?? $n['prioridade']) ?><?= !empty($n['treinamento_nome']) ? ' · Atendida por: ' . htmlspecialchars($n['treinamento_nome']) : '' ?></div>
          <?php if ($n['status'] === 'pendente'): ?>
            <div class="flex flex-wrap gap-3 mt-2">
              <?php if (!empty($treinamentosDisponiveis)): ?>
              <details>
                <summary class="cursor-pointer text-xs text-green-700 font-semibold">Atender com treinamento</summary>
                <form method="post" action="index.php?route=pessoas/necessidadeAtender" class="mt-2 bg-gray-50 rounded p-2 w-64">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>" />
                  <select name="treinamento_id" class="border rounded p-1 w-full text-xs" required>
                    <option value="">Selecione</option>
                    <?php foreach ($treinamentosDisponiveis as $o): ?><option value="<?= (int)$o['id'] ?>"><?= htmlspecialchars($o['nome']) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" class="px-2 py-1 rounded bg-green-600 text-white text-xs w-full mt-1">Vincular e marcar atendida</button>
                </form>
              </details>
              <?php endif; ?>
              <form method="post" action="index.php?route=pessoas/necessidadeCancelar" onsubmit="return confirm('Cancelar esta necessidade?');">
                <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>" />
                <button type="submit" class="text-xs text-red-600 font-semibold">Cancelar</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($ativa): ?>
        <details>
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Registrar Necessidade de Treinamento</summary>
          <form method="post" action="index.php?route=pessoas/necessidadeCreate" class="mt-2 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="acao_id" value="<?= (int)$acao['id'] ?>" />
            <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Ex.: Comunicação assertiva" required maxlength="255" />
            <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição (opcional)" maxlength="2000"></textarea>
            <select name="prioridade" class="border rounded p-2 w-full text-sm">
              <option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option>
            </select>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Registrar necessidade</button>
          </form>
        </details>
      <?php elseif (empty($necessidades)): ?>
        <div class="text-sm text-gray-500">Nenhuma necessidade registrada.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
