<?php
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$base = rtrim(dirname($scriptName), '/\\');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$path = str_replace('\\', '/', (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/'));
$query = (string)(parse_url($requestUri, PHP_URL_QUERY) ?? '');
$relativePath = $path;
if ($base !== '' && str_starts_with($relativePath, $base)) {
    $relativePath = substr($relativePath, strlen($base));
}
$relativePath = '/' . ltrim($relativePath, '/');

if (preg_match('#^/avaliar/([^/]+)/?$#', $relativePath, $m)) {
    $target = $base . '/public_html/index.php?route=avaliacao-publica/open&slug=' . rawurlencode((string)$m[1]);
    if ($query !== '') {
        $target .= '&' . $query;
    }
} else {
    $target = $base . '/public_html/index.php';
    if ($query !== '') {
        $target .= '?' . $query;
    }
}

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
