<?php /** @var array $items */ ?>
<?php
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$firstDay = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
$weekday = (int)$firstDay->format('w'); // 0=Sun
$start = (clone $firstDay)->modify("-{$weekday} days");
$daysInMonth = (int)$firstDay->format('t');
$totalCells = 42;
$eventsByDate = [];
foreach ($items as $i) {
  $d = $i['data_prevista'] ?? null;
  if (!$d) continue;
  $key = (new \DateTime($d))->format('Y-m-d');
  if (!isset($eventsByDate[$key])) $eventsByDate[$key] = [];
  $eventsByDate[$key][] = $i;
}
$prev = (clone $firstDay)->modify('-1 month');
$next = (clone $firstDay)->modify('+1 month');
?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Agenda</h1>
    <div class="flex items-center gap-2">
      <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=agenda/index&year=<?= (int)$prev->format('Y') ?>&month=<?= (int)$prev->format('n') ?>">◀ <?= htmlspecialchars($prev->format('M')) ?></a>
      <div class="px-3 py-1 font-semibold"><?= htmlspecialchars($firstDay->format('F Y')) ?></div>
      <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=agenda/index&year=<?= (int)$next->format('Y') ?>&month=<?= (int)$next->format('n') ?>"><?= htmlspecialchars($next->format('M')) ?> ▶</a>
    </div>
  </div>
  <div class="bg-white shadow rounded p-4">
    <style>
      .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
      .cal-head { font-weight: 600; text-align: center; padding: 8px 0; color: #555; }
      .cal-cell { border: 1px solid #e5e7eb; border-radius: 6px; min-height: 120px; padding: 8px; }
      .cal-date { font-size: 12px; color: #666; margin-bottom: 6px; }
      .cal-event { display: block; font-size: 12px; margin: 2px 0; padding: 4px 6px; border-radius: 4px; background: #edf2ff; color: #1f2937; }
      .cal-event:hover { background: #e5ecff; text-decoration: none; }
      .cal-muted { opacity: 0.6; }
    </style>
    <div class="cal-grid mb-2">
      <?php foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $wd): ?>
        <div class="cal-head"><?= $wd ?></div>
      <?php endforeach; ?>
    </div>
    <div class="cal-grid">
      <?php for ($cell = 0; $cell < $totalCells; $cell++): ?>
        <?php $day = (clone $start)->modify("+{$cell} days"); $isCurrentMonth = ((int)$day->format('n') === $month); $key = $day->format('Y-m-d'); ?>
        <div class="cal-cell <?= $isCurrentMonth ? '' : 'cal-muted' ?>">
          <div class="cal-date"><?= (int)$day->format('j') ?></div>
          <?php foreach ($eventsByDate[$key] ?? [] as $ev): ?>
            <a class="cal-event" href="index.php?route=aplicacoes/show&id=<?= (int)$ev['id'] ?>">
              <?= htmlspecialchars(($ev['cliente'] ?? '') ? ($ev['cliente'] . ' • ') : '') ?><?= htmlspecialchars($ev['tarefa'] ?? '') ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</div>
