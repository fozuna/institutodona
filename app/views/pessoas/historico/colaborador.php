<?php use App\Core\DateHelper; use App\Core\PessoasAvaliacaoScale; use App\Core\PessoasGestaoConfig; ?>
<?php
/**
 * Central do Colaborador (Sprint 05) - evolução da antiga tela de Histórico.
 * Somente fatos derivados das fontes originais: nenhum score, nenhuma
 * classificação da pessoa.
 *
 * @var array $colaborador @var array $perfil @var array $resumo @var ?array $ultimaAvaliacao @var ?array $pdiAtivo
 * @var array $treinamentosColaborador @var array $pontosAtencao @var array $timeline
 * @var array $avaliacoes @var array $gaps @var array $gapsPorId @var array $feedbacks @var array $acoes
 * @var array $desenvolvimento @var array $usuariosResponsaveis @var array $links @var array $pdis
 */
$gapStatusLabels = PessoasGestaoConfig::gapStatusLabels();
$gapStatusClasses = PessoasGestaoConfig::gapStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$feedbackTipoLabels = PessoasGestaoConfig::feedbackTipoLabels();
$feedbackTipoClasses = PessoasGestaoConfig::feedbackTipoClasses();
$acaoStatusLabels = PessoasGestaoConfig::acaoStatusLabels();
$acaoStatusClasses = PessoasGestaoConfig::acaoStatusClasses();
$necStatusLabels = PessoasGestaoConfig::necessidadeStatusLabels();
$csrf = \App\Core\Security::csrfToken();
$cid = (int)$colaborador['id'];
$pessoasSubnavAtivo = 'colaboradores';
$ativo = !array_key_exists('ativo', $colaborador) || (int)$colaborador['ativo'] === 1;
$listaUrl = static fn(string $rota, array $extra): string => 'index.php?' . http_build_query(array_merge(['route' => $rota, 'colaborador_id' => $cid], $extra));
$treinConcluidos = count(array_filter($treinamentosColaborador, static fn(array $t): bool => $t['status'] === 'concluido'));
$orgPartes = array_values(array_filter([
    $perfil['empresa_nome'] ?? null, $perfil['departamento_nome'] ?? null, $perfil['setor_nome'] ?? null, $perfil['funcao_nome'] ?? null,
], static fn($v): bool => $v !== null && $v !== ''));

// Situação atual: somente fatos objetivos.
$situacao = [];
if ($pdiAtivo) { $situacao[] = 'PDI ativo — ' . number_format((float)$pdiAtivo['progresso'], 0, ',', '.') . '%'; }
if ($resumo['gaps_abertos'] + $resumo['gaps_em_tratamento'] > 0) { $situacao[] = ($resumo['gaps_abertos'] + $resumo['gaps_em_tratamento']) . ' GAP(s) em aberto'; }
if ($resumo['acoes_vencidas'] > 0) { $situacao[] = $resumo['acoes_vencidas'] . ' ação(ões) atrasada(s)'; }
if ($ultimaAvaliacao) { $situacao[] = 'Avaliação concluída em ' . DateHelper::formatDate((string)$ultimaAvaliacao['finalizado_em']); }
if ($resumo['necessidades_pendentes'] > 0) { $situacao[] = $resumo['necessidades_pendentes'] . ' necessidade(s) de treinamento pendente(s)'; }
$tipoTimeline = ['avaliacao' => 'Avaliação', 'gap' => 'GAP', 'feedback' => 'Feedback', 'acao' => 'Ação', 'encaminhamento' => 'Encaminhamento', 'necessidade' => 'Necessidade', 'pdi' => 'PDI'];
?>
<div class="p-4 md:p-6 space-y-5 max-w-6xl mx-auto">
  <?php require __DIR__ . '/../_subnav.php'; ?>

  <!-- Cabeçalho -->
  <div class="bg-white shadow rounded-xl p-4 md:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs uppercase tracking-wide text-gray-500">Central do Colaborador</p>
        <h1 class="text-xl md:text-2xl font-bold text-brand-black flex flex-wrap items-center gap-2">
          <?= htmlspecialchars($colaborador['nome']) ?>
          <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $ativo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
          <?php if (!empty($colaborador['status_atual']) && $colaborador['status_atual'] !== 'ativo'): ?>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?= htmlspecialchars(ucfirst((string)$colaborador['status_atual'])) ?></span>
          <?php endif; ?>
        </h1>
        <p class="text-sm text-gray-600"><?= htmlspecialchars(implode(' · ', $orgPartes)) ?></p>
        <?php if (!empty($colaborador['matricula']) || !empty($colaborador['data_admissao'])): ?>
          <p class="text-xs text-gray-500">
            <?= !empty($colaborador['matricula']) ? 'Matrícula ' . htmlspecialchars((string)$colaborador['matricula']) : '' ?>
            <?= !empty($colaborador['matricula']) && !empty($colaborador['data_admissao']) ? ' · ' : '' ?>
            <?= !empty($colaborador['data_admissao']) ? 'Admissão ' . htmlspecialchars(DateHelper::formatDate((string)$colaborador['data_admissao'])) : '' ?>
          </p>
        <?php endif; ?>
      </div>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=colaboradores/index">Voltar a Colaboradores</a>
    </div>

    <?php if (!empty($situacao)): ?>
      <ul class="mt-3 flex flex-wrap gap-2 text-xs" aria-label="Situação atual">
        <?php foreach ($situacao as $fato): ?>
          <li class="px-2 py-1 rounded bg-gray-100 text-gray-700"><?= htmlspecialchars($fato) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- Atalhos contextuais -->
    <div class="mt-4 flex flex-wrap gap-2 text-sm" aria-label="Atalhos">
      <a class="px-3 py-2 rounded bg-brand-red text-white" href="#novo-feedback" data-abrir="novo-feedback">Registrar feedback</a>
      <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="#novo-gap" data-abrir="novo-gap">Registrar GAP</a>
      <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="#nova-acao" data-abrir="nova-acao">Criar ação</a>
      <?php if ($pdiAtivo && !empty($links['pdi'])): ?>
        <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="index.php?route=pessoas/pdiShow&id=<?= (int)$pdiAtivo['id'] ?>">Abrir PDI ativo</a>
      <?php elseif (!$pdiAtivo && !empty($links['pdi_criar'])): ?>
        <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="index.php?route=pessoas/pdiCreate&colaborador_id=<?= $cid ?>">Criar PDI</a>
      <?php endif; ?>
      <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="#avaliacoes">Ver avaliações</a>
      <?php if (!empty($acoes)): ?>
        <a class="px-3 py-2 rounded bg-gray-100 text-brand-brown" href="#acoes">Encaminhar desenvolvimento</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <!-- Resumo do desenvolvimento -->
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-xs text-gray-500">Última avaliação</div>
      <?php if ($ultimaAvaliacao): ?>
        <div class="text-lg font-bold text-brand-black"><?= $ultimaAvaliacao['resultado'] !== null ? number_format((float)$ultimaAvaliacao['resultado'], 2, ',', '.') . ' / 5,00' : '—' ?></div>
        <div class="text-xs text-gray-600"><?= $ultimaAvaliacao['resultado'] !== null ? htmlspecialchars(PessoasAvaliacaoScale::classification((float)$ultimaAvaliacao['resultado'])) . ' · ' : '' ?><?= htmlspecialchars(DateHelper::formatDate((string)$ultimaAvaliacao['finalizado_em'])) ?></div>
      <?php else: ?>
        <div class="text-sm text-gray-600 mt-1">Nenhuma avaliação concluída</div>
      <?php endif; ?>
      <?php if ($resumo['avaliacoes_pendentes'] + $resumo['avaliacoes_em_andamento'] > 0): ?>
        <div class="text-xs text-amber-800 mt-1"><?= $resumo['avaliacoes_pendentes'] + $resumo['avaliacoes_em_andamento'] ?> pendente(s)/em andamento</div>
      <?php endif; ?>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-xs text-gray-500">GAPs</div>
      <div class="text-lg font-bold text-brand-black"><?= $resumo['gaps_abertos'] + $resumo['gaps_em_tratamento'] ?> <span class="text-sm font-normal text-gray-600">em aberto</span></div>
      <div class="text-xs text-gray-600"><?= $resumo['gaps_abertos'] ?> aberto(s) · <?= $resumo['gaps_em_tratamento'] ?> em tratamento</div>
      <?php if ($resumo['gaps_abertos_sem_acao'] > 0): ?>
        <div class="text-xs mt-1"><?php if (!empty($links['gaps'])): ?><a class="text-red-700 underline" href="<?= htmlspecialchars($listaUrl('pessoas/gaps', ['status' => 'aberto', 'tratamento' => 'sem_acao'])) ?>"><?= $resumo['gaps_abertos_sem_acao'] ?> sem ação</a><?php else: ?><span class="text-red-700"><?= $resumo['gaps_abertos_sem_acao'] ?> sem ação</span><?php endif; ?></div>
      <?php endif; ?>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-xs text-gray-500">Ações de melhoria</div>
      <div class="text-lg font-bold text-brand-black"><?= $resumo['acoes_pendentes'] + $resumo['acoes_em_andamento'] ?> <span class="text-sm font-normal text-gray-600">ativa(s)</span></div>
      <div class="text-xs text-gray-600"><?= $resumo['acoes_pendentes'] ?> pendente(s) · <?= $resumo['acoes_em_andamento'] ?> em andamento</div>
      <?php if ($resumo['acoes_vencidas'] > 0): ?>
        <div class="text-xs mt-1"><?php if (!empty($links['acoes'])): ?><a class="text-red-700 underline" href="<?= htmlspecialchars($listaUrl('pessoas/acoes', ['atrasadas' => 1])) ?>"><?= $resumo['acoes_vencidas'] ?> atrasada(s)</a><?php else: ?><span class="text-red-700"><?= $resumo['acoes_vencidas'] ?> atrasada(s)</span><?php endif; ?></div>
      <?php endif; ?>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-xs text-gray-500">PDI</div>
      <?php if ($pdiAtivo): ?>
        <div class="text-lg font-bold text-brand-black"><?= htmlspecialchars(PessoasGestaoConfig::formatProgresso((float)$pdiAtivo['progresso'])) ?></div>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mt-1" role="img" aria-label="Progresso do PDI"><div class="bg-brand-red h-2" style="width: <?= (int)round((float)$pdiAtivo['progresso']) ?>%"></div></div>
        <div class="text-xs text-gray-600 mt-1">Ativo · <?= (int)$pdiAtivo['objetivos_concluidos'] ?>/<?= (int)$pdiAtivo['objetivos_validos'] ?> objetivo(s)</div>
      <?php else: ?>
        <div class="text-sm text-gray-600 mt-1">Sem PDI ativo</div>
      <?php endif; ?>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-xs text-gray-500">Treinamentos</div>
      <div class="text-lg font-bold text-brand-black"><?= count($treinamentosColaborador) ?> <span class="text-sm font-normal text-gray-600">vinculado(s)</span></div>
      <div class="text-xs text-gray-600"><?= $treinConcluidos ?> concluído(s)</div>
      <?php if ($resumo['necessidades_pendentes'] > 0): ?>
        <div class="text-xs mt-1"><?php if (!empty($links['necessidades'])): ?><a class="text-amber-800 underline" href="<?= htmlspecialchars($listaUrl('pessoas/necessidades', ['status' => 'pendente'])) ?>"><?= $resumo['necessidades_pendentes'] ?> necessidade(s) pendente(s)</a><?php else: ?><span class="text-amber-800"><?= $resumo['necessidades_pendentes'] ?> necessidade(s) pendente(s)</span><?php endif; ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pontos de atenção -->
  <section class="bg-white shadow rounded-xl p-4" aria-labelledby="ptsAtencao">
    <h2 id="ptsAtencao" class="font-semibold mb-2">Pontos de atenção</h2>
    <?php if (empty($pontosAtencao)): ?>
      <p class="text-sm text-gray-600">Nenhuma pendência de desenvolvimento identificada no momento.</p>
    <?php else: ?>
      <ul class="space-y-1 text-sm">
        <?php foreach ($pontosAtencao as $pt): ?>
          <?php $rotaPt = (string)(parse_url($pt['href'], PHP_URL_QUERY) ?? ''); parse_str($rotaPt, $qPt); $permitido = empty($qPt['route']) || \App\Core\AccessControl::canAccessRoute((string)$qPt['route'], 'GET', $_SESSION['user'] ?? null); ?>
          <li class="flex items-start gap-2"><span aria-hidden="true" class="text-amber-600">•</span>
            <?php if ($permitido): ?><a class="text-brand-brown hover:underline" href="<?= htmlspecialchars($pt['href']) ?>"><?= htmlspecialchars($pt['texto']) ?></a><?php else: ?><?= htmlspecialchars($pt['texto']) ?><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <!-- PDI -->
      <section class="bg-white shadow rounded-xl p-4 md:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
          <h2 class="font-semibold">PDI — Plano de Desenvolvimento Individual</h2>
          <?php if (!empty($links['pdi_criar'])): ?>
            <a class="px-3 py-2 rounded bg-brand-red text-white text-sm" href="index.php?route=pessoas/pdiCreate&colaborador_id=<?= $cid ?>">Criar PDI</a>
          <?php endif; ?>
        </div>
        <?php if (empty($pdis)): ?>
          <div class="text-sm text-gray-500">Nenhum PDI criado ainda.</div>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($pdis as $p): ?>
              <div class="border rounded p-3 text-sm flex flex-wrap items-center justify-between gap-2">
                <div>
                  <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/pdiShow&id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['titulo']) ?></a>
                  <div class="text-xs text-gray-500">
                    <?= !empty($p['data_inicio']) ? htmlspecialchars(DateHelper::formatDate((string)$p['data_inicio'])) : '—' ?> a <?= !empty($p['data_fim_prevista']) ? htmlspecialchars(DateHelper::formatDate((string)$p['data_fim_prevista'])) : '—' ?>
                    · Progresso do PDI: <?= htmlspecialchars(PessoasGestaoConfig::formatProgresso((float)$p['progresso'])) ?> (<?= (int)$p['objetivos_concluidos'] ?>/<?= (int)$p['objetivos_validos'] ?>)
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded text-xs <?= PessoasGestaoConfig::pdiStatusClasses()[$p['status']] ?? '' ?>"><?= htmlspecialchars(PessoasGestaoConfig::pdiStatusLabels()[$p['status']] ?? $p['status']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- Avaliações -->
      <section id="avaliacoes" class="bg-white shadow rounded-xl p-4 md:p-5 scroll-mt-4">
        <h2 class="font-semibold mb-3">Avaliações</h2>
        <?php if (empty($avaliacoes)): ?>
          <div class="text-sm text-gray-500">Nenhuma avaliação registrada ainda.</div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead><tr class="text-left border-b bg-gray-50 text-xs text-gray-600"><th class="p-2">Ciclo</th><th class="p-2">Modelo</th><th class="p-2">Status</th><th class="p-2">Resultado</th><th class="p-2">Data</th><th class="p-2"></th></tr></thead>
              <tbody>
                <?php foreach ($avaliacoes as $a): ?>
                  <tr class="border-b">
                    <td class="p-2"><?= htmlspecialchars($a['ciclo_nome']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($a['modelo_nome']) ?></td>
                    <td class="p-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$a['status']))) ?></td>
                    <td class="p-2"><?php if ($a['resultado'] !== null): ?><?= number_format((float)$a['resultado'], 2, ',', '.') ?> / 5,00 — <?= htmlspecialchars(PessoasAvaliacaoScale::classification((float)$a['resultado'])) ?><?php else: ?>—<?php endif; ?></td>
                    <td class="p-2"><?= !empty($a['finalizado_em']) ? htmlspecialchars(DateHelper::formatDate((string)$a['finalizado_em'])) : '—' ?></td>
                    <td class="p-2 text-right"><?php if ($a['status'] === 'finalizada'): ?><a class="text-brand-pink font-semibold" href="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$a['id'] ?>">Ver resultado</a><?php endif; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- GAPs -->
      <section id="gaps" class="bg-white shadow rounded-xl p-4 md:p-5 scroll-mt-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold">GAPs</h2>
          <?php if (!empty($links['gaps']) && !empty($gaps)): ?><a class="text-sm text-brand-pink" href="<?= htmlspecialchars($listaUrl('pessoas/gaps', [])) ?>">ver na listagem</a><?php endif; ?>
        </div>
        <?php if (empty($gaps)): ?>
          <div class="text-sm text-gray-500 mb-3">Nenhum GAP registrado ainda.</div>
        <?php else: ?>
          <div class="space-y-2 mb-3">
            <?php foreach ($gaps as $g): ?>
              <a href="index.php?route=pessoas/gapShow&id=<?= (int)$g['id'] ?>" class="block border rounded p-3 text-sm hover:bg-gray-50">
                <div class="flex items-center justify-between gap-2">
                  <span class="font-medium"><?= htmlspecialchars($g['titulo']) ?></span>
                  <span class="px-2 py-0.5 rounded text-xs <?= $gapStatusClasses[$g['status']] ?? '' ?>"><?= htmlspecialchars($gapStatusLabels[$g['status']] ?? $g['status']) ?></span>
                </div>
                <div class="text-xs text-gray-500 mt-1">Prioridade: <?= htmlspecialchars($prioridadeLabels[$g['prioridade']] ?? $g['prioridade']) ?> · <?= $g['origem'] === 'avaliacao' ? 'De avaliação' : 'Manual' ?> · <?= htmlspecialchars(DateHelper::formatDate((string)$g['created_at'])) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <details id="novo-gap" class="scroll-mt-4">
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Novo GAP</summary>
          <form method="post" action="index.php?route=pessoas/gapCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="colaborador_id" value="<?= $cid ?>" />
            <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
            <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" maxlength="2000"></textarea>
            <select name="prioridade" class="border rounded p-2 w-full text-sm">
              <option value="baixa">Baixa</option>
              <option value="media" selected>Média</option>
              <option value="alta">Alta</option>
            </select>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Registrar GAP manual</button>
          </form>
        </details>
      </section>

      <!-- Feedbacks -->
      <section class="bg-white shadow rounded-xl p-4 md:p-5">
        <h2 class="font-semibold mb-3">Feedbacks</h2>
        <?php if (empty($feedbacks)): ?>
          <div class="text-sm text-gray-500 mb-3">Nenhum feedback registrado ainda.</div>
        <?php else: ?>
          <div class="space-y-2 mb-3">
            <?php foreach ($feedbacks as $f): ?>
              <div class="border rounded p-3 text-sm">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <span class="font-medium"><?= htmlspecialchars($f['titulo']) ?></span>
                  <span class="px-2 py-0.5 rounded text-xs <?= $feedbackTipoClasses[$f['tipo']] ?? '' ?>"><?= htmlspecialchars($feedbackTipoLabels[$f['tipo']] ?? $f['tipo']) ?></span>
                </div>
                <p class="text-gray-600"><?= htmlspecialchars(mb_strimwidth($f['descricao'], 0, 180, '…')) ?></p>
                <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(DateHelper::formatDate((string)$f['data_feedback'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <details id="novo-feedback" class="scroll-mt-4">
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Novo Feedback</summary>
          <form method="post" action="index.php?route=pessoas/feedbackCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="colaborador_id" value="<?= $cid ?>" />
            <select name="tipo" class="border rounded p-2 w-full text-sm" required>
              <option value="positivo">Feedback Positivo</option>
              <option value="melhoria">Feedback de Melhoria</option>
            </select>
            <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
            <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" required maxlength="2000"></textarea>
            <input type="date" name="data_feedback" class="border rounded p-2 w-full text-sm" value="<?= htmlspecialchars(date('Y-m-d')) ?>" />
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Registrar feedback</button>
          </form>
        </details>
      </section>

      <!-- Ações de Melhoria -->
      <section id="acoes" class="bg-white shadow rounded-xl p-4 md:p-5 scroll-mt-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold">Ações de Melhoria</h2>
          <?php if (!empty($links['acoes']) && !empty($acoes)): ?><a class="text-sm text-brand-pink" href="<?= htmlspecialchars($listaUrl('pessoas/acoes', [])) ?>">ver na listagem</a><?php endif; ?>
        </div>
        <?php if (empty($acoes)): ?>
          <div class="text-sm text-gray-500 mb-3">Nenhuma ação de melhoria criada ainda.</div>
        <?php else: ?>
          <div class="space-y-2 mb-3">
            <?php foreach ($acoes as $a): ?>
              <?php
                $dev = $desenvolvimento[(int)$a['id']] ?? null;
                $gapDaAcao = !empty($a['gap_id']) ? ($gapsPorId[(int)$a['gap_id']] ?? null) : null;
                $vencida = PessoasGestaoConfig::isAcaoVencida($a['prazo'] ?? null, (string)$a['status']);
              ?>
              <div class="border rounded p-3 text-sm <?= $vencida ? 'border-red-200' : '' ?>">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <a class="font-medium text-brand-brown hover:underline" href="index.php?route=pessoas/acaoShow&id=<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['titulo']) ?></a>
                  <span class="flex items-center gap-1">
                    <?php if ($vencida): ?><span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700 font-semibold">Atrasada</span><?php endif; ?>
                    <span class="px-2 py-0.5 rounded text-xs <?= $acaoStatusClasses[$a['status']] ?? '' ?>"><?= htmlspecialchars($acaoStatusLabels[$a['status']] ?? $a['status']) ?></span>
                  </span>
                </div>
                <div class="text-xs text-gray-500">
                  Responsável: <?= htmlspecialchars((string)($a['responsavel_nome'] ?? 'Não definido')) ?>
                  <?php if (!empty($a['prazo'])): ?> · Prazo: <?= htmlspecialchars(DateHelper::formatDate((string)$a['prazo'])) ?><?php endif; ?>
                  <?php if (!empty($a['gap_id'])): ?> · <a class="text-brand-pink" href="index.php?route=pessoas/gapShow&id=<?= (int)$a['gap_id'] ?>">Ver GAP relacionado</a><?php endif; ?>
                </div>
                <?php if ($dev): ?>
                  <div class="flex flex-wrap items-center gap-1 text-xs mt-2">
                    <?php if ($gapDaAcao && $gapDaAcao['origem'] === 'avaliacao'): ?><span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700">Avaliação</span><span class="text-gray-400">→</span><?php endif; ?>
                    <?php if ($gapDaAcao): ?><span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800">GAP: <?= htmlspecialchars(mb_strimwidth($gapDaAcao['titulo'], 0, 40, '…')) ?></span><span class="text-gray-400">→</span><?php endif; ?>
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-800">Ação</span><span class="text-gray-400">→</span>
                    <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800"><?= htmlspecialchars($dev['estado']) ?></span>
                  </div>
                  <?php if ($dev['plano']): ?>
                    <div class="text-xs mt-1">Plano de Ação: <?= htmlspecialchars($dev['plano']['titulo']) ?> — <?= htmlspecialchars((string)$dev['plano']['plano_status']) ?>
                      <?php if (!empty($links['plano'])): ?> · <a class="text-brand-pink" href="index.php?route=planoacao/show&id=<?= (int)$dev['plano']['plano_task_id'] ?>">Abrir Plano</a><?php endif; ?></div>
                  <?php endif; ?>
                  <?php foreach ($dev['treinamentos'] as $t): ?>
                    <div class="text-xs mt-1">Treinamento: <?= htmlspecialchars($t['nome']) ?> — <?= htmlspecialchars($t['situacao']['label']) ?><?= !empty($t['situacao']['certificado']) ? ' (certificado emitido)' : '' ?>
                      <?php if (!empty($links['treinamento'])): ?> · <a class="text-brand-pink" href="index.php?route=treinamentos/show&id=<?= (int)$t['treinamento_id'] ?>">Abrir</a><?php endif; ?></div>
                  <?php endforeach; ?>
                  <?php foreach ($dev['necessidades'] as $n): ?>
                    <div class="text-xs mt-1">Necessidade de treinamento: <?= htmlspecialchars($n['titulo']) ?> — <?= htmlspecialchars($necStatusLabels[$n['status']] ?? ucfirst($n['status'])) ?> (prioridade <?= htmlspecialchars($prioridadeLabels[$n['prioridade']] ?? $n['prioridade']) ?>)</div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <details id="nova-acao" class="scroll-mt-4">
          <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Nova Ação de Melhoria</summary>
          <form method="post" action="index.php?route=pessoas/acaoCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= $csrf ?>" />
            <input type="hidden" name="colaborador_id" value="<?= $cid ?>" />
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
              <div><label class="block text-xs text-gray-600 mb-1">Início</label><input type="date" name="data_inicio" class="border rounded p-2 w-full text-sm" /></div>
              <div><label class="block text-xs text-gray-600 mb-1">Prazo</label><input type="date" name="prazo" class="border rounded p-2 w-full text-sm" /></div>
            </div>
            <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar ação</button>
          </form>
        </details>
      </section>

      <!-- Treinamentos relacionados -->
      <section class="bg-white shadow rounded-xl p-4 md:p-5">
        <h2 class="font-semibold mb-3">Treinamentos relacionados</h2>
        <?php if (empty($treinamentosColaborador)): ?>
          <div class="text-sm text-gray-500">O colaborador não está vinculado a nenhum treinamento.</div>
        <?php else: ?>
          <ul class="space-y-1 text-sm">
            <?php foreach ($treinamentosColaborador as $t): ?>
              <li class="flex flex-wrap items-center justify-between gap-2 border-b last:border-0 py-1">
                <span><?= htmlspecialchars($t['nome']) ?><?= !empty($t['encerrado_em']) ? ' <span class="text-xs text-gray-500">(treinamento encerrado)</span>' : '' ?></span>
                <span class="flex items-center gap-2 text-xs">
                  <span class="px-2 py-0.5 rounded <?= $t['status'] === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>"><?= $t['status'] === 'concluido' ? 'Concluído' : 'Pendente' ?></span>
                  <?php if (!empty($links['treinamento'])): ?><a class="text-brand-pink" href="index.php?route=treinamentos/show&id=<?= (int)$t['id'] ?>">Abrir</a><?php endif; ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>

    <!-- Timeline -->
    <aside class="bg-white shadow rounded-xl p-4 md:p-5 h-fit" aria-labelledby="tlTitulo">
      <h2 id="tlTitulo" class="font-semibold mb-3">Linha do tempo</h2>
      <?php if (empty($timeline)): ?>
        <p class="text-sm text-gray-500">Nenhum evento de desenvolvimento registrado ainda.</p>
      <?php else: ?>
        <ol class="relative border-l border-gray-200 ml-2 space-y-3">
          <?php foreach ($timeline as $ev): ?>
            <li class="ml-4">
              <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-gray-300 border border-white" aria-hidden="true"></span>
              <div class="text-xs text-gray-500"><?= htmlspecialchars(DateHelper::formatDate((string)$ev['data'])) ?> · <?= htmlspecialchars($tipoTimeline[$ev['tipo']] ?? $ev['tipo']) ?></div>
              <div class="text-sm"><?php if ($ev['href'] !== ''): ?><a class="text-brand-brown hover:underline" href="<?= htmlspecialchars($ev['href']) ?>"><?= htmlspecialchars($ev['texto']) ?></a><?php else: ?><?= htmlspecialchars($ev['texto']) ?><?php endif; ?></div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </aside>
  </div>
</div>
<script>
  // Atalhos "Registrar ..." abrem o formulário correspondente (details) ao navegar para a âncora.
  (function () {
    function abrir(id) { var el = document.getElementById(id); if (el && el.tagName === 'DETAILS') { el.open = true; } }
    document.querySelectorAll('[data-abrir]').forEach(function (a) { a.addEventListener('click', function () { abrir(a.getAttribute('data-abrir')); }); });
    if (location.hash) { abrir(location.hash.slice(1)); }
  })();
</script>
