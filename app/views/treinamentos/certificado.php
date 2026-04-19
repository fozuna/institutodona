<?php /** @var array $agenda */ /** @var array $participant */ ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Certificado - <?= htmlspecialchars((string)($participant['colaborador_nome'] ?? 'Participante')) ?></title>
  <style>
    body { font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 40px; background: #f8fafc; }
    .page { max-width: 960px; margin: 0 auto; background: #fff; border: 8px solid #d97706; padding: 56px; }
    .title { font-size: 40px; font-weight: bold; text-align: center; margin-bottom: 24px; }
    .subtitle { text-align: center; font-size: 18px; margin-bottom: 32px; }
    .name { text-align: center; font-size: 34px; font-weight: bold; margin: 30px 0; }
    .body { text-align: center; font-size: 18px; line-height: 1.6; }
    .footer { margin-top: 42px; display: flex; justify-content: space-between; font-size: 14px; }
  </style>
</head>
<body onload="window.print()">
  <div class="page">
    <div class="title">Certificado</div>
    <div class="subtitle">Certificamos que</div>
    <div class="name"><?= htmlspecialchars((string)($participant['colaborador_nome'] ?? '')) ?></div>
    <div class="body">
      concluiu o treinamento <strong><?= htmlspecialchars((string)($agenda['treinamento_nome'] ?? '')) ?></strong>,
      com carga horária de <strong><?= htmlspecialchars((string)($agenda['carga_horaria'] ?? '')) ?> hora(s)</strong>,
      realizado em <strong><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($agenda['data'] ?? ''))) ?></strong>,
      na unidade <strong><?= htmlspecialchars((string)($agenda['unidade_nome'] ?? '')) ?></strong>.
    </div>
    <div class="footer">
      <span>Instrutor: <?= htmlspecialchars((string)($agenda['instrutor'] ?: ($agenda['responsavel_nome'] ?? ''))) ?></span>
      <span>Emitido em: <?= htmlspecialchars(\App\Core\DateHelper::now()) ?></span>
    </div>
  </div>
</body>
</html>
