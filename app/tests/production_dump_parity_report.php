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
        'catalogo_grupo_sync_logs' => '/CREATE TABLE `catalogo_grupo_sync_logs`/i',
        'departamento_clientes' => '/CREATE TABLE `departamento_clientes`/i',
        'manuais' => '/CREATE TABLE `manuais`/i',
        'manual_filial_links' => '/CREATE TABLE `manual_filial_links`/i',
        'manual_portal_tokens' => '/CREATE TABLE `manual_portal_tokens`/i',
        'faturamento_faixas' => '/CREATE TABLE `faturamento_faixas`/i',
        'avaliacoes_publicas' => '/CREATE TABLE `avaliacoes_publicas`/i',
        'indicador_eventos' => '/CREATE TABLE `indicador_eventos`/i',
        'indicador_responsavel' => '/CREATE TABLE `indicador_responsavel`/i',
        'colaborador_status_logs' => '/CREATE TABLE `colaborador_status_logs`/i',
        'cronograma_evento_anexos' => '/CREATE TABLE `cronograma_evento_anexos`/i',
        'cronograma_evento_tipos' => '/CREATE TABLE `cronograma_evento_tipos`/i',
        'treinamentos' => '/CREATE TABLE `treinamentos`/i',
        'treinamentos_agenda' => '/CREATE TABLE `treinamentos_agenda`/i',
        'treinamento_setores' => '/CREATE TABLE `treinamento_setores`/i',
        'treinamento_funcoes' => '/CREATE TABLE `treinamento_funcoes`/i',
        'treinamento_colaboradores' => '/CREATE TABLE `treinamento_colaboradores`/i',
        'treinamento_participantes' => '/CREATE TABLE `treinamento_participantes`/i',
        'treinamento_auditoria_logs' => '/CREATE TABLE `treinamento_auditoria_logs`/i',
        'treinamento_export_cache' => '/CREATE TABLE `treinamento_export_cache`/i',
        'unidades_medida' => '/CREATE TABLE `unidades_medida`/i',
    ],
    'columns' => [
        'clientes.dominio_publico' => '/CREATE TABLE `clientes`[\s\S]*?`dominio_publico`/i',
        'avaliacoes.faturamento_faixa_id' => '/CREATE TABLE `avaliacoes`[\s\S]*?`faturamento_faixa_id`/i',
        'avaliacoes_publicas.faturamento_faixa_id' => '/CREATE TABLE `avaliacoes_publicas`[\s\S]*?`faturamento_faixa_id`/i',
        'auditoria_arquivos.descricao' => '/CREATE TABLE `auditoria_arquivos`[\s\S]*?`descricao`/i',
        'auditorias.lock_version' => '/CREATE TABLE `auditorias`[\s\S]*?`lock_version`/i',
        'auditorias.updated_at' => '/CREATE TABLE `auditorias`[\s\S]*?`updated_at`/i',
        'auditorias.responsavel_id' => '/CREATE TABLE `auditorias`[\s\S]*?`responsavel_id`/i',
        'colaboradores.matricula' => '/CREATE TABLE `colaboradores`[\s\S]*?`matricula`/i',
        'colaboradores.cpf' => '/CREATE TABLE `colaboradores`[\s\S]*?`cpf`/i',
        'colaboradores.status_atual' => '/CREATE TABLE `colaboradores`[\s\S]*?`status_atual`/i',
        'cronogramas.ativo' => '/CREATE TABLE `cronogramas`[\s\S]*?`ativo`/i',
        'cronograma_eventos.tipo_evento' => '/CREATE TABLE `cronograma_eventos`[\s\S]*?`tipo_evento`/i',
        'departamentos.ativo' => '/CREATE TABLE `departamentos`[\s\S]*?`ativo`/i',
        'departamentos.compartilhar_todas_filiais' => '/CREATE TABLE `departamentos`[\s\S]*?`compartilhar_todas_filiais`/i',
        'setores.ativo' => '/CREATE TABLE `setores`[\s\S]*?`ativo`/i',
        'funcoes.ativo' => '/CREATE TABLE `funcoes`[\s\S]*?`ativo`/i',
        'manual_portal_tokens.scope_ids_json' => '/CREATE TABLE `manual_portal_tokens`[\s\S]*?`scope_ids_json`/i',
        'manual_portal_tokens.filters_json' => '/CREATE TABLE `manual_portal_tokens`[\s\S]*?`filters_json`/i',
        'treinamentos.cliente_id' => '/CREATE TABLE `treinamentos`[\s\S]*?`cliente_id`/i',
        'treinamentos.tipo_treinamento' => '/CREATE TABLE `treinamentos`[\s\S]*?`tipo_treinamento`/i',
        'treinamentos.template_certificado' => '/CREATE TABLE `treinamentos`[\s\S]*?`template_certificado`/i',
        'treinamentos.assinatura_responsavel' => '/CREATE TABLE `treinamentos`[\s\S]*?`assinatura_responsavel`/i',
        'treinamentos_agenda.data_fim' => '/CREATE TABLE `treinamentos_agenda`[\s\S]*?`data_fim`/i',
        'treinamento_colaboradores.status_detalhe' => '/CREATE TABLE `treinamento_colaboradores`[\s\S]*?`status_detalhe`/i',
        'usuarios.platform_access' => '/CREATE TABLE `usuarios`[\s\S]*?`platform_access`/i',
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
