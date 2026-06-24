<?php
require_once __DIR__ . '/../autoload.php';

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
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

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

$suffix = uniqid('aud_pdf_', true);
$clienteId = 0;
$departamentoId = 0;
$setorId = 0;
$auditoriaId = 0;
$arquivoIds = [];
$paths = [];
$baseDir = '';
$messages = [];
$jpegBinary = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAVEQEBAAAAAAAAAAAAAAAAAAAAAf/aAAwDAQACEAMQAAAByA//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/AlP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IVP/2Q==');
assert_true($jpegBinary !== false, 'Prepara imagem valida de teste para o PDF');
$messages[] = 'OK: Prepara imagem JPEG de teste para o PDF';

try {
    $clienteId = $clientes->create([
        'nome_empresa' => 'Cliente PDF Auditoria ' . $suffix,
        'CNPJ' => $makeCnpj(),
        'contato' => 'Teste',
        'is_matriz' => 1,
        'matriz_id' => null,
    ]);
    assert_true($clienteId > 0, 'Cria cliente para o teste do PDF');
    $messages[] = 'OK: Cria cliente para o teste do PDF';

    $departamentoId = $departamentos->create([
        'nome' => 'Departamento PDF ' . $suffix,
        'cliente_id' => $clienteId,
        'cliente_ids' => [$clienteId],
    ]);
    assert_true($departamentoId > 0, 'Cria departamento para o teste do PDF');
    $messages[] = 'OK: Cria departamento para o teste do PDF';

    $setorId = $setores->create([
        'nome' => 'Setor PDF ' . $suffix,
        'departamento_id' => $departamentoId,
    ]);
    assert_true($setorId > 0, 'Cria setor para o teste do PDF');
    $messages[] = 'OK: Cria setor para o teste do PDF';

    $auditoriaId = $auditorias->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria PDF Completa ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Responsavel PDF',
            'pergunta' => 'Pergunta PDF ' . $suffix,
            'referencia_esperada' => 'REF-PDF-' . $suffix,
            'processos' => ['PROC-A', 'PROC-B'],
        ]],
    ], 1);
    assert_true($auditoriaId > 0, 'Cria auditoria para o teste do PDF');
    $messages[] = 'OK: Cria auditoria para o teste do PDF';

    $questoes = $auditorias->questoesByAuditoria($auditoriaId);
    $questaoId = (int)($questoes[0]['id'] ?? 0);
    assert_true($questaoId > 0, 'Carrega a questao da auditoria para o PDF');
    $messages[] = 'OK: Carrega a questao da auditoria para o PDF';

    $ok = $auditorias->finalizarAuditoria($auditoriaId, [[
        'questao_id' => $questaoId,
        'conformidade' => 'nao_conforme',
        'observacoes' => 'Observacao completa da questao ' . $suffix,
    ]], 1);
    assert_true($ok, 'Finaliza auditoria com observacoes para o PDF');
    $messages[] = 'OK: Finaliza auditoria com observacoes para o PDF';

    $baseDir = __DIR__ . '/../../storage/tests_pdf_auditoria_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $suffix);
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    for ($i = 1; $i <= 2; $i++) {
        $path = $baseDir . DIRECTORY_SEPARATOR . 'evidencia_' . $i . '.jpg';
        file_put_contents($path, $jpegBinary);
        $paths[] = $path;
        $arquivoIds[] = $arquivos->create([
            'auditoria_id' => $auditoriaId,
            'questao_id' => $questaoId,
            'path' => $path,
            'compressed_path' => null,
            'original_name' => 'evidencia_' . $i . '_' . $suffix . '.jpg',
            'mime' => 'image/jpeg',
            'size' => filesize($path),
            'sha256' => hash_file('sha256', $path),
            'thumb_path' => null,
            'created_by' => 1,
        ]);
    }
    assert_true(count(array_filter($arquivoIds)) === 2, 'Cria anexos de imagem para o PDF');
    $messages[] = 'OK: Cria anexos de imagem para o PDF';

    $_GET = ['id' => $auditoriaId];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    (new AuditoriasController())->relatorioPdf();
    $pdf = (string)ob_get_clean();

    assert_true(str_starts_with($pdf, '%PDF-1.4'), 'Gera um binario PDF valido');
    $messages[] = 'OK: Gera um binario PDF valido';
    assert_true(str_contains($pdf, 'Auditoria PDF Completa ' . $suffix), 'PDF contem o nome da auditoria');
    $messages[] = 'OK: PDF contem o nome da auditoria';
    assert_true(str_contains($pdf, 'Pergunta PDF ' . $suffix), 'PDF contem o texto da pergunta');
    $messages[] = 'OK: PDF contem o texto da pergunta';
    assert_true(str_contains($pdf, 'PROC-A, PROC-B'), 'PDF contem os processos vinculados');
    $messages[] = 'OK: PDF contem os processos vinculados';
    assert_true(str_contains($pdf, 'Observacao completa da questao ' . $suffix), 'PDF contem as observacoes preenchidas');
    $messages[] = 'OK: PDF contem as observacoes preenchidas';
    assert_true(str_contains($pdf, 'evidencia_1_' . $suffix . '.jpg'), 'PDF lista o primeiro anexo associado');
    $messages[] = 'OK: PDF lista o primeiro anexo associado';
    assert_true(str_contains($pdf, 'evidencia_2_' . $suffix . '.jpg'), 'PDF lista o segundo anexo associado');
    $messages[] = 'OK: PDF lista o segundo anexo associado';
    assert_true(substr_count($pdf, '/Subtype /Image') === 2, 'PDF incorpora apenas as imagens anexadas no documento, sem logo indevido');
    $messages[] = 'OK: PDF incorpora as imagens anexadas no documento';
    assert_true(!str_contains($pdf, 'QuestÃ'), 'PDF nao contem texto mojibake nas questoes');
    $messages[] = 'OK: PDF nao contem texto mojibake nas questoes';
    assert_true(!str_contains($pdf, 'RelatÃ'), 'PDF nao contem texto mojibake no cabecalho');
    $messages[] = 'OK: PDF nao contem texto mojibake no cabecalho';

    foreach ($messages as $message) {
        echo $message . "\n";
    }
    echo "auditorias_relatorio_pdf_content_smoke passed.\n";
} finally {
    foreach ($arquivoIds as $arquivoId) {
        if ($arquivoId > 0) {
            $pdo->prepare('DELETE FROM auditoria_arquivos WHERE id = :id')->execute(['id' => $arquivoId]);
        }
    }
    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    if (!empty($baseDir) && is_dir($baseDir)) {
        @rmdir($baseDir);
    }
    if ($auditoriaId > 0) {
        $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $auditoriaId]);
    }
    if ($setorId > 0) {
        $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $setorId]);
    }
    if ($departamentoId > 0) {
        $pdo->prepare('DELETE FROM departamento_clientes WHERE departamento_id = :id')->execute(['id' => $departamentoId]);
        $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $departamentoId]);
    }
    if ($clienteId > 0) {
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
    }
}
