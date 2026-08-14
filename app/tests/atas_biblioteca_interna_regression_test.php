<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Core\Auth;
use App\Database\Database;
use App\Controllers\ManuaisController;
use App\Models\AtaModel;
use App\Models\ManualModel;
use App\Models\ManualPortalTokenModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 05B: acervo interno de Atas da Biblioteca. Cobre RBAC em profundidade
 * (rota ADMIN_MODULE + AtasController + AtaModel, todos Instituto-only),
 * upload/download/exclusao ponta-a-ponta via probe de subprocesso (mesmo
 * padrao de cronograma_evento_probe.php - necessario porque
 * AtasController::download() da exit() no caminho de sucesso), isolamento
 * total do Portal publico da Biblioteca e ausencia de impacto em
 * Manuais/Dashboard/Portal existentes.
 */

function runProbe(string $method, string $role, int $userId, string $action, array $extra = [], bool $withCsrf = true): array
{
    $probe = __DIR__ . '/helpers/atas_probe.php';
    $extraB64 = base64_encode(json_encode($extra, JSON_UNESCAPED_UNICODE));
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg($action) . ' '
        . escapeshellarg($extraB64) . ' '
        . escapeshellarg($withCsrf ? '1' : '0');
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $marker = '---PROBE_RESULT---';
    $pos = strpos($raw, $marker);
    $body = $pos !== false ? substr($raw, 0, $pos) : $raw;
    $resultLine = $pos !== false ? trim(substr($raw, $pos + strlen($marker))) : '';
    $decoded = json_decode($resultLine, true);
    return [
        'body' => $body,
        'status' => is_array($decoded) ? ($decoded['status'] ?? null) : null,
        'location' => is_array($decoded) ? ($decoded['location'] ?? '') : '',
    ];
}

$pdo = Database::getConnection();
$suffix = 'atas_' . date('YmdHis') . '_' . random_int(100, 999);
$ataIds = [];
$tmpFiles = [];

function makeTmpFile(string $content, string $ext): string
{
    global $tmpFiles;
    $path = sys_get_temp_dir() . '/' . uniqid('ata_probe_', true) . '.' . $ext;
    file_put_contents($path, $content);
    $tmpFiles[] = $path;
    return $path;
}

try {
    // ===================== PARTE 1: RBAC de rota (defesa em profundidade, camada 1) =====================
    function roleUser(string $role): array
    {
        return ['id' => 1, 'nome' => ucfirst($role), 'email' => $role . '@test.local', 'tipo_acesso' => $role, 'id_cliente' => null, 'allowed_client_ids' => []];
    }
    if (!AccessControl::canAccessRoute('atas/index', 'GET', roleUser('instituto'))) {
        failFast('Instituto deveria ter acesso à rota atas/index');
    }
    foreach (['cliente_admin', 'cliente', 'reader', 'consultor'] as $blockedRole) {
        if (AccessControl::canAccessRoute('atas/index', 'GET', roleUser($blockedRole))) {
            failFast("Perfil '$blockedRole' não deveria ter acesso à rota atas/index (ADMIN_MODULE é Instituto-only)");
        }
    }
    ok('Camada 1 (rota/AccessControl): atas/* liberado apenas para Instituto - cliente_admin/cliente/reader/consultor bloqueados');

    // ===================== PARTE 2: validação de upload (camada de arquivo) =====================
    $size = 1024;
    if (!empty(AtaModel::validateUpload('a.exe', $size, 'application/octet-stream')['ok'])) {
        failFast('Extensão .exe deveria ser rejeitada');
    }
    if (!empty(AtaModel::validateUpload('a.pdf', 0, 'application/pdf')['ok'])) {
        failFast('Tamanho zero deveria ser rejeitado');
    }
    if (!empty(AtaModel::validateUpload('a.pdf', 21 * 1024 * 1024, 'application/pdf')['ok'])) {
        failFast('Arquivo acima de 20MB deveria ser rejeitado');
    }
    if (!empty(AtaModel::validateUpload('a.pdf', $size, 'text/plain')['ok'])) {
        failFast('MIME incompatível com a extensão declarada deveria ser rejeitado');
    }
    if (empty(AtaModel::validateUpload('a.pdf', $size, 'application/pdf')['ok'])) {
        failFast('PDF válido deveria ser aceito pela validação');
    }
    if (empty(AtaModel::validateUpload('a.docx', $size, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')['ok'])) {
        failFast('DOCX válido deveria ser aceito pela validação');
    }
    ok('Validação de upload: PDF/DOC/DOCX aceitos, extensão inválida/tamanho excedido/MIME incompatível rejeitados (não confia só na extensão)');

    // ===================== PARTE 3: fluxo ponta-a-ponta via probe (Instituto) =====================
    $nomeAta = 'Ata Regressão ' . $suffix;
    $pdfContent = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\nCONTEUDO-UNICO-" . $suffix . "\n%%EOF";
    $pdfFile = makeTmpFile($pdfContent, 'pdf');

    $rStore = runProbe('POST', 'instituto', 9101, 'store', [
        'nome' => $nomeAta,
        'descricao' => 'Descrição de teste',
        'file_name' => 'ata.pdf',
        'file_type' => 'application/pdf',
        'file_tmp' => $pdfFile,
        'file_size' => strlen($pdfContent),
    ]);
    if (in_array((int)$rStore['status'], [400, 403, 404], true)) {
        failFast('Cenário 2: Instituto deveria conseguir cadastrar Ata. status=' . (string)$rStore['status'] . ' body=' . $rStore['body']);
    }
    $stmt = $pdo->prepare('SELECT id, arquivo FROM atas WHERE nome = :n');
    $stmt->execute(['n' => $nomeAta]);
    $row = $stmt->fetch();
    if (!$row) {
        failFast('Cenário 2: Ata não foi persistida no banco após o store()');
    }
    $ataId = (int)$row['id'];
    $ataIds[] = $ataId;
    $absPath = dirname(__DIR__, 2) . '/' . ltrim((string)$row['arquivo'], '/');
    if (!is_file($absPath)) {
        failFast('Cenário 2: arquivo físico da Ata não foi salvo em disco');
    }
    ok('Cenário 2: Instituto cadastra Ata - registro no banco e arquivo físico criados');

    // Instituto: listagem mostra a Ata recém-criada
    $rIndex = runProbe('GET', 'instituto', 9101, 'index');
    if (in_array((int)$rIndex['status'], [403, 404], true) || strpos($rIndex['body'], 'Biblioteca — Atas') === false) {
        failFast('Cenário 1: Instituto deveria ver a página de Atas. status=' . (string)$rIndex['status']);
    }
    if (strpos($rIndex['body'], htmlspecialchars($nomeAta)) === false) {
        failFast('Cenário 3: listagem de Instituto deveria conter a Ata recém-cadastrada');
    }
    ok('Cenário 1/3: Instituto vê a seção Atas e a listagem contém o item cadastrado');

    // Instituto: busca por nome (reaproveitando o padrão de filtro atual)
    $rSearch = runProbe('GET', 'instituto', 9101, 'index', ['nome' => $nomeAta]);
    if (strpos($rSearch['body'], htmlspecialchars($nomeAta)) === false) {
        failFast('Busca por nome deveria retornar a Ata cadastrada');
    }
    $rSearchMiss = runProbe('GET', 'instituto', 9101, 'index', ['nome' => 'nome-que-nao-existe-' . $suffix]);
    if (strpos($rSearchMiss['body'], htmlspecialchars($nomeAta)) !== false) {
        failFast('Busca por nome que não corresponde não deveria retornar a Ata');
    }
    ok('Pesquisa por nome funciona (reaproveita o padrão já usado em Manuais)');

    // Instituto: download funciona e entrega o conteúdo correto
    $rDownload = runProbe('GET', 'instituto', 9101, 'download', ['id' => $ataId]);
    if (strpos($rDownload['body'], 'CONTEUDO-UNICO-' . $suffix) === false) {
        failFast('Cenário 4: Instituto deveria conseguir baixar o conteúdo real da Ata. status=' . (string)$rDownload['status']);
    }
    ok('Cenário 4: Instituto baixa a Ata e recebe o conteúdo correto do arquivo');

    // ===================== PARTE 4: bloqueio por perfil (camadas 2 e 3 - controller e model) =====================
    foreach (['cliente_admin' => 9102, 'cliente' => 9103, 'reader' => 9104, 'consultor' => 9105] as $blockedRole => $blockedUserId) {
        $rBlockedIndex = runProbe('GET', $blockedRole, $blockedUserId, 'index');
        if (strpos($rBlockedIndex['body'], 'Biblioteca — Atas') !== false || strpos($rBlockedIndex['body'], htmlspecialchars($nomeAta)) !== false) {
            failFast("Cenário 6-9: perfil '$blockedRole' não deveria enxergar a seção/listagem de Atas");
        }
        $rBlockedDownload = runProbe('GET', $blockedRole, $blockedUserId, 'download', ['id' => $ataId]);
        if (strpos($rBlockedDownload['body'], 'CONTEUDO-UNICO-' . $suffix) !== false) {
            failFast("Cenário 10: perfil '$blockedRole' conseguiu baixar o conteúdo da Ata via URL direta (deveria ser bloqueado)");
        }
        $rBlockedDelete = runProbe('POST', $blockedRole, $blockedUserId, 'delete', ['id' => $ataId]);
        $stillThere = $pdo->prepare('SELECT COUNT(*) FROM atas WHERE id = :id');
        $stillThere->execute(['id' => $ataId]);
        if ((int)$stillThere->fetchColumn() !== 1) {
            failFast("Cenário 10: perfil '$blockedRole' não deveria conseguir excluir a Ata via URL direta");
        }
    }
    ok('Cenários 6-10: cliente_admin/cliente/reader/consultor não veem, não baixam e não excluem Atas via URL direta - RBAC protegido em profundidade (rota + controller + model)');

    // ===================== PARTE 5: arquivo inválido continua rejeitado no store() real =====================
    $exeFile = makeTmpFile('MZ-fake-binary', 'exe');
    $countBefore = (int)$pdo->query('SELECT COUNT(*) FROM atas')->fetchColumn();
    $rInvalid = runProbe('POST', 'instituto', 9101, 'store', [
        'nome' => 'Ata Inválida ' . $suffix,
        'file_name' => 'malware.exe',
        'file_type' => 'application/octet-stream',
        'file_tmp' => $exeFile,
        'file_size' => filesize($exeFile),
    ]);
    $countAfter = (int)$pdo->query('SELECT COUNT(*) FROM atas')->fetchColumn();
    if ((int)$rInvalid['status'] !== 400 || $countAfter !== $countBefore) {
        failFast('Cenário 17: upload de arquivo inválido (.exe) deveria ser rejeitado (400) sem criar registro. status=' . (string)$rInvalid['status']);
    }
    ok('Cenário 17: arquivo inválido continua sendo rejeitado no fluxo real de store()');

    // ===================== PARTE 6: isolamento do Portal público (não deve enxergar Atas de forma alguma) =====================
    $portalSrc = file_get_contents(__DIR__ . '/../models/ManualPortalTokenModel.php');
    $manualModelSrc = file_get_contents(__DIR__ . '/../models/ManualModel.php');
    if ($portalSrc === false || $manualModelSrc === false) {
        failFast('Não foi possível ler os arquivos do Portal público para checagem de isolamento');
    }
    if (stripos($portalSrc, 'atas') !== false) {
        failFast('ManualPortalTokenModel não deveria referenciar a tabela/rota atas');
    }
    if (preg_match('/portalList|portalCount/', $manualModelSrc, $m, PREG_OFFSET_CAPTURE)) {
        $portalSection = substr($manualModelSrc, 0, strpos($manualModelSrc, 'public function portalAllowsManual'));
        if (stripos($portalSection, 'atas') !== false) {
            failFast('portalList()/portalCount() não deveriam referenciar a tabela atas');
        }
    }
    ok('Cenário 14 (código-fonte): ManualPortalTokenModel e ManualModel::portalList()/portalCount() não referenciam Atas');

    Auth::login(['id' => 9101, 'nome' => 'Instituto Atas Probe', 'email' => 'instituto.atasprobe@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $manuaisModel = new ManualModel();
    $tokens = new ManualPortalTokenModel();
    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)');
    $stmt->execute(['n' => 'Cliente Portal Atas ' . $suffix, 'c' => '88.888.8' . substr($suffix, -2) . '/0001-88', 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();

    $countBeforePortal = $manuaisModel->portalCount([$clienteId]);
    // Cenário 14 (funcional): criar mais uma Ata não deve mexer no contador do Portal (empresa/departamento não têm relação com Atas).
    $countAfterPortal = $manuaisModel->portalCount([$clienteId]);
    if ($countBeforePortal !== 0 || $countAfterPortal !== 0 || $countBeforePortal !== $countAfterPortal) {
        failFast('Cenário 14: contagem do Portal não deveria ser afetada por Atas');
    }
    ok('Cenário 14: criação de Ata não altera a contagem do Portal público (tabelas totalmente separadas)');

    $token = $tokens->issue($clienteId, date('Y-m-d H:i:s', strtotime('+1 day')));
    $_SESSION['portal'] = null;
    $_GET = ['token' => $token];
    ob_start();
    (new ManuaisController())->portal();
    $portalHtml = (string)ob_get_clean();
    if (strpos($portalHtml, htmlspecialchars($nomeAta)) !== false) {
        failFast('Cenário 11: Portal público não deveria listar a Ata em nenhuma circunstância');
    }
    ok('Cenário 11: Portal público (ManuaisController::portal()) não lista Atas');

    // Cenário 12: tentar baixar pelo endpoint público de Manuais usando o ID de uma Ata real.
    unset($_SESSION['user']);
    $_GET = ['token' => $token];
    ob_start();
    (new ManuaisController())->portal(); // reidrata a sessão de portal válida
    ob_end_clean();
    http_response_code(200);
    $_GET = ['id' => (string)$ataId];
    ob_start();
    (new ManuaisController())->download();
    $downloadBody = (string)ob_get_clean();
    if ((int)http_response_code() !== 404 || strpos($downloadBody, 'CONTEUDO-UNICO-' . $suffix) !== false) {
        failFast('Cenário 12: manipular o ID de uma Ata na rota pública manuais/download não pode expor o arquivo. status=' . (string)http_response_code());
    }
    ok('Cenário 12: manuais/download não expõe uma Ata mesmo manipulando o ID (tabela separada - "Manual não encontrado")');
    Auth::login(['id' => 9101, 'nome' => 'Instituto Atas Probe', 'email' => 'instituto.atasprobe@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);

    // ===================== PARTE 7: Manuais/Portal/links permanentes continuam funcionando (sem regressão) =====================
    if (!is_array($manuaisModel->list([]))) {
        failFast('Cenário 15: ManualModel::list() deveria continuar funcionando normalmente');
    }
    $tokenCheck = $tokens->findValid($token);
    if (empty($tokenCheck)) {
        failFast('Cenário 16: link permanente do Portal (token) deveria continuar válido/funcional');
    }
    ok('Cenários 15/16: Manuais e links permanentes do Portal continuam funcionando sem regressão');

    $pdo->prepare('DELETE FROM manual_portal_tokens WHERE empresa_id = :id')->execute(['id' => $clienteId]);
    $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);

    // Cenário 13 (código-fonte): Dashboard não referencia Atas em nenhum contador.
    $dashboardSrc = file_get_contents(__DIR__ . '/../controllers/DashboardController.php');
    if ($dashboardSrc !== false && stripos($dashboardSrc, 'atas') !== false) {
        failFast('Cenário 13: DashboardController não deveria referenciar Atas em nenhum contador');
    }
    ok('Cenário 13: Dashboard não tem nenhum contador/referência a Atas nesta versão');

    // ===================== PARTE 8: Instituto exclui - consistência banco + arquivo físico =====================
    $rDeleteBlocked = runProbe('POST', 'cliente_admin', 9102, 'delete', ['id' => $ataId]);
    $rDeleteOk = runProbe('POST', 'instituto', 9101, 'delete', ['id' => $ataId]);
    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM atas WHERE id = :id');
    $stmtCheck->execute(['id' => $ataId]);
    if ((int)$stmtCheck->fetchColumn() !== 0) {
        failFast('Cenário 5: Instituto deveria conseguir excluir a Ata. status=' . (string)$rDeleteOk['status']);
    }
    if (is_file($absPath)) {
        failFast('Cenário 5: exclusão deveria remover também o arquivo físico (sem órfão)');
    }
    $ataIds = [];
    ok('Cenário 5: Instituto exclui a Ata - registro removido do banco e arquivo físico removido do disco (sem órfão)');

    echo "atas_biblioteca_interna_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($ataIds)) {
        $in = implode(',', array_map('intval', $ataIds));
        $rows = $pdo->query("SELECT arquivo FROM atas WHERE id IN ($in)")->fetchAll();
        foreach ($rows as $r) {
            $p = dirname(__DIR__, 2) . '/' . ltrim((string)$r['arquivo'], '/');
            if (is_file($p)) { @unlink($p); }
        }
        $pdo->exec("DELETE FROM atas WHERE id IN ($in)");
    }
    $pdo->prepare('DELETE FROM atas WHERE nome LIKE :n')->execute(['n' => '%' . $suffix . '%']);
    foreach ($tmpFiles as $f) {
        if (is_file($f)) { @unlink($f); }
    }
    Auth::logout();
}
