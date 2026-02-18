<?php
// Redirect root to public_html to ensure correct asset paths
$target = 'public_html/';
if (!headers_sent()) {
    header('Location: ' . $target);
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<meta charset="utf-8">
<meta http-equiv="refresh" content="0;url=public_html/">
<title>Redirecionando…</title>
<body>
Se não for redirecionado automaticamente, <a href="public_html/">clique aqui</a>.
</body>
</html>
