<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AuditoriasController;
use App\Core\PdfSupport;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

ob_start();

function assert_true($condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
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

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['aud1' => 0, 'aud2' => 0, 'setor1' => 0, 'setor2' => 0, 'departamento_id' => 0, 'cliente_id' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        if (!empty($cleanup['aud1']) || !empty($cleanup['aud2'])) {
            $ids = array_values(array_filter([$cleanup['aud1'], $cleanup['aud2']]));
            if (!empty($ids)) {
                $in = implode(',', array_map('intval', $ids));
                $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
                $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
                $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
            }
        }
        if (!empty($cleanup['setor1'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor1']]); }
        if (!empty($cleanup['setor2'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor2']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {
    }
});

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Relatorio Executivo ' . $suffix,
    'CNPJ' => '22.333.444/0001-' . substr($suffix, 0, 2),
    'contato' => 'Teste',
]);
assert_true($clienteId > 0, 'Criou cliente para o teste do relatório executivo');
$cleanup['cliente_id'] = $clienteId;

$departamentoId = $departamentos->create(['nome' => 'Manutenção ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
assert_true($departamentoId > 0, 'Criou departamento');
$cleanup['departamento_id'] = $departamentoId;

$setorOficinaId = $setores->create(['nome' => 'Gestão de Oficina ' . $suffix, 'departamento_id' => $departamentoId]);
$setorPreventivaId = $setores->create(['nome' => 'Manutenção Preventiva ' . $suffix, 'departamento_id' => $departamentoId]);
assert_true($setorOficinaId > 0 && $setorPreventivaId > 0, 'Criou os dois setores (áreas) do teste');
$cleanup['setor1'] = $setorOficinaId;
$cleanup['setor2'] = $setorPreventivaId;

$auditoriaOficina = $auditorias->create([
    'cliente_id' => $clienteId,
    'setor_id' => $setorOficinaId,
    'nome_auditoria' => 'Auditoria Oficina ' . $suffix,
    'data_auditoria' => '2026-07-05',
    'questoes' => [
        ['pergunta' => 'O apontamento de início é realizado no RMS antes da execução? ' . $suffix, 'referencia_esperada' => 'Sim'],
        ['pergunta' => 'O serviço é executado conforme normas de segurança? ' . $suffix, 'referencia_esperada' => 'Sim'],
    ],
], 1);
assert_true($auditoriaOficina > 0, 'Criou auditoria de Gestão de Oficina');
$cleanup['aud1'] = $auditoriaOficina;

$auditoriaPreventiva = $auditorias->create([
    'cliente_id' => $clienteId,
    'setor_id' => $setorPreventivaId,
    'nome_auditoria' => 'Auditoria Preventiva ' . $suffix,
    'data_auditoria' => '2026-07-10',
    'questoes' => [
        ['pergunta' => 'O PCM monitora o horímetro dos equipamentos? ' . $suffix, 'referencia_esperada' => 'Sim'],
    ],
], 1);
assert_true($auditoriaPreventiva > 0, 'Criou auditoria de Manutenção Preventiva');
$cleanup['aud2'] = $auditoriaPreventiva;

$questoesOficina = $auditorias->questoesByAuditoria($auditoriaOficina);
$questoesPreventiva = $auditorias->questoesByAuditoria($auditoriaPreventiva);

$okOficina = $auditorias->finalizarAuditoria($auditoriaOficina, [
    ['questao_id' => $questoesOficina[0]['id'], 'conformidade' => 'nao_conforme', 'observacoes' => 'Problema de sistema no registro sendo tratado pelo PCM ' . $suffix],
    ['questao_id' => $questoesOficina[1]['id'], 'conformidade' => 'nao_conforme', 'observacoes' => 'Identificados técnicos sem EPI ' . $suffix],
], 1);
$okPreventiva = $auditorias->finalizarAuditoria($auditoriaPreventiva, [
    ['questao_id' => $questoesPreventiva[0]['id'], 'conformidade' => 'conforme', 'observacoes' => 'Conforme, porém atenção ao próximo ciclo ' . $suffix],
], 1);
assert_true($okOficina && $okPreventiva, 'Finalizou as duas auditorias com avaliações registradas');

$filters = [
    'cliente' => $clienteId,
    'departamento' => null,
    'setor' => null,
    'status' => null,
    'farol' => null,
    'inicio' => '2026-07-01',
    'fim' => '2026-07-31',
    'q' => '',
];

// 1) Agregação de dados do relatório (AuditoriaModel::executiveReportData).
$data = $auditorias->executiveReportData($filters);
assert_true((int)$data['total_auditorias'] === 2, 'Contabiliza as 2 auditorias realizadas no período');
assert_true((int)$data['total_itens'] === 3, 'Contabiliza os 3 itens avaliados no período');
assert_true((int)$data['total_conforme'] === 1, 'Contabiliza 1 item conforme');
assert_true((int)$data['total_nao_conforme'] === 2, 'Contabiliza 2 itens não conformes');
assert_true(count($data['por_area']) === 2, 'Agrupa por área (2 setores distintos)');
assert_true(count($data['nao_conformidades']) === 2, 'Lista as 2 não conformidades com descrição');
assert_true($data['nao_conformidades'][0]['questao_numero'] === 1 && $data['nao_conformidades'][1]['questao_numero'] === 2, 'Numera as questões não conformes na ordem da auditoria de origem');
assert_true(str_contains($data['nao_conformidades'][0]['observacao'], 'Problema de sistema'), 'Descrição da não conformidade inclui a observação registrada');
assert_true(count($data['observacoes_adicionais']) === 1 && str_contains($data['observacoes_adicionais'][0]['observacao'], 'atenção ao próximo ciclo'), 'Captura observação adicional registrada em item conforme');

// 2) Isolamento multiempresa: usuário sem acesso ao cliente não deve ver nada.
$_SESSION['user'] = ['id' => 2, 'nome' => 'Outro', 'email' => 'outro@example.com', 'tipo_acesso' => 'cliente_admin', 'allowed_client_ids' => [999999999]];
$scopedData = $auditorias->executiveReportData($filters);
assert_true((int)$scopedData['total_auditorias'] === 0, 'Usuário sem escopo para o cliente não vê nenhuma auditoria (multiempresa)');
$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

// 3) Tela HTML (auditorias/relatorio_executivo).
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'auditorias/relatorio_executivo', 'cliente' => (string)$clienteId, 'inicio' => '01/07/2026', 'fim' => '31/07/2026'];
ob_start();
(new AuditoriasController())->relatorioExecutivo();
$html = (string)ob_get_clean();
assert_true(str_contains($html, 'Relatório Executivo de Fechamento de Auditorias'), 'Tela do relatório executivo renderiza o título');
assert_true(str_contains($html, 'Gestão de Oficina ' . $suffix), 'Tela exibe a área "Gestão de Oficina"');
assert_true(str_contains($html, 'Manutenção Preventiva ' . $suffix), 'Tela exibe a área "Manutenção Preventiva"');
assert_true(str_contains($html, 'Segue o fechamento das auditorias realizadas'), 'Tela exibe o texto executivo gerado');
assert_true(str_contains($html, 'Observações adicionais registradas'), 'Tela exibe a seção de observações adicionais');

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível — pulando verificação de preview/PDF binário.\n";
} else {
    // 4) Preview HTML do PDF (auditorias/relatorio_executivo_pdf?preview=1).
    $_GET = ['route' => 'auditorias/relatorio_executivo_pdf', 'cliente' => (string)$clienteId, 'inicio' => '01/07/2026', 'fim' => '31/07/2026', 'preview' => '1'];
    ob_start();
    (new AuditoriasController())->relatorioExecutivoPdf();
    $preview = (string)ob_get_clean();
    assert_true(str_contains($preview, '<html'), 'Preview do PDF gera HTML válido');
    assert_true(str_contains($preview, 'Itens não conforme'), 'Preview do PDF contém a seção de não conformidades');

    // 5) PDF binário real (dompdf).
    unset($_GET['preview']);
    header_remove();
    ob_start();
    (new AuditoriasController())->relatorioExecutivoPdf();
    $pdf = (string)ob_get_clean();
    assert_true(substr($pdf, 0, 4) === '%PDF', 'relatorio_executivo_pdf retorna um binário PDF válido');
    assert_true(strlen($pdf) > 1200, 'PDF gerado tem tamanho mínimo plausível');
    if (preg_match('/\/MediaBox\s*\[\s*0\s+0\s+([0-9.]+)\s+([0-9.]+)\s*\]/', $pdf, $mm)) {
        assert_true((float)$mm[2] > (float)$mm[1], 'PDF do relatório executivo está em retrato (MediaBox height > width)');
    }
}

echo "Auditorias relatório executivo smoke tests passed.\n";
ob_end_flush();
