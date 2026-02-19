<?php
// Redireciona explicitamente para o index do public_html preservando o subcaminho (ex.: /institutodona)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base === '/' || $base === '\\') { $base = ''; }
$target = $base . '/public_html/index.php';
if (!headers_sent()) {
    header('Location: ' . $target);
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<meta charset="utf-8">
<meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>">
<title>Redirecionando…</title>
<body>
Se não for redirecionado automaticamente, <a href="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>">clique aqui</a>.
</body>
</html>
