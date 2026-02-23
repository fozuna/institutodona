<?php

$secret = 'd2QeaBbq0YXdgTaQDGWGP0HzVGuEa05S';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    exit('Acesso negado');
}

$repoDir = __DIR__;

$output = shell_exec("cd $repoDir && git fetch origin 2>&1 && git reset --hard origin/main 2>&1 && git clean -fd 2>&1");

file_put_contents('deploy.log', date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);

echo "Deploy executado";