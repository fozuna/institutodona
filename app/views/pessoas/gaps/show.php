<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $gap */
/** @var array $colaborador */
/** @var array $feedbacks */
/** @var array $acoes */
/** @var array $usuariosResponsaveis */
$statusLabels = PessoasGestaoConfig::gapStatusLabels();
$statusClasses = PessoasGestaoConfig::gapStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$feedbackTipoLabels = PessoasGestaoConfig::feedbackTipoLabels();
$feedbackTipoClasses = PessoasGestaoConfig::feedbackTipoClasses();
$acaoStatusLabels = PessoasGestaoConfig::acaoStatusLabels();
$acaoStatusClasses = PessoasGestaoConfig::acaoStatusClasses();
$csrf = \App\Core\Security::csrfToken();
?>
<div class="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
  <?php $pessoasSubnavAtivo = 'gaps'; require __DIR__ . '/../_subnav.php'; ?>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black"><?= htmlspecialchars($gap['titulo']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($colaborador['nome']) ?></p>
    </div>
    <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">Histórico do colaborador</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <!-- PDI (Sprint 04): nunca cria PDI automaticamente -->
  <div class="bg-white shadow rounded-xl p-4 text-sm">
    <?php if (!empty($pdiDoGap)): ?>
      <span class="font-medium">Incluído no PDI:</span>
      <a class="text-brand-pink hover:underline" href="index.php?route=pessoas/pdiShow&id=<?= (int)$pdiDoGap['id'] ?>"><?= htmlspecialchars($pdiDoGap['titulo']) ?></a>
    <?php else: ?>
      <div class="flex flex-wrap items-center gap-2">
        <?php if (!empty($pdisEditaveis)): ?>
          <form method="post" action="index.php?route=pessoas/pdiGapAdd" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="gap_id" value="<?= (int)$gap['id'] ?>" />
            <input type="hidden" name="return" value="gap" />
            <select name="pdi_id" class="border rounded p-2 text-sm">
              <?php foreach ($pdisEditaveis as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['titulo']) ?> (<?= htmlspecialchars(PessoasGestaoConfig::pdiStatusLabels()[$p['status']] ?? $p['status']) ?>)</option><?php endforeach; ?>
            </select>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm">Adicionar ao PDI</button>
          </form>
          <span class="text-gray-400">ou</span>
        <?php endif; ?>
        <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/pdiCreate&colaborador_id=<?= (int)$colaborador['id'] ?>&gap_id=<?= (int)$gap['id'] ?>">Criar PDI</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white shadow rounded-xl p-4 md:p-6 space-y-3">
    <div class="flex flex-wrap items-center gap-2">
      <span class="px-2 py-1 rounded text-xs <?= $statusClasses[$gap['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= htmlspecialchars($statusLabels[$gap['status']] ?? $gap['status']) ?></span>
      <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">Prioridade: <?= htmlspecialchars($prioridadeLabels[$gap['prioridade']] ?? $gap['prioridade']) ?></span>
      <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700"><?= $gap['origem'] === 'avaliacao' ? 'Originado de avaliação' : 'Registrado manualmente' ?></span>
    </div>

    <?php if (!empty($gap['descricao'])): ?>
      <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($gap['descricao'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($gap['pergunta_snapshot'])): ?>
      <div class="bg-gray-50 rounded p-3 text-sm">
        <div class="text-xs text-gray-500 mb-1"><?= htmlspecialchars((string)($gap['grupo_nome_snapshot'] ?? '')) ?> · Ciclo: <?= htmlspecialchars((string)($gap['ciclo_nome'] ?? '')) ?></div>
        <div class="font-medium">"<?= htmlspecialchars($gap['pergunta_snapshot']) ?>"</div>
        <div class="text-sm mt-1">Nota obtida: <strong><?= $gap['nota'] !== null ? (int)$gap['nota'] : '—' ?></strong> / 5</div>
      </div>
    <?php endif; ?>

    <div class="text-xs text-gray-500">Criado em <?= htmlspecialchars(DateHelper::formatDateTime((string)$gap['created_at'])) ?><?= !empty($gap['resolvido_em']) ? ' · Resolvido em ' . htmlspecialchars(DateHelper::formatDateTime((string)$gap['resolvido_em'])) : '' ?></div>

    <?php if ($gap['status'] !== 'resolvido'): ?>
      <div class="flex flex-wrap gap-2 pt-2 border-t">
        <?php if ($gap['status'] === 'aberto'): ?>
          <form method="post" action="index.php?route=pessoas/gapStatus">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="id" value="<?= (int)$gap['id'] ?>" />
            <input type="hidden" name="status" value="em_tratamento" />
            <button type="submit" class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Colocar em tratamento</button>
          </form>
        <?php endif; ?>
        <form method="post" action="index.php?route=pessoas/gapStatus" onsubmit="return confirm('Marcar este GAP como resolvido?');">
          <input type="hidden" name="csrf" value="<?= $csrf ?>" />
          <input type="hidden" name="id" value="<?= (int)$gap['id'] ?>" />
          <input type="hidden" name="status" value="resolvido" />
          <button type="submit" class="px-3 py-2 rounded bg-green-600 text-white text-sm">Marcar como resolvido</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Feedbacks relacionados</h2>
    <?php if (empty($feedbacks)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhum feedback relacionado a este GAP ainda.</div>
    <?php else: ?>
      <div class="space-y-2 mb-3">
        <?php foreach ($feedbacks as $f): ?>
          <div class="border rounded p-3 text-sm">
            <div class="flex items-center justify-between gap-2 mb-1">
              <span class="font-medium"><?= htmlspecialchars($f['titulo']) ?></span>
              <span class="px-2 py-0.5 rounded text-xs <?= $feedbackTipoClasses[$f['tipo']] ?? '' ?>"><?= htmlspecialchars($feedbackTipoLabels[$f['tipo']] ?? $f['tipo']) ?></span>
            </div>
            <p class="text-gray-600"><?= nl2br(htmlspecialchars($f['descricao'])) ?></p>
            <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(DateHelper::formatDate((string)$f['data_feedback'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details>
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Registrar Feedback</summary>
      <form method="post" action="index.php?route=pessoas/feedbackCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
        <input type="hidden" name="gap_id" value="<?= (int)$gap['id'] ?>" />
        <input type="hidden" name="voltar_para" value="index.php?route=pessoas/gapShow&id=<?= (int)$gap['id'] ?>" />
        <select name="tipo" class="border rounded p-2 w-full text-sm" required>
          <option value="melhoria">Feedback de Melhoria</option>
          <option value="positivo">Feedback Positivo</option>
        </select>
        <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
        <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" required maxlength="2000"></textarea>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Salvar Feedback</button>
      </form>
    </details>
  </div>

  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Ações de Melhoria</h2>
    <?php if (empty($acoes)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhuma ação de melhoria criada para este GAP ainda.</div>
    <?php else: ?>
      <div class="space-y-2 mb-3">
        <?php foreach ($acoes as $a): ?>
          <div class="border rounded p-3 text-sm">
            <div class="flex items-center justify-between gap-2 mb-1">
              <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/acaoShow&id=<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['titulo']) ?></a>
              <span class="px-2 py-0.5 rounded text-xs <?= $acaoStatusClasses[$a['status']] ?? '' ?>"><?= htmlspecialchars($acaoStatusLabels[$a['status']] ?? $a['status']) ?></span>
            </div>
            <?php if (!empty($a['descricao'])): ?><p class="text-gray-600 mb-1"><?= nl2br(htmlspecialchars($a['descricao'])) ?></p><?php endif; ?>
            <div class="text-xs text-gray-500">
              Responsável: <?= htmlspecialchars((string)($a['responsavel_nome'] ?? 'Não definido')) ?>
              <?php if (!empty($a['prazo'])): ?> · Prazo: <?= htmlspecialchars(DateHelper::formatDate((string)$a['prazo'])) ?><?php endif; ?>
              · <a class="text-brand-pink" href="index.php?route=pessoas/acaoShow&id=<?= (int)$a['id'] ?>">Desenvolvimento</a>
            </div>
            <?php if (!empty($a['conclusao'])): ?>
              <div class="mt-2 text-xs bg-green-50 border border-green-200 rounded p-2 text-green-800"><?= nl2br(htmlspecialchars($a['conclusao'])) ?></div>
            <?php endif; ?>
            <?php if (in_array($a['status'], ['pendente', 'em_andamento'], true)): ?>
              <div class="flex flex-wrap gap-2 mt-2">
                <?php if ($a['status'] === 'pendente'): ?>
                  <form method="post" action="index.php?route=pessoas/acaoEmAndamento">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                    <button type="submit" class="text-xs text-blue-700 font-semibold">Marcar em andamento</button>
                  </form>
                <?php endif; ?>
                <details>
                  <summary class="cursor-pointer text-xs text-green-700 font-semibold">Concluir</summary>
                  <form method="post" action="index.php?route=pessoas/acaoConcluir" class="mt-2 bg-gray-50 rounded p-2 w-64">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                    <textarea name="conclusao" class="border rounded p-1 w-full text-xs" rows="2" placeholder="Descreva o resultado (opcional)" maxlength="2000"></textarea>
                    <button type="submit" class="px-2 py-1 rounded bg-green-600 text-white text-xs w-full mt-1">Confirmar conclusão</button>
                  </form>
                </details>
                <form method="post" action="index.php?route=pessoas/acaoCancelar" onsubmit="return confirm('Cancelar esta ação?');">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                  <button type="submit" class="text-xs text-red-600 font-semibold">Cancelar</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details>
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Criar Ação de Melhoria</summary>
      <form method="post" action="index.php?route=pessoas/acaoCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
        <input type="hidden" name="gap_id" value="<?= (int)$gap['id'] ?>" />
        <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
        <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" maxlength="2000"></textarea>
        <?php if (!empty($usuariosResponsaveis)): ?>
          <select name="responsavel_usuario_id" class="border rounded p-2 w-full text-sm">
            <option value="">Sem responsável definido</option>
            <?php foreach ($usuariosResponsaveis as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Início</label>
            <input type="date" name="data_inicio" class="border rounded p-2 w-full text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Prazo</label>
            <input type="date" name="prazo" class="border rounded p-2 w-full text-sm" />
          </div>
        </div>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar ação</button>
      </form>
    </details>
  </div>
</div>
