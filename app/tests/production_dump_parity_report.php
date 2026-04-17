<?php
require_once __DIR__ . '/../autoload.php';

$root = dirname(__DIR__, 2);
$dumpPath = $root . '/public/institutodona_dump.sql';

if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump de produção não encontrado em public/institutodona_dump.sql\n");
    exit(2);
}

$dump = file_get_contents($dumpPath);
if ($dump === false) {
    fwrite(STDERR, "Não foi possível ler o dump de produção.\n");
    exit(2);
}

$checks = [
    'tables' => [
        'schema_migrations' => '/CREATE TABLE `schema_migrations`/i',
        'auditoria_responsaveis' => '/CREATE TABLE `auditoria_responsaveis`/i',
        'auditoria_questao_responsaveis' => '/CREATE TABLE `auditoria_questao_responsaveis`/i',
        'auditoria_historico' => '/CREATE TABLE `auditoria_historico`/i',
        'manuais' => '/CREATE TABLE `manuais`/i',
        'manual_portal_tokens' => '/CREATE TABLE `manual_portal_tokens`/i',
        'faturamento_faixas' => '/CREATE TABLE `faturamento_faixas`/i',
        'avaliacoes_publicas' => '/CREATE TABLE `avaliacoes_publicas`/i',
    ],
    'columns' => [
        'clientes.dominio_publico' => '/CREATE TABLE `clientes`[\s\S]*?`dominio_publico`/i',
        'avaliacoes.faturamento_faixa_id' => '/CREATE TABLE `avaliacoes`[\s\S]*?`faturamento_faixa_id`/i',
        'avaliacoes_publicas.faturamento_faixa_id' => '/CREATE TABLE `avaliacoes_publicas`[\s\S]*?`faturamento_faixa_id`/i',
        'auditoria_arquivos.descricao' => '/CREATE TABLE `auditoria_arquivos`[\s\S]*?`descricao`/i',
        'auditorias.lock_version' => '/CREATE TABLE `auditorias`[\s\S]*?`lock_version`/i',
        'auditorias.updated_at' => '/CREATE TABLE `auditorias`[\s\S]*?`updated_at`/i',
        'auditorias.responsavel_id' => '/CREATE TABLE `auditorias`[\s\S]*?`responsavel_id`/i',
    ],
];

$result = [
    'dump_path' => $dumpPath,
    'generated_at' => date('c'),
    'summary' => [
        'ok' => 0,
        'missing' => 0,
    ],
    'checks' => [],
    'missing' => [],
];

foreach ($checks as $group => $items) {
    foreach ($items as $name => $pattern) {
        $ok = preg_match($pattern, $dump) === 1;
        $result['checks'][$group][$name] = $ok;
        if ($ok) {
            $result['summary']['ok']++;
            continue;
        }
        $result['summary']['missing']++;
        $result['missing'][] = $group . ':' . $name;
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit($result['summary']['missing'] > 0 ? 1 : 0);
