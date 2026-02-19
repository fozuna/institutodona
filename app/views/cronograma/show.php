<?php /** @var array $crono */ /** @var array $events */ /** @var array $grid */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Cronograma</h1>
      <div class="text-sm text-gray-600"><?= htmlspecialchars($crono['cliente'] ?? '') ?> · Ano <?= (int)($crono['ano'] ?? date('Y')) ?></div>
      <?php if (!empty($crono['nome'])): ?><div class="text-sm text-gray-600">Nome: <?= htmlspecialchars($crono['nome']) ?></div><?php endif; ?>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=cronograma/index">Voltar</a>
  </div>

  <!-- Grid estilo planilha por meses -->
  <div class="bg-white shadow rounded mb-6">
    <div class="px-4 py-3 border-b font-semibold">Cronograma mensal (J F M A M J J A S O N D)</div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="text-left border-b">
            <th class="p-3">Item</th>
            <th class="p-3">Tópico</th>
            <th class="p-3">Unidade</th>
            <th class="p-3">Atividade</th>
            <th class="p-3">Responsável</th>
            <th class="p-3">Modelo</th>
            <?php $months = ['J','F','M','A','M','J','J','A','S','O','N','D']; ?>
            <?php foreach ($months as $m): ?>
              <th class="p-3 text-center"><?= $m ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($grid as $row): ?>
            <tr class="border-b">
              <td class="p-3"><?= $i++ ?></td>
              <td class="p-3"><?= htmlspecialchars($row['topico']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['unidade']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['atividade']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['responsavel']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['modelo']) ?></td>
              <?php for ($mIdx = 1; $mIdx <= 12; $mIdx++): ?>
                <?php $ev = $row['meses'][$mIdx] ?? null; ?>
                <?php
                  $isPastMonth = ($crono['ano'] ?? (int)date('Y')) < (int)date('Y')
                    || (($crono['ano'] ?? (int)date('Y')) == (int)date('Y') && $mIdx < (int)date('n'));
                  $notDone = $ev ? (($ev['status'] ?? '') !== 'Realizado') : false;
                  $cellClass = ($ev && $isPastMonth && $notDone) ? 'bg-brand-red text-white' : 'bg-gray-50';
                ?>
                <td class="p-3 text-center <?= $cellClass ?>">
                  <?php if ($ev): ?>
                    <span title="Data: <?= htmlspecialchars($ev['data']) ?>"><?= 'X' ?></span>
                  <?php else: ?>
                    <span class="text-gray-300">—</span>
                  <?php endif; ?>
                </td>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-3">
      <div class="bg-white shadow rounded">
        <div class="px-4 py-3 border-b font-semibold flex items-center justify-between">
          <span>Eventos</span>
          <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=cronograma/addEventoForm&id=<?= (int)$crono['id'] ?>">Adicionar evento</a>
        </div>
        <div class="p-4">
          <table class="min-w-full">
            <thead>
              <tr class="text-left border-b">
                <th class="p-3">Data</th>
                <th class="p-3">Tópico / Unidade</th>
                <th class="p-3">Atividade</th>
                <th class="p-3">Responsável</th>
                <th class="p-3">Modelo</th>
                <th class="p-3">Status</th>
                <th class="p-3">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $ev): ?>
                <?php
                  $isPast = !empty($ev['data']) && (strtotime($ev['data']) < strtotime('today'));
                  $notDone = ($ev['status'] ?? '') !== 'Realizado';
                  $badgeClass = ($isPast && $notDone) ? 'bg-brand-red text-white' : 'bg-gray-200 text-gray-800';
                ?>
                <tr class="border-b">
                  <td class="p-3"><?= htmlspecialchars($ev['data']) ?></td>
                  <td class="p-3">
                    <div class="text-sm font-medium"><?= htmlspecialchars($ev['topico']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($ev['unidade'] ?? '') ?></div>
                  </td>
                  <td class="p-3"><?= htmlspecialchars($ev['atividade']) ?></td>
                  <td class="p-3"><?= htmlspecialchars($ev['responsavel'] ?? '') ?></td>
                  <td class="p-3"><?= htmlspecialchars($ev['modelo'] ?? '') ?></td>
                  <td class="p-3">
                    <span class="text-xs px-2 py-1 rounded <?= $badgeClass ?>"><?= htmlspecialchars($ev['status']) ?></span>
                  </td>
                  <td class="p-3">
                    <form method="post" action="index.php?route=cronograma/updateEvento" class="space-x-2 inline-flex items-center">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="id_evento" value="<?= (int)$ev['id'] ?>" />
                      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                      <input type="date" name="data" value="<?= htmlspecialchars($ev['data']) ?>" />
                      <input type="text" name="topico" value="<?= htmlspecialchars($ev['topico']) ?>" class="w-28" />
                      <input type="text" name="unidade" value="<?= htmlspecialchars($ev['unidade'] ?? '') ?>" class="w-28" />
                      <input type="text" name="atividade" value="<?= htmlspecialchars($ev['atividade']) ?>" class="w-40" />
                      <input type="text" name="responsavel" value="<?= htmlspecialchars($ev['responsavel'] ?? '') ?>" class="w-28" />
                      <select name="modelo">
                        <option value="">—</option>
                        <option value="Online" <?= ($ev['modelo'] ?? '') === 'Online' ? 'selected' : '' ?>>On-line</option>
                        <option value="Presencial" <?= ($ev['modelo'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                      </select>
                      <select name="status">
                        <?php foreach (['Planejado','Realizado','Não Realizado'] as $s): ?>
                          <option value="<?= $s ?>" <?= ($ev['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="icon-btn icon-btn--primary" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
                      <a class="text-brand-brown icon-action" href="index.php?route=cronograma/deleteEvento&id=<?= (int)$ev['id'] ?>&id_cronograma=<?= (int)$crono['id'] ?>" title="Remover"><span data-feather="trash-2"></span></a>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
  </div>
</div>
