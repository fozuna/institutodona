<?php
/**
 * Exportacao de Planos de Acao respeita UMA empresa por vez:
 *  - matriz selecionada -> so planos da matriz (sem filiais);
 *  - filial selecionada (via filial_id na matriz ou direto como cliente) -> so a filial;
 *  - planilha com somente as 7 colunas aprovadas.
 */
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

if (!class_exists('ZipArchive')) {
    echo "SKIP: ZipArchive indisponível; teste ignorado neste ambiente\n";
    exit(0);
}

$pdo = Database::getConnection();
$suffix = 'escopo_' . substr(bin2hex(random_bytes(4)), 0, 8);
$clienteIds = [];
$taskIds = [];

register_shutdown_function(function () use ($pdo, &$clienteIds, &$taskIds) {
    try {
        foreach ($taskIds as $id) { $pdo->prepare('DELETE FROM pdca_tasks WHERE id = :id')->execute(['id' => $id]); }
        foreach (array_reverse($clienteIds) as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'i@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

$insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
$cnpj = static function (): string {
    $b = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($b, 0, 2) . '.' . substr($b, 2, 3) . '.' . substr($b, 5, 3) . '/' . substr($b, 8, 4) . '-' . substr($b, 12, 2);
};
$insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => $cnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
$matrizId = (int)$pdo->lastInsertId();
$clienteIds[] = $matrizId;
$insCli->execute(['n' => 'Filial ' . $suffix, 'c' => $cnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => $matrizId]);
$filialId = (int)$pdo->lastInsertId();
$clienteIds[] = $filialId;

$tasks = new PlanoAcaoTaskModel();
$tituloMatriz = 'Plano da Matriz ' . $suffix;
$tituloFilial = 'Plano da Filial ' . $suffix;
$taskIds[] = $tasks->create(['id_cliente' => $matrizId, 'titulo' => $tituloMatriz, 'descricao' => 'Motivo matriz', 'meta_valor' => 'Meta matriz', 'meta_unidade' => 'Reunião', 'responsavel' => 'Resp Matriz', 'status' => 'Planejado']);
$taskIds[] = $tasks->create(['id_cliente' => $filialId, 'titulo' => $tituloFilial, 'status' => 'Pendente']);
ok('Criou matriz e filial com um plano cada');

function exportarPlanilha(string $query): string
{
    $out = tempnam(sys_get_temp_dir(), 'exp_escopo_');
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/helpers/export_planos_probe.php') . ' planoacao/export instituto ' . escapeshellarg('') . ' ' . escapeshellarg($query) . ' > ' . escapeshellarg($out) . ' 2> /dev/null';
    exec($cmd);
    $body = (string)file_get_contents($out);
    @unlink($out);
    if (substr($body, 0, 2) !== 'PK') {
        failFast('Exportação não retornou XLSX para "' . $query . '": ' . substr($body, 0, 200));
    }
    $tmp = tempnam(sys_get_temp_dir(), 'exp_escopo_xlsx_');
    file_put_contents($tmp, $body);
    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) failFast('XLSX inválido');
    $xml = (string)$zip->getFromName('xl/worksheets/sheet1.xml') . (string)$zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    @unlink($tmp);
    return $xml;
}

$xmlMatriz = exportarPlanilha('cliente=' . $matrizId);
if (!str_contains($xmlMatriz, $tituloMatriz)) failFast('Exportação da matriz deveria conter o plano da matriz');
if (str_contains($xmlMatriz, $tituloFilial)) failFast('Exportação da matriz NÃO deveria conter planos da filial');
ok('Matriz selecionada: exporta somente os planos da matriz');

$xmlFilialViaMatriz = exportarPlanilha('cliente=' . $matrizId . '&filial_id=' . $filialId);
if (!str_contains($xmlFilialViaMatriz, $tituloFilial) || str_contains($xmlFilialViaMatriz, $tituloMatriz)) {
    failFast('Filial selecionada no perfil da matriz deveria exportar somente a filial');
}
ok('Filial selecionada no perfil da matriz: exporta somente a filial');

$xmlFilialDireta = exportarPlanilha('cliente=' . $filialId);
if (!str_contains($xmlFilialDireta, $tituloFilial) || str_contains($xmlFilialDireta, $tituloMatriz)) {
    failFast('Filial selecionada diretamente deveria exportar somente a filial');
}
ok('Filial selecionada diretamente: exporta somente a filial');

foreach (['O Quê? (Problema)', 'Por que?', 'Meta / Objetivo', 'Origem', 'Responsável', 'Status', 'Prazo', 'Motivo matriz', 'Meta matriz', 'Reunião', 'Resp Matriz', 'Matriz ' . $suffix] as $needle) {
    if (!str_contains($xmlMatriz, htmlspecialchars($needle, ENT_XML1 | ENT_QUOTES, 'UTF-8')) && !str_contains($xmlMatriz, $needle)) {
        failFast('Planilha deveria conter: ' . $needle);
    }
}
foreach (['ID Cliente', 'Progresso', '>Fase<', 'Data de Cria'] as $needle) {
    if (str_contains($xmlMatriz, $needle)) failFast('Planilha não deveria conter a coluna: ' . $needle);
}
ok('Planilha tem só as 7 colunas aprovadas e o nome da empresa no cabeçalho');

echo "Plano de Acao export escopo empresa regression tests passed.\n";
