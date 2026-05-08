<?php
require_once __DIR__ . '/../autoload.php';

use App\Services\TreinamentoDocumentService;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$agenda = [
    'id' => 1,
    'treinamento_id' => 1,
    'treinamento_nome' => 'Treinamento de Segurança',
    'data' => '2026-05-08 09:00:00',
    'carga_horaria' => '2',
    'instrutor' => 'Instrutor X',
    'responsavel_nome' => 'Responsável Y',
    'local' => 'Sala 1',
];

$participants = [
    ['colaborador_nome' => 'Ana Silva', 'departamento_nome' => 'Operações', 'funcao_nome' => 'Analista'],
    ['colaborador_nome' => 'Bruno Souza', 'departamento_nome' => 'RH', 'funcao_nome' => 'Assistente'],
];

$_GET['sort'] = 'nome';
$pdf = (new TreinamentoDocumentService())->renderPresencePdf($agenda, $participants);
if (!is_string($pdf) || $pdf === '' || !str_starts_with($pdf, '%PDF')) {
    failFast('PDF de presença deveria ser gerado com cabeçalho %PDF');
}
ok('Gerou PDF de lista de presença (A4) com sucesso');
echo "treinamentos_presenca_pdf_smoke passed.\n";

