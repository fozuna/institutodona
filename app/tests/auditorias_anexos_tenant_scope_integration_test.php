<?php
require_once __DIR__ . '/../autoload.php';

// Evita que avisos do PHP (ex.: "headers already sent", esperado neste
// harness de CLI porque mensagens OK/FAIL já foram impressas antes da
// chamada ao controller) poluam o buffer de saída capturado via ob_start().
ini_set('display_errors', 'stderr');

use App\Controllers\AuditoriasController;
use App\Database\Database;
use App\Models\AuditoriaArquivoModel;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

function callApiListAnexos(int $auditoriaId, int $questaoId): array
{
    $_GET = ['route' => 'auditorias/api_list_anexos', 'auditoria_id' => $auditoriaId, 'questao_id' => $questaoId];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    http_response_code(200);
    ob_start();
    (new AuditoriasController())->apiListAnexos();
    $raw = (string)ob_get_clean();
    $decoded = json_decode($raw, true);
    return [
        'status' => http_response_code(),
        'raw' => $raw,
        'json' => is_array($decoded) ? $decoded : null,
    ];
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$auditorias = new AuditoriaModel();
$arquivos = new AuditoriaArquivoModel();

$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$suffix = uniqid('aud_anexo_scope_', true);
$clienteAId = 0;
$clienteBId = 0;
$departamentoAId = 0;
$setorAId = 0;
$auditoriaAId = 0;
$arquivoId = 0;

try {
    $clienteAId = $clientes->create([
        'nome_empresa' => 'Empresa A Anexos ' . $suffix,
        'CNPJ' => $makeCnpj(),
        'contato' => 'contato',
        'is_matriz' => 1,
        'matriz_id' => null,
    ]);
    assert_true($clienteAId > 0, 'Cria cliente A (dono da auditoria e do anexo)');

    $clienteBId = $clientes->create([
        'nome_empresa' => 'Empresa B Anexos ' . $suffix,
        'CNPJ' => $makeCnpj(),
        'contato' => 'contato',
        'is_matriz' => 1,
        'matriz_id' => null,
    ]);
    assert_true($clienteBId > 0, 'Cria cliente B (tenant sem relação com A)');

    $departamentoAId = $departamentos->create([
        'nome' => 'Departamento A Anexos ' . $suffix,
        'cliente_id' => $clienteAId,
        'cliente_ids' => [$clienteAId],
    ]);
    assert_true($departamentoAId > 0, 'Cria departamento do cliente A');

    $setorAId = $setores->create([
        'nome' => 'Setor A Anexos ' . $suffix,
        'departamento_id' => $departamentoAId,
    ]);
    assert_true($setorAId > 0, 'Cria setor do cliente A');

    $auditoriaAId = $auditorias->create([
        'cliente_id' => $clienteAId,
        'setor_id' => $setorAId,
        'nome_auditoria' => 'Auditoria Confidencial A ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Responsavel A',
            'pergunta' => 'Pergunta confidencial da empresa A',
            'referencia_esperada' => 'REF-A-' . $suffix,
            'processos' => ['P1'],
        ]],
    ], 1);
    assert_true($auditoriaAId > 0, 'Cria auditoria pertencente ao cliente A');

    $questoes = $auditorias->questoesByAuditoria($auditoriaAId);
    $questaoAId = (int)($questoes[0]['id'] ?? 0);
    assert_true($questaoAId > 0, 'Carrega a questao da auditoria do cliente A');

    $segredo = 'segredo_confidencial_empresa_a_' . $suffix . '.pdf';
    $arquivoId = $arquivos->create([
        'auditoria_id' => $auditoriaAId,
        'questao_id' => $questaoAId,
        'path' => '/tmp/nao-existe-' . $suffix . '.pdf',
        'compressed_path' => null,
        'original_name' => $segredo,
        'mime' => 'application/pdf',
        'size' => 1024,
        'sha256' => hash('sha256', $segredo),
        'thumb_path' => null,
        'created_by' => 1,
    ]);
    assert_true($arquivoId > 0, 'Cria anexo vinculado a questao do cliente A');

    // Sessao "atacante": usuario autenticado, mas escopado somente ao cliente B.
    $_SESSION['user'] = [
        'id' => 9501,
        'nome' => 'Cliente Admin B',
        'email' => 'cliente.b@test.local',
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteBId,
        'allowed_client_ids' => [$clienteBId],
        'selected_client_ids' => [$clienteBId],
        'unrestricted_access' => false,
    ];

    $attackerResponse = callApiListAnexos($auditoriaAId, $questaoAId);
    assert_true($attackerResponse['status'] === 403, 'Usuario de outro tenant recebe HTTP 403 ao listar anexos da auditoria A');
    assert_true(
        $attackerResponse['json'] !== null && ($attackerResponse['json']['success'] ?? true) === false,
        'Resposta JSON reporta success=false para usuario fora do escopo'
    );
    assert_true(
        strpos($attackerResponse['raw'], $segredo) === false,
        'Nome do anexo confidencial nao vaza na resposta para usuario de outro tenant'
    );

    // Sessao legitima: usuario autenticado e escopado ao proprio cliente A.
    $_SESSION['user'] = [
        'id' => 9502,
        'nome' => 'Cliente Admin A',
        'email' => 'cliente.a@test.local',
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteAId,
        'allowed_client_ids' => [$clienteAId],
        'selected_client_ids' => [$clienteAId],
        'unrestricted_access' => false,
    ];

    $ownerResponse = callApiListAnexos($auditoriaAId, $questaoAId);
    assert_true($ownerResponse['status'] === 200, 'Usuario do proprio tenant recebe HTTP 200 ao listar anexos da auditoria A');
    assert_true(
        $ownerResponse['json'] !== null && ($ownerResponse['json']['success'] ?? false) === true,
        'Resposta JSON reporta success=true para usuario do proprio tenant'
    );
    assert_true(
        strpos($ownerResponse['raw'], $segredo) !== false,
        'Nome do anexo aparece normalmente para usuario do proprio tenant (sem regressao funcional)'
    );

    echo "auditorias_anexos_tenant_scope_integration_test passed.\n";
} finally {
    unset($_SESSION['user']);
    if ($arquivoId > 0) {
        $pdo->prepare('DELETE FROM auditoria_arquivos WHERE id = :id')->execute(['id' => $arquivoId]);
    }
    if ($auditoriaAId > 0) {
        $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $auditoriaAId]);
    }
    if ($setorAId > 0) {
        $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $setorAId]);
    }
    if ($departamentoAId > 0) {
        $pdo->prepare('DELETE FROM departamento_clientes WHERE departamento_id = :id')->execute(['id' => $departamentoAId]);
        $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $departamentoAId]);
    }
    if ($clienteAId > 0) {
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteAId]);
    }
    if ($clienteBId > 0) {
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteBId]);
    }
}
