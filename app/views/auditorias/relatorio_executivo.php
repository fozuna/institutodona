<?php
use App\Core\DateHelper;
/** @var array $data */
/** @var string $resumoExecutivo */
/** @var array $filters */
/** @var array $clientes */
/** @var array $departamentos */
/** @var array $setores */

$totalAuditorias = (int)($data['total_auditorias'] ?? 0);
$totalItens = (int)($data['total_itens'] ?? 0);
$totalConforme = (int)($data['total_conforme'] ?? 0);
$totalNaoConforme = (int)($data['total_nao_conforme'] ?? 0);
$totalNaoAplica = (int)($data['total_nao_aplica'] ?? 0);
$pctMedia = $data['conformidade_pct_media'] ?? null;
$porArea = is_array($data['por_area'] ?? null) ? $data['por_area'] : [];
$naoConformidades = is_array($data['nao_conformidades'] ?? null) ? $data['nao_conformidades'] : [];
$observacoesAdicionais = is_array($data['observacoes_adicionais'] ?? null) ? $data['observacoes_adicionais'] : [];

$naoConformidadesPorArea = [];
foreach ($naoConformidades as $item) {
    $naoConformidadesPorArea[(string)$item['setor_nome']][] = $item;
}

$pdfQuery = http_build_query(array_filter([
    'route' => 'auditorias/relatorio_executivo_pdf',
    'cliente' => $filters['cliente'] ?? null,
    'departamento' => $filters['departamento'] ?? null,
    'setor' => $filters['setor'] ?? null,
    'status' => $filters['status'] ?? null,
    'farol' => $filters['farol'] ?? null,
    'inicio' => !empty($filters['inicio']) ? DateHelper::formatDate((string)$filters['inicio']) : null,
    'fim' => !empty($filters['fim']) ? DateHelper::formatDate((string)$filters['fim']) : null,
    'q' => $filters['q'] ?? null,
], static fn($v): bool => $v !== null && $v !== ''));
?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Relatório Executivo de Fechamento de Auditorias</h1>
    <div class="flex items-center gap-2">
      <a href="index.php?<?= $pdfQuery ?>&preview=1" target="_blank" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Visualizar</a>
      <a href="index.php?<?= $pdfQuery ?>" class="px-4 py-2 rounded bg-brand-red text-white">Gerar PDF</a>
      <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Voltar</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-12 gap-3">
    <input type="hidden" name="route" value="auditorias/relatorio_executivo" />
    <div class="md:col-span-2">
      <label class="block text-sm">Empresa</label>
      <select name="cliente" class="border rounded p-2 w-full">
        <option value="">Todas</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($filters['cliente'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Departamento</label>
      <select name="departamento" class="border rounded p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($departamentos as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= ((int)($filters['departamento'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Setor</label>
      <select name="setor" class="border rounded p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($setores as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= ((int)($filters['setor'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Início</label>
      <input name="inicio" value="<?= htmlspecialchars(!empty($filters['inicio']) ? DateHelper::formatDate((string)$filters['inicio']) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Fim</label>
      <input name="fim" value="<?= htmlspecialchars(!empty($filters['fim']) ? DateHelper::formatDate((string)$filters['fim']) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
    </div>
    <div class="md:col-span-2 flex items-end gap-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
      <a href="index.php?route=auditorias/relatorio_executivo" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Limpar</a>
    </div>
  </form>

  <div class="bg-white shadow rounded p-4 mb-4">
    <h2 class="font-semibold mb-2">Resumo executivo</h2>
    <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($resumoExecutivo)) ?></p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div class="bg-white shadow rounded p-4">
      <div class="text-xs uppercase tracking-wide text-gray-500">Auditorias realizadas</div>
      <div class="text-2xl font-bold text-brand-brown mt-1"><?= $totalAuditorias ?></div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="text-xs uppercase tracking-wide text-gray-500">Itens conformes</div>
      <div class="text-2xl font-bold text-green-700 mt-1"><?= $totalConforme ?></div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="text-xs uppercase tracking-wide text-gray-500">Itens não conformes</div>
      <div class="text-2xl font-bold text-red-700 mt-1"><?= $totalNaoConforme ?></div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="text-xs uppercase tracking-wide text-gray-500">Conformidade média</div>
      <div class="text-2xl font-bold text-brand-brown mt-1"><?= $pctMedia !== null ? htmlspecialchars(number_format((float)$pctMedia, 2, ',', '.') . '%') : '—' ?></div>
    </div>
  </div>

  <div class="bg-white shadow rounded overflow-x-auto mb-4">
    <div class="px-4 py-3 border-b font-semibold">Consolidado por área</div>
    <?php if (empty($porArea)): ?>
      <div class="p-4 text-sm text-gray-600">Nenhuma área com auditorias no período informado.</div>
    <?php else: ?>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Área</th>
            <th class="p-3">Departamento</th>
            <th class="p-3">Auditorias</th>
            <th class="p-3">Conforme</th>
            <th class="p-3">Não conforme</th>
            <th class="p-3">Não se aplica</th>
            <th class="p-3">Conformidade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($porArea as $area): ?>
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars((string)$area['setor_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)$area['departamento_nome']) ?></td>
              <td class="p-3"><?= (int)$area['total_auditorias'] ?></td>
              <td class="p-3"><?= (int)$area['conforme'] ?></td>
              <td class="p-3"><?= (int)$area['nao_conforme'] ?></td>
              <td class="p-3"><?= (int)$area['nao_aplica'] ?></td>
              <td class="p-3"><?= htmlspecialchars(number_format((float)$area['conformidade_pct'], 2, ',', '.') . '%') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-white shadow rounded p-4 mb-4">
    <h2 class="font-semibold mb-3">Itens não conforme</h2>
    <?php if (empty($naoConformidadesPorArea)): ?>
      <div class="text-sm text-gray-600">Nenhuma não conformidade identificada no período informado.</div>
    <?php else: ?>
      <?php foreach ($naoConformidadesPorArea as $areaNome => $itens): ?>
        <div class="mb-4">
          <div class="font-semibold text-brand-brown mb-1"><?= htmlspecialchars((string)$areaNome) ?>:</div>
          <ul class="list-disc pl-8 space-y-1">
            <?php foreach ($itens as $item): ?>
              <li class="text-sm">
                <span class="font-semibold">Questão <?= (int)$item['questao_numero'] ?></span>
                <div class="text-gray-700"><?= htmlspecialchars((string)$item['pergunta']) ?><?= $item['observacao'] !== '' ? ' — ' . htmlspecialchars((string)$item['observacao']) : '' ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($observacoesAdicionais)): ?>
    <div class="bg-white shadow rounded overflow-x-auto mb-4">
      <div class="px-4 py-3 border-b font-semibold">Observações adicionais registradas</div>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Área</th>
            <th class="p-3">Auditoria</th>
            <th class="p-3">Questão</th>
            <th class="p-3">Status</th>
            <th class="p-3">Observação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($observacoesAdicionais as $obs): ?>
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars((string)$obs['setor_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)$obs['auditoria_nome']) ?></td>
              <td class="p-3">Questão <?= (int)$obs['questao_numero'] ?></td>
              <td class="p-3"><?= htmlspecialchars((string)$obs['conformidade']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)$obs['observacao']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
