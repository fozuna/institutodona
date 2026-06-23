<?php

$secret = 'd2QeaBbq0YXdgTaQDGWGP0HzVGuEa05S';

function runDeployCommand(string $repoDir, string $command): array
{
    $output = [];
    $exitCode = 0;
    $fullCommand = 'cd ' . escapeshellarg($repoDir) . ' && ' . $command . ' 2>&1';
    exec($fullCommand, $output, $exitCode);
    return [
        'command' => $command,
        'output' => trim(implode(PHP_EOL, $output)),
        'exit_code' => $exitCode,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    exit('Acesso negado');
}

$repoDir = __DIR__;
$timestamp = date('Y-m-d H:i:s');
$logPath = __DIR__ . DIRECTORY_SEPARATOR . 'deploy.log';
$commands = [
    'git fetch origin',
    'git reset --hard origin/main',
    'git clean -fd',
    'composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader',
    'composer run deploy:migrate',
];

$lines = [$timestamp];
foreach ($commands as $command) {
    $result = runDeployCommand($repoDir, $command);
    $lines[] = '$ ' . $result['command'];
    $lines[] = $result['output'];
    $lines[] = 'exit_code=' . $result['exit_code'];
    if ((int)$result['exit_code'] !== 0) {
        file_put_contents($logPath, implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        exit('Falha no deploy automatico. Consulte deploy.log.');
    }
}

file_put_contents($logPath, implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL, FILE_APPEND);

echo "Deploy executado com migrations automáticas";
