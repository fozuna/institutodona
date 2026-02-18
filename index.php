<?php
// Redireciona explicitamente para o index do public_html
$target = 'public_html/index.php';
if (!headers_sent()) {
    header('Location: ' . $target);
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<meta charset="utf-8">
<meta http-equiv="refresh" content="0;url=public_html/index.php">
<title>Redirecionando…</title>
<body>
Se não for redirecionado automaticamente, <a href="public_html/index.php">clique aqui</a>.
</body>
</html>
