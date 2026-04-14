<?php
$uri = str_replace('\\', '/', (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'));
$path = __DIR__ . $uri;

if ($uri !== '/' && is_file($path)) {
    return false;
}

if (preg_match('#^/avaliar/([^/]+)/?$#', $uri, $m)) {
    $_GET['route'] = 'avaliacao-publica/open';
    $_GET['slug'] = $m[1];
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('#^/public/avaliacao/api/validate/([A-Za-z0-9-]+)/?$#', $uri, $m)) {
    $_GET['resource'] = 'validate';
    $_GET['token'] = $m[1];
    require __DIR__ . '/public/avaliacao/index.php';
    return true;
}

if (preg_match('#^/public/avaliacao/([A-Za-z0-9-]+)/?$#', $uri, $m)) {
    $_GET['token'] = $m[1];
    require __DIR__ . '/public/avaliacao/index.php';
    return true;
}

require __DIR__ . '/index.php';
