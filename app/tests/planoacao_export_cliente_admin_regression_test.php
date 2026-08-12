<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\PlanoAcaoTaskModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function skip(string $msg): void { echo "SKIP: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Executa exportPlanos()/export() num processo PHP separado (helpers/export_planos_probe.php),
 * pois ambos terminam com exit() - tanto no download com sucesso quanto no 404
 * oculto de RBAC de rota - inviavel de chamar diretamente no processo do teste.
 * Retorna o corpo bruto da resposta (stdout) e os headers capturados (stderr).
 */
function runExportProbe(string $route, string $role, array $allowedClientIds, string $query): array
{
    $scratch = sys_get_temp_dir();
    $outFile = tempnam($scratch, 'export_probe_out_');
    $errFile = tempnam($scratch, 'export_probe_err_');
    $probe = __DIR__ . '/helpers/export_planos_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($route) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg(implode(',', $allowedClientIds)) . ' '
        . escapeshellarg($query)
        . ' > ' . escapeshellarg($outFile) . ' 2> ' . escapeshellarg($errFile);
    exec($cmd);
    $stdout = (string)file_get_contents($outFile);
    $stderr = (string)file_get_contents($errFile);
    @unlink($outFile);
    @unlink($errFile);
    return ['stdout' => $stdout, 'stderr' => $stderr];
}

function extractLocation(string $stderr): ?string
{
    if (preg_match('/^HEADER:Location:\s*(.+)$/mi', $stderr, $m)) {
        return trim($m[1]);
    }
    return null;
}

function isXlsxZip(string $stdout): bool
{
    return substr($stdout, 0, 2) === 'PK';
}

function isNotFoundPage(string $stdout): bool
{
    return str_contains($stdout, 'Conteúdo não encontrado');
}

// A geracao real do arquivo (XlsxExport::exportPlanos()) depende da extensao
// ZipArchive do PHP, indisponivel neste ambiente local (mesma limitacao ja
// documentada e tratada com skip gracioso em app/tests/export_planos_test.php).
// Isso e uma limitacao de ambiente, nao um bug desta correcao: o codigo
// reaproveitado (App\Core\XlsxExport::exportPlanos()) e o mesmo que a rota
// antiga ja chamava antes desta entrega. Os cenarios de bloqueio/tenant/RBAC
// (o foco real do item 08) nao dependem de ZipArchive e sao testados
// integralmente de qualquer forma.
$zipAvailable = class_exists('ZipArchive');
if (!$zipAvailable) {
    skip('Extensão ZipArchive indisponível neste ambiente; assertivas de geração real de XLSX serão puladas (RBAC/tenant continuam testados integralmente)');
}

function assertNoLeakedError(string $stdout, string $context, bool $zipAvailable): void
{
    if (!$zipAvailable && str_contains($stdout, 'ZipArchive')) {
        // Fatal error de ambiente (extensão ausente), não relacionado a esta correção.
        return;
    }
    foreach (['Fatal error', 'Uncaught Exception', 'Stack trace', 'in C:\\', 'in /'] as $needle) {
        if (str_contains($stdout, $needle)) {
            failFast("Vazamento de mensagem técnica/erro 500 em {$context}: contém \"{$needle}\"");
        }
    }
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$clienteAId = $clientes->create(['nome_empresa' => 'Cliente Export A ' . $suffix, 'CNPJ' => '55.666.7' . substr($suffix, 0, 2) . '/0001-55', 'contato' => 'Contato A']);
$clienteCId = $clientes->create(['nome_empresa' => 'Cliente Export C ' . $suffix, 'CNPJ' => '66.777.8' . substr($suffix, 0, 2) . '/0001-66', 'contato' => 'Contato C']);
$clienteVazioId = $clientes->create(['nome_empresa' => 'Cliente Export Vazio ' . $suffix, 'CNPJ' => '77.888.9' . substr($suffix, 0, 2) . '/0001-77', 'contato' => 'Contato Vazio']);
if ($clienteAId <= 0 || $clienteCId <= 0 || $clienteVazioId <= 0) { failFast('Falha ao criar clientes de teste'); }
ok('Criou clientes A, C (tenants distintos) e Vazio (sem planos) para o teste');

$tasks = new PlanoAcaoTaskModel();
$tituloA = 'Plano Exclusivo A ' . $suffix;
$tituloC = 'Plano Exclusivo C ' . $suffix;
$taskAId = $tasks->create(['id_cliente' => $clienteAId, 'titulo' => $tituloA, 'status' => 'Planejado']);
$taskCId = $tasks->create(['id_cliente' => $clienteCId, 'titulo' => $tituloC, 'status' => 'Planejado']);
if ($taskAId <= 0 || $taskCId <= 0) { failFast('Falha ao criar planos de ação de teste'); }
ok('Criou um plano de ação para cada tenant (A e C)');

// ===================== INSTITUTO =====================

// 1) Instituto exporta normalmente pela rota oficial nova.
$r1 = runExportProbe('planoacao/export', 'instituto', [], 'cliente=' . $clienteAId);
if ($zipAvailable) {
    if (!isXlsxZip($r1['stdout'])) { failFast('Instituto: planoacao/export não retornou um arquivo XLSX válido'); }
    ok('Instituto exporta normalmente pela rota oficial (planoacao/export)');
} else {
    skip('Instituto exporta normalmente pela rota oficial (planoacao/export) — requer ZipArchive');
}
assertNoLeakedError($r1['stdout'], 'Instituto/planoacao/export', $zipAvailable);

// 2) Filtros continuam funcionando (status que não existe para o cliente -> "nenhum encontrado", não 500).
// Esta assertiva não depende de ZipArchive: o filtro zera o resultado ANTES de chamar XlsxExport.
$r2 = runExportProbe('planoacao/export', 'instituto', [], 'cliente=' . $clienteAId . '&plano_status[]=Concluído');
if (isXlsxZip($r2['stdout'])) { failFast('Filtro de status deveria ter zerado o resultado (task criada é "Planejado"), mas retornou arquivo'); }
if (!str_contains($r2['stdout'], 'Nenhum plano de ação encontrado')) { failFast('Filtro de status não retornou a mensagem esperada de "nenhum encontrado"'); }
ok('Filtros de status continuam funcionando na nova rota (resultado zerado tratado corretamente)');

// 17) Regressão: fluxo antigo do Instituto (clientes/exportPlanos) continua funcionando -
// agora via redirecionamento interno para a rota oficial. O redirecionamento em si
// não depende de ZipArchive.
$rOld = runExportProbe('clientes/exportPlanos', 'instituto', [], 'id=' . $clienteAId);
$location = extractLocation($rOld['stderr']);
if ($location === null || !str_starts_with($location, 'index.php?')) {
    failFast('Rota antiga (clientes/exportPlanos) não emitiu redirecionamento para a rota oficial');
}
parse_str((string)parse_url($location, PHP_URL_QUERY), $redirectQuery);
if (($redirectQuery['route'] ?? '') !== 'planoacao/export' || (int)($redirectQuery['cliente'] ?? 0) !== $clienteAId) {
    failFast('Redirecionamento da rota antiga não aponta para planoacao/export com o cliente correto: ' . $location);
}
ok('Rota antiga (clientes/exportPlanos) redireciona internamente para a rota oficial, preservando o cliente');

if ($zipAvailable) {
    $rOldFollow = runExportProbe('planoacao/export', 'instituto', [], http_build_query($redirectQuery));
    if (!isXlsxZip($rOldFollow['stdout'])) { failFast('Seguir o redirecionamento da rota antiga não resultou em download válido'); }
    ok('Regressão: fluxo antigo do Instituto (clientes/exportPlanos) continua funcionando de ponta a ponta via redirecionamento');
} else {
    skip('Seguir o redirecionamento da rota antiga até o download — requer ZipArchive');
}

// ===================== CLIENTE ADMIN =====================

// 4/5) Cliente Admin (restrito à empresa A) exporta a própria empresa com sucesso.
$r4 = runExportProbe('planoacao/export', 'cliente_admin', [$clienteAId], 'cliente=' . $clienteAId);
if ($zipAvailable) {
    if (!isXlsxZip($r4['stdout'])) { failFast('Cliente Admin não conseguiu exportar Plano de Ação da própria empresa'); }
    ok('Cliente Admin consegue exportar Planos de Ação da própria empresa');

    // 6) O conteúdo exportado contém somente a própria empresa (não vaza o plano da empresa C).
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_check_');
    file_put_contents($tmp, $r4['stdout']);
    $zip = new ZipArchive();
    $conteudoOk = false;
    $contemOutraEmpresa = false;
    if ($zip->open($tmp) === true) {
        $shared = (string)$zip->getFromName('xl/sharedStrings.xml');
        $conteudoOk = str_contains($shared, $tituloA);
        $contemOutraEmpresa = str_contains($shared, $tituloC);
        $zip->close();
    }
    @unlink($tmp);
    if (!$conteudoOk) { failFast('Arquivo exportado pelo Cliente Admin não contém o plano da própria empresa'); }
    if ($contemOutraEmpresa) { failFast('CRÍTICO: arquivo exportado pelo Cliente Admin contém dado da empresa C (vazamento cross-tenant)'); }
    ok('Conteúdo exportado contém somente a própria empresa do Cliente Admin (sem vazamento cross-tenant)');
} else {
    skip('Cliente Admin exporta e conteúdo isolado por tenant — requer ZipArchive');
}
assertNoLeakedError($r4['stdout'], 'Cliente Admin/planoacao/export', $zipAvailable);

// 7) Filtros continuam funcionando para Cliente Admin também (não depende de ZipArchive).
$r7 = runExportProbe('planoacao/export', 'cliente_admin', [$clienteAId], 'cliente=' . $clienteAId . '&plano_status[]=Concluído');
if (isXlsxZip($r7['stdout'])) { failFast('Filtro de status não funcionou para Cliente Admin'); }
ok('Filtros continuam funcionando para o Cliente Admin');

// ===================== TENANT =====================

// 8) Cliente Admin tenta informar outra empresa (C) na URL -> bloqueado (404 oculto), não silenciosamente vazio.
$r8 = runExportProbe('planoacao/export', 'cliente_admin', [$clienteAId], 'cliente=' . $clienteCId);
if (isXlsxZip($r8['stdout'])) { failFast('CRÍTICO: Cliente Admin conseguiu exportar dados de outra empresa manipulando "cliente" na URL'); }
if (!isNotFoundPage($r8['stdout'])) { failFast('Tentativa de acessar outra empresa deveria retornar a página de "não encontrado" (404 oculto)'); }
ok('Cliente Admin tentando informar outra empresa na URL é bloqueado com 404 oculto (não retorna lista vazia silenciosa)');

// 9) Manipulação via parâmetro reconhecido em POST (cliente_id) também é bloqueada - mesmo
// gate genérico de tenant do framework (routeClienteCandidate() reconhece "cliente_id" tanto
// em GET quanto em POST).
$probeGate = __DIR__ . '/helpers/access_gate_probe.php';
$cmdPost = 'php ' . escapeshellarg($probeGate) . ' '
    . escapeshellarg('planoacao/export') . ' POST cliente_admin '
    . escapeshellarg((string)$clienteAId) . ' '
    . escapeshellarg('cliente_id=' . $clienteCId);
$outPost = [];
exec($cmdPost . ' 2>&1', $outPost);
$respPost = implode("\n", $outPost);
if (strpos($respPost, 'ALLOWED') !== false) {
    failFast('CRÍTICO: manipulação de cliente_id via POST não foi bloqueada pelo gate de tenant');
}
ok('Manipulação de cliente_id via POST para outra empresa é bloqueada pelo gate de tenant');

// 10) Empresa inexistente: resposta segura (erro tratado), não 500. Não depende de ZipArchive
// (o erro é retornado antes de chegar em XlsxExport).
$r10 = runExportProbe('planoacao/export', 'instituto', [], 'cliente=999999999');
if (!str_contains($r10['stdout'], 'Cliente inválido')) { failFast('Empresa inexistente não retornou a mensagem esperada de erro tratado'); }
ok('Empresa inexistente retorna resposta segura e tratada (sem 500)');

// ===================== CLIENTE (perfil não-admin) =====================

// 12) O papel "cliente" (não-admin) não tem hoje nenhuma rota alcançável do módulo
// planoacao/* (pré-existente, ver clientes_export_planos_permission_smoke.php) -
// continua bloqueado, mesmo comportamento de antes desta correção.
$r12 = runExportProbe('planoacao/export', 'cliente', [$clienteAId], 'cliente=' . $clienteAId);
if (isXlsxZip($r12['stdout'])) { failFast('Papel "cliente" (não-admin) não deveria conseguir exportar (ampliação de permissão não solicitada)'); }
if (!isNotFoundPage($r12['stdout'])) { failFast('Papel "cliente" deveria ser bloqueado com 404 oculto (módulo planoacao inacessível)'); }
ok('Comportamento do perfil "cliente" (não-admin) preservado conforme regra existente');

// ===================== REGRESSÃO ADICIONAL =====================

foreach (['r2' => $r2, 'r7' => $r7, 'r8' => $r8, 'r10' => $r10, 'r12' => $r12] as $label => $r) {
    assertNoLeakedError($r['stdout'], "cenário {$label}", $zipAvailable);
}
ok('Nenhum cenário testado (independente de ZipArchive) expôs erro 500 ou mensagem técnica');

// Vazio (empresa sem nenhum plano de ação): mensagem tratada, não 500, não arquivo vazio "sucesso".
// Não depende de ZipArchive (retorna antes de chegar em XlsxExport).
$rVazio = runExportProbe('planoacao/export', 'instituto', [], 'cliente=' . $clienteVazioId);
if (isXlsxZip($rVazio['stdout'])) { failFast('Empresa sem planos não deveria gerar um arquivo de exportação'); }
if (!str_contains($rVazio['stdout'], 'Nenhum plano de ação encontrado')) { failFast('Empresa sem planos não retornou a mensagem esperada'); }
ok('Empresa sem nenhum plano de ação retorna mensagem tratada (não gera arquivo vazio silenciosamente)');

echo "Planoacao export Cliente Admin regression tests passed.\n";
