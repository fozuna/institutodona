<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $pdi */ /** @var array $colaborador */ /** @var array $gapsDoPdi */ /** @var array $gapsDisponiveis */ /** @var array $objetivos */
/** @var array $feedbacks */ /** @var array $usuariosResponsaveis */ /** @var array $links */
$csrf = \App\Core\Security::csrfToken();
$pdiStatusLabels = PessoasGestaoConfig::pdiStatusLabels();
$pdiStatusClasses = PessoasGestaoConfig::pdiStatusClasses();
$objStatusLabels = PessoasGestaoConfig::objetivoStatusLabels();
$objStatusClasses = PessoasGestaoConfig::objetivoStatusClasses();
$gapStatusLabels = PessoasGestaoConfig::gapStatusLabels();
$acaoStatusLabels = PessoasGestaoConfig::acaoStatusLabels();
$acaoStatusClasses = PessoasGestaoConfig::acaoStatusClasses();
$editavel = in_array($pdi['status'], ['rascunho', 'ativo'], true);
$fmt = static fn($d) => !empty($d) ? DateHelper::formatDate((string)$d) : '—';
$postForm = static function (string $route, array $hidden, string $label, string $btnClass = 'bg-gray-200 text-brand-brown', string $confirm = '') use ($csrf): string {
    $h = '<form method="post" action="index.php?route=' . htmlspecialchars($route) . '" class="inline"' . ($confirm !== '' ? ' onsubmit="return confirm(\'' . htmlspecialchars($confirm, ENT_QUOTES) . '\')"' : '') . '><input type="hidden" name="csrf" value="' . htmlspecialchars($csrf) . '" />';
    foreach ($hidden as $k => $v) {
        $h .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string)$v) . '" />';
    }
    return $h . '<button type="submit" class="px-3 py-1.5 rounded text-xs ' . $btnClass . '">' . htmlspecialchars($label) . '</button></form>';
};
?>
<div class="p-4 md:p-6 space-y-6 max-w-5xl mx-auto">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black"><?= htmlspecialchars($pdi['titulo']) ?></h1>
      <p class="text-sm text-gray-600">
        <a class="text-brand-pink" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>"><?= htmlspecialchars($colaborador['nome']) ?></a>
        · <?= htmlspecialchars((string)$pdi['empresa_nome']) ?>
      </p>
    </div>
    <div class="flex items-center gap-2">
      <span class="px-3 py-1 rounded text-sm <?= $pdiStatusClasses[$pdi['status']] ?? '' ?>"><?= htmlspecialchars($pdiStatusLabels[$pdi['status']] ?? $pdi['status']) ?></span>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/pdiIndex">Voltar</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <!-- Cabeçalho -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6 space-y-3">
    <?php if (!empty($pdi['descricao'])): ?><p class="text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($pdi['descricao']) ?></p><?php endif; ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
      <div><div class="text-xs text-gray-500">Início</div><?= htmlspecialchars($fmt($pdi['data_inicio'])) ?></div>
      <div><div class="text-xs text-gray-500">Fim previsto</div><?= htmlspecialchars($fmt($pdi['data_fim_prevista'])) ?></div>
      <div><div class="text-xs text-gray-500">Concluído em</div><?= htmlspecialchars($fmt($pdi['concluido_em'])) ?></div>
      <div>
        <div class="text-xs text-gray-500">Progresso do PDI</div>
        <div class="font-semibold"><?= htmlspecialchars(PessoasGestaoConfig::formatProgresso((float)$pdi['progresso'])) ?></div>
        <div class="text-xs text-gray-500"><?= (int)$pdi['objetivos_concluidos'] ?> de <?= (int)$pdi['objetivos_validos'] ?> objetivo(s) concluído(s)</div>
      </div>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden"><div class="bg-brand-red h-2" style="width: <?= (int)round($pdi['progresso']) ?>%"></div></div>
    <p class="text-xs text-gray-500">O progresso mede objetivos concluídos (cancelados não contam) e não representa desempenho do colaborador.</p>
    <?php if (!empty($pdi['observacoes_conclusao'])): ?>
      <div class="text-sm"><span class="font-medium">Observações de conclusão:</span> <?= htmlspecialchars($pdi['observacoes_conclusao']) ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-2 pt-2 border-t">
      <?php if ($pdi['status'] === 'rascunho'): ?>
        <?= $postForm('pessoas/pdiAtivar', ['id' => $pdi['id']], 'Ativar PDI', 'bg-brand-red text-white') ?>
      <?php endif; ?>
      <?php if ($editavel): ?>
        <?= $postForm('pessoas/pdiCancelar', ['id' => $pdi['id']], 'Cancelar PDI', 'bg-red-100 text-red-700', 'Cancelar este PDI? GAPs, Ações, Planos e Treinamentos são preservados.') ?>
      <?php endif; ?>
    </div>

    <?php if ($pdi['status'] === 'ativo'): ?>
      <form method="post" action="index.php?route=pessoas/pdiConcluir" class="space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="id" value="<?= (int)$pdi['id'] ?>" />
        <label class="block text-xs text-gray-600">Observações de conclusão (obrigatório; todos os objetivos não cancelados precisam estar concluídos)</label>
        <textarea name="observacoes_conclusao" rows="2" maxlength="2000" required class="border rounded p-2 w-full text-sm"></textarea>
        <button type="submit" class="px-3 py-2 rounded bg-green-600 text-white text-sm">Concluir PDI</button>
      </form>
    <?php endif; ?>

    <?php if ($pdi['status'] === 'rascunho'): ?>
      <details>
        <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Editar dados do PDI</summary>
        <form method="post" action="index.php?route=pessoas/pdiUpdate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>" />
          <input type="hidden" name="id" value="<?= (int)$pdi['id'] ?>" />
          <input type="text" name="titulo" value="<?= htmlspecialchars($pdi['titulo']) ?>" required maxlength="255" class="border rounded p-2 w-full text-sm" />
          <textarea name="descricao" rows="2" maxlength="2000" class="border rounded p-2 w-full text-sm"><?= htmlspecialchars((string)$pdi['descricao']) ?></textarea>
          <div class="grid grid-cols-2 gap-2">
            <input type="date" name="data_inicio" value="<?= htmlspecialchars((string)$pdi['data_inicio']) ?>" class="border rounded p-2 text-sm" />
            <input type="date" name="data_fim_prevista" value="<?= htmlspecialchars((string)$pdi['data_fim_prevista']) ?>" class="border rounded p-2 text-sm" />
          </div>
          <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm">Salvar</button>
        </form>
      </details>
    <?php endif; ?>
  </div>

  <!-- GAPs relacionados -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">GAPs relacionados</h2>
    <?php if (empty($gapsDoPdi)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhum GAP vinculado.</div>
    <?php else: ?>
      <ul class="space-y-1 mb-3 text-sm">
        <?php foreach ($gapsDoPdi as $g): ?>
          <li class="flex items-center justify-between gap-2 border rounded p-2">
            <span><a class="text-brand-brown hover:underline" href="index.php?route=pessoas/gapShow&id=<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['titulo']) ?></a>
              <span class="px-2 py-0.5 rounded text-xs <?= PessoasGestaoConfig::gapStatusClasses()[$g['status']] ?? '' ?>"><?= htmlspecialchars($gapStatusLabels[$g['status']] ?? $g['status']) ?></span></span>
            <?php if ($pdi['status'] === 'rascunho'): ?>
              <?= $postForm('pessoas/pdiGapRemove', ['pdi_id' => $pdi['id'], 'gap_id' => $g['id']], 'Remover do PDI', 'bg-gray-100 text-gray-700') ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($editavel && !empty($gapsDisponiveis)): ?>
      <form method="post" action="index.php?route=pessoas/pdiGapAdd" class="flex flex-wrap gap-2 items-center">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="pdi_id" value="<?= (int)$pdi['id'] ?>" />
        <select name="gap_id" class="border rounded p-2 text-sm">
          <?php foreach ($gapsDisponiveis as $g): ?>
            <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['titulo']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm">Adicionar GAP</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Objetivos -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Objetivos</h2>
    <?php if (empty($objetivos)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhum objetivo cadastrado.</div>
    <?php endif; ?>
    <div class="space-y-3 mb-3">
      <?php foreach ($objetivos as $o): ?>
        <?php $vencido = PessoasGestaoConfig::isObjetivoVencido($o['prazo'] ?? null, $o['status']); ?>
        <div class="border rounded p-3 text-sm">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <span class="font-medium"><?= htmlspecialchars($o['titulo']) ?></span>
            <span>
              <?php if ($vencido): ?><span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Vencido</span><?php endif; ?>
              <span class="px-2 py-0.5 rounded text-xs <?= $objStatusClasses[$o['status']] ?? '' ?>"><?= htmlspecialchars($objStatusLabels[$o['status']] ?? $o['status']) ?></span>
            </span>
          </div>
          <?php if (!empty($o['descricao'])): ?><div class="text-gray-700 whitespace-pre-line"><?= htmlspecialchars($o['descricao']) ?></div><?php endif; ?>
          <div class="text-xs text-gray-500 mt-1">
            Prazo: <?= htmlspecialchars($fmt($o['prazo'])) ?>
            <?php if (!empty($o['criterio_sucesso'])): ?> · Critério de sucesso: <?= htmlspecialchars($o['criterio_sucesso']) ?><?php endif; ?>
            <?php if (!empty($o['concluido_em'])): ?> · Concluído em <?= htmlspecialchars(DateHelper::formatDate((string)$o['concluido_em'])) ?><?php endif; ?>
          </div>

          <?php if ($editavel && in_array($o['status'], ['pendente', 'em_andamento'], true)): ?>
            <div class="flex flex-wrap gap-2 mt-2">
              <?php if ($pdi['status'] === 'ativo'): ?>
                <?php if ($o['status'] === 'pendente'): ?>
                  <?= $postForm('pessoas/pdiObjetivoStatus', ['objetivo_id' => $o['id'], 'status' => 'em_andamento'], 'Iniciar', 'bg-amber-100 text-amber-800') ?>
                <?php endif; ?>
                <?= $postForm('pessoas/pdiObjetivoStatus', ['objetivo_id' => $o['id'], 'status' => 'concluido'], 'Concluir', 'bg-green-100 text-green-700') ?>
              <?php endif; ?>
              <?= $postForm('pessoas/pdiObjetivoStatus', ['objetivo_id' => $o['id'], 'status' => 'cancelado'], 'Cancelar objetivo', 'bg-red-100 text-red-700', 'Cancelar este objetivo?') ?>
            </div>
          <?php endif; ?>

          <!-- Ações do objetivo -->
          <div class="mt-3 pl-3 border-l-2 border-gray-200 space-y-2">
            <div class="text-xs font-semibold text-gray-600">Ações de Melhoria</div>
            <?php if (empty($o['acoes'])): ?><div class="text-xs text-gray-500">Nenhuma ação vinculada.</div><?php endif; ?>
            <?php foreach ($o['acoes'] as $a): ?>
              <?php $dev = $o['desenvolvimento'][(int)$a['id']] ?? null; ?>
              <div class="text-xs">
                <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/acaoShow&id=<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['titulo']) ?></a>
                <span class="px-2 py-0.5 rounded <?= $acaoStatusClasses[$a['status']] ?? '' ?>"><?= htmlspecialchars($acaoStatusLabels[$a['status']] ?? $a['status']) ?></span>
                · Prazo: <?= htmlspecialchars($fmt($a['prazo'])) ?>
                <?php if ($dev): ?>
                  <div class="text-gray-600">Desenvolvimento: <?= htmlspecialchars($dev['estado']) ?></div>
                  <?php if ($dev['plano']): ?>
                    <div>Plano de Ação: <?= htmlspecialchars($dev['plano']['titulo']) ?> — <?= htmlspecialchars((string)$dev['plano']['plano_status']) ?>
                      <?php if (!empty($links['plano'])): ?> · <a class="text-brand-pink" href="index.php?route=planoacao/show&id=<?= (int)$dev['plano']['plano_task_id'] ?>">Abrir Plano</a><?php endif; ?></div>
                  <?php endif; ?>
                  <?php foreach ($dev['treinamentos'] as $t): ?>
                    <div>Treinamento: <?= htmlspecialchars($t['nome']) ?> — <?= htmlspecialchars($t['situacao']['label']) ?>
                      <?php if (!empty($links['treinamento'])): ?> · <a class="text-brand-pink" href="index.php?route=treinamentos/show&id=<?= (int)$t['treinamento_id'] ?>">Abrir</a><?php endif; ?></div>
                  <?php endforeach; ?>
                  <?php foreach ($dev['necessidades'] as $n): ?>
                    <div>Necessidade de treinamento: <?= htmlspecialchars($n['titulo']) ?> — <?= htmlspecialchars(ucfirst($n['status'])) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <?php if ($editavel && $o['status'] !== 'cancelado'): ?>
              <?php if (!empty($o['disponiveis'])): ?>
                <form method="post" action="index.php?route=pessoas/pdiAcaoVincular" class="flex flex-wrap gap-2 items-center">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                  <input type="hidden" name="objetivo_id" value="<?= (int)$o['id'] ?>" />
                  <select name="acao_id" class="border rounded p-1.5 text-xs">
                    <?php foreach ($o['disponiveis'] as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['titulo']) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" class="px-3 py-1.5 rounded bg-gray-200 text-brand-brown text-xs">Vincular ação existente</button>
                </form>
              <?php endif; ?>
              <details>
                <summary class="cursor-pointer text-xs text-brand-pink font-semibold">Criar nova ação para este objetivo</summary>
                <form method="post" action="index.php?route=pessoas/pdiAcaoCriar" class="mt-2 space-y-2 bg-gray-50 rounded p-3">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                  <input type="hidden" name="objetivo_id" value="<?= (int)$o['id'] ?>" />
                  <input type="text" name="titulo" required maxlength="255" placeholder="Título da ação" class="border rounded p-2 w-full text-sm" />
                  <textarea name="descricao" rows="2" maxlength="2000" placeholder="Descrição" class="border rounded p-2 w-full text-sm"></textarea>
                  <?php if (!empty($gapsDoPdi)): ?>
                    <select name="gap_id" class="border rounded p-2 w-full text-sm">
                      <option value="">Sem GAP relacionado</option>
                      <?php foreach ($gapsDoPdi as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['titulo']) ?></option><?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                  <?php if (!empty($usuariosResponsaveis)): ?>
                    <select name="responsavel_usuario_id" class="border rounded p-2 w-full text-sm">
                      <option value="">Sem responsável definido</option>
                      <?php foreach ($usuariosResponsaveis as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option><?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                  <input type="date" name="prazo" value="<?= htmlspecialchars((string)($o['prazo'] ?? '')) ?>" class="border rounded p-2 text-sm" />
                  <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar ação</button>
                </form>
              </details>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($editavel): ?>
      <details>
        <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Novo objetivo</summary>
        <form method="post" action="index.php?route=pessoas/pdiObjetivoCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
          <input type="hidden" name="csrf" value="<?= $csrf ?>" />
          <input type="hidden" name="pdi_id" value="<?= (int)$pdi['id'] ?>" />
          <input type="text" name="titulo" required maxlength="255" placeholder="Título do objetivo" class="border rounded p-2 w-full text-sm" />
          <textarea name="descricao" rows="2" maxlength="2000" placeholder="Descrição" class="border rounded p-2 w-full text-sm"></textarea>
          <input type="text" name="criterio_sucesso" maxlength="1000" placeholder="Critério de sucesso" class="border rounded p-2 w-full text-sm" />
          <div>
            <label class="block text-xs text-gray-600 mb-1">Prazo</label>
            <input type="date" name="prazo" class="border rounded p-2 text-sm" />
          </div>
          <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar objetivo</button>
        </form>
      </details>
    <?php endif; ?>
  </div>

  <!-- Feedbacks do período -->
  <?php if (!empty($feedbacks)): ?>
    <div class="bg-white shadow rounded-xl p-4 md:p-6">
      <h2 class="font-semibold mb-3">Feedbacks no período do PDI</h2>
      <ul class="space-y-1 text-sm">
        <?php foreach ($feedbacks as $f): ?>
          <li><?= htmlspecialchars(DateHelper::formatDate((string)$f['data_feedback'])) ?> — <?= htmlspecialchars(PessoasGestaoConfig::feedbackTipoLabels()[$f['tipo']] ?? $f['tipo']) ?>: <?= htmlspecialchars($f['titulo']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
