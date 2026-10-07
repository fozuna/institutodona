<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $avaliacao */
/** @var array $grupos */
/** @var array $resultadoPorGrupo */
/** @var string $classificacaoGeral */
/** @var array $gapPorResposta */
$resultadoGeral = (float)$avaliacao['resultado'];
$gapPorResposta = $gapPorResposta ?? [];
$csrf = \App\Core\Security::csrfToken();
?>
<div class="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-xl md:text-2xl font-bold text-brand-black">Resultado da Avaliação</h1>
    <div class="flex flex-wrap gap-2">
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$avaliacao['colaborador_id'] ?>">Histórico do colaborador</a>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/cicloShow&id=<?= (int)$avaliacao['ciclo_id'] ?>">Voltar ao ciclo</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
      <div><span class="text-gray-500">Colaborador:</span> <span class="font-semibold"><?= htmlspecialchars($avaliacao['colaborador_nome']) ?></span></div>
      <div><span class="text-gray-500">Ciclo:</span> <?= htmlspecialchars($avaliacao['ciclo_nome']) ?></div>
      <div><span class="text-gray-500">Modelo:</span> <?= htmlspecialchars($avaliacao['modelo_nome']) ?></div>
      <div><span class="text-gray-500">Departamento/Setor/Função:</span> <?= htmlspecialchars((string)($avaliacao['departamento'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['setor'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['funcao'] ?? '')) ?></div>
      <div><span class="text-gray-500">Avaliador:</span> <?= htmlspecialchars((string)($avaliacao['avaliador_nome'] ?? '—')) ?></div>
      <div><span class="text-gray-500">Finalizada em:</span> <?= !empty($avaliacao['finalizado_em']) ? htmlspecialchars(DateHelper::formatDateTime((string)$avaliacao['finalizado_em'])) : '—' ?></div>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-6 text-center">
    <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Resultado Geral</div>
    <div class="text-4xl font-bold text-brand-red"><?= number_format($resultadoGeral, 2, ',', '.') ?> <span class="text-lg text-gray-400">/ 5,00</span></div>
    <div class="text-sm font-semibold text-brand-brown mt-2"><?= htmlspecialchars($classificacaoGeral) ?></div>
    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden mt-4 max-w-md mx-auto">
      <div class="bg-brand-red h-3" style="width: <?= min(100, round(($resultadoGeral / 5) * 100)) ?>%"></div>
    </div>
    <details class="mt-4 text-left max-w-xl mx-auto">
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold text-center">Registrar Feedback sobre esta avaliação</summary>
      <form method="post" action="index.php?route=pessoas/feedbackCreate" class="mt-3 space-y-4 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$avaliacao['colaborador_id'] ?>" />
        <input type="hidden" name="avaliacao_id" value="<?= (int)$avaliacao['id'] ?>" />
        <input type="hidden" name="voltar_para" value="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$avaliacao['id'] ?>" />
        <?php $tipo = 'positivo'; $valores = []; require __DIR__ . '/../feedbacks/_form_campos.php'; ?>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Salvar Feedback</button>
      </form>
    </details>
  </div>

  <div class="bg-white shadow rounded-xl p-6">
    <h2 class="font-semibold mb-4">Resultado por grupo</h2>
    <div class="space-y-3">
      <?php foreach ($resultadoPorGrupo as $g): ?>
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="font-medium"><?= htmlspecialchars($g['grupo']) ?></span>
            <span class="text-gray-600"><?= $g['resultado'] !== null ? number_format((float)$g['resultado'], 2, ',', '.') . ' / 5,00 — ' . htmlspecialchars((string)$g['classificacao']) : '—' ?></span>
          </div>
          <?php if ($g['resultado'] !== null): ?>
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
              <div class="bg-brand-brown h-2" style="width: <?= min(100, round(((float)$g['resultado'] / 5) * 100)) ?>%"></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-6">
    <h2 class="font-semibold mb-1">Detalhamento das perguntas</h2>
    <p class="text-xs text-gray-500 mb-4">Notas baixas aparecem como ponto de atenção (não é acusação, é um ponto para desenvolvimento); notas altas permitem registrar reconhecimento rapidamente.</p>
    <?php foreach ($grupos as $grupoNome => $itens): ?>
      <div class="mb-4">
        <div class="text-sm font-semibold text-brand-brown mb-2"><?= htmlspecialchars($grupoNome) ?></div>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b bg-gray-50 text-xs text-gray-600">
              <th class="p-2">Pergunta</th>
              <th class="p-2 w-16">Peso</th>
              <th class="p-2 w-16">Nota</th>
              <th class="p-2">Observação</th>
              <th class="p-2 w-40">Ação</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($itens as $item): ?>
              <?php
                $nota = $item['resposta'] !== null ? (int)$item['resposta'] : null;
                $isPotencialGap = $nota !== null && PessoasGestaoConfig::isPotencialGap($nota);
                $isDestaque = $nota !== null && PessoasGestaoConfig::isDestaquePositivo($nota);
                $gapExistenteId = $gapPorResposta[(int)$item['id']] ?? null;
              ?>
              <tr class="border-b align-top">
                <td class="p-2">
                  <?= htmlspecialchars($item['pergunta_snapshot']) ?>
                  <?php if ($isPotencialGap): ?>
                    <span class="block mt-1 inline-block px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800">Ponto de atenção</span>
                  <?php endif; ?>
                </td>
                <td class="p-2"><?= htmlspecialchars((string)$item['peso_snapshot']) ?></td>
                <td class="p-2 font-semibold"><?= $nota !== null ? $nota : '—' ?></td>
                <td class="p-2 text-gray-600"><?= htmlspecialchars((string)($item['observacao'] ?? '')) ?></td>
                <td class="p-2">
                  <?php if ($isPotencialGap): ?>
                    <?php if ($gapExistenteId): ?>
                      <a class="text-xs text-green-700 font-semibold" href="index.php?route=pessoas/gapShow&id=<?= (int)$gapExistenteId ?>">GAP registrado</a>
                    <?php else: ?>
                      <details>
                        <summary class="cursor-pointer text-xs text-brand-red font-semibold">Registrar GAP</summary>
                        <form method="post" action="index.php?route=pessoas/gapCreate" class="mt-2 space-y-1 bg-gray-50 rounded p-2 w-56">
                          <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                          <input type="hidden" name="colaborador_id" value="<?= (int)$avaliacao['colaborador_id'] ?>" />
                          <input type="hidden" name="avaliacao_id" value="<?= (int)$avaliacao['id'] ?>" />
                          <input type="hidden" name="resposta_id" value="<?= (int)$item['id'] ?>" />
                          <input type="text" name="titulo" class="border rounded p-1 w-full text-xs" placeholder="Título" value="<?= htmlspecialchars(mb_substr((string)$item['pergunta_snapshot'], 0, 80)) ?>" required maxlength="255" />
                          <textarea name="descricao" class="border rounded p-1 w-full text-xs" rows="2" placeholder="Contexto (opcional)" maxlength="2000"></textarea>
                          <select name="prioridade" class="border rounded p-1 w-full text-xs">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                          </select>
                          <button type="submit" class="px-2 py-1 rounded bg-brand-red text-white text-xs w-full">Salvar GAP</button>
                        </form>
                      </details>
                    <?php endif; ?>
                  <?php elseif ($isDestaque): ?>
                    <details>
                      <summary class="cursor-pointer text-xs text-green-700 font-semibold">Registrar Feedback Positivo</summary>
                      <form method="post" action="index.php?route=pessoas/feedbackCreate" class="mt-2 space-y-1 bg-gray-50 rounded p-2 w-72">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                        <input type="hidden" name="colaborador_id" value="<?= (int)$avaliacao['colaborador_id'] ?>" />
                        <input type="hidden" name="avaliacao_id" value="<?= (int)$avaliacao['id'] ?>" />
                        <input type="hidden" name="tipo" value="positivo" />
                        <input type="hidden" name="voltar_para" value="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$avaliacao['id'] ?>" />
                        <input type="text" name="titulo" class="border rounded p-1 w-full text-xs" placeholder="Título" value="<?= htmlspecialchars(mb_substr((string)$item['pergunta_snapshot'], 0, 80)) ?>" required maxlength="255" />
                        <!-- Modelo SBI compacto (tipo fixo: positivo) -->
                        <textarea name="situacao" class="border rounded p-1 w-full text-xs" rows="2" placeholder="Situação: em que momento?" required maxlength="1000"></textarea>
                        <textarea name="comportamento" class="border rounded p-1 w-full text-xs" rows="2" placeholder="Comportamento: o que ele fez?" required maxlength="1000"></textarea>
                        <textarea name="impacto" class="border rounded p-1 w-full text-xs" rows="2" placeholder="Impacto: qual o efeito?" required maxlength="1000"></textarea>
                        <textarea name="orientacao" class="border rounded p-1 w-full text-xs" rows="2" placeholder="O que deve ser reconhecido e mantido?" required maxlength="1000"></textarea>
                        <textarea name="proximo_passo" class="border rounded p-1 w-full text-xs" rows="1" placeholder="Próximo passo (opcional)" maxlength="1000"></textarea>
                        <button type="submit" class="px-2 py-1 rounded bg-green-600 text-white text-xs w-full">Salvar Feedback</button>
                      </form>
                    </details>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>
