<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\CronogramaController;
use App\Core\PdfSupport;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\CronogramaEventoModel;
use App\Models\CronogramaEventoTipoModel;
use App\Models\CronogramaModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível no ambiente atual.\n";
    exit(0);
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$cronogramas = new CronogramaModel();
$eventos = new CronogramaEventoModel();
$tipos = new CronogramaEventoTipoModel();

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['cronograma_id' => 0, 'cliente_id' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        if (!empty($cleanup['cronograma_id'])) {
            $pdo->prepare('DELETE FROM cronograma_eventos WHERE id_cronograma = :id')->execute(['id' => $cleanup['cronograma_id']]);
            $pdo->prepare('DELETE FROM cronogramas WHERE id = :id')->execute(['id' => $cleanup['cronograma_id']]);
        }
        if (!empty($cleanup['cliente_id'])) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]);
        }
    } catch (\Throwable $e) {
    }
});

$tipoEvento = 'Tipo Grid PDF ' . uniqid();
$tipos->create($tipoEvento);

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Grid PDF ' . $suffix,
    'CNPJ' => '77.888.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Teste',
]);
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;

$cronogramaId = $cronogramas->create(['id_cliente' => $clienteId, 'nome' => 'Cronograma Grid PDF ' . $suffix, 'ano' => 2026]);
if ($cronogramaId <= 0) failFast('Falha ao criar cronograma');
$cleanup['cronograma_id'] = $cronogramaId;

$eventoFinalizadoId = $eventos->create($cronogramaId, [
    'topico' => 'Pilar Finalizado ' . $suffix,
    'unidade' => 'Matriz',
    'atividade' => 'Atividade concluída ' . $suffix,
    'responsavel' => 'Fulano de Tal',
    'periodicidade' => 'unico',
    'data' => '2026-03-10',
    'tipo_evento' => $tipoEvento,
]);
if ($eventoFinalizadoId <= 0) failFast('Falha ao criar evento finalizado');
if (!$eventos->setStatus($eventoFinalizadoId, 'Finalizado')) failFast('Falha ao finalizar evento');
ok('Criou evento finalizado (verde)');

$eventoAtrasadoId = $eventos->create($cronogramaId, [
    'topico' => 'Pilar Atrasado ' . $suffix,
    'unidade' => 'Filial',
    'atividade' => 'Atividade pendente ' . $suffix,
    'responsavel' => 'Ciclana de Tal',
    'periodicidade' => 'unico',
    'data' => '2026-01-05',
    'tipo_evento' => $tipoEvento,
]);
if ($eventoAtrasadoId <= 0) failFast('Falha ao criar evento atrasado');
ok('Criou evento em atraso (vermelho, data passada não finalizada)');

// 1) Preview HTML: confirma que os dois pilares, status e cores (legenda) aparecem.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'cronograma/grid_pdf', 'id' => (string)$cronogramaId, 'preview' => '1'];
ob_start();
(new CronogramaController())->gridPdf();
$preview = (string)ob_get_clean();
if (!str_contains($preview, '<html')) failFast('Preview não gerou HTML válido');
ok('Preview do PDF gera HTML válido');
if (!str_contains($preview, 'Pilar Finalizado ' . $suffix) || !str_contains($preview, 'Pilar Atrasado ' . $suffix)) {
    failFast('Preview não contém os dois pilares esperados (agrupamento por série preservado)');
}
ok('Preview contém os dois pilares (agrupamento por série preservado)');
if (!str_contains($preview, 'Fulano de Tal') || !str_contains($preview, 'Ciclana de Tal')) {
    failFast('Preview não contém os responsáveis esperados');
}
ok('Preview contém os responsáveis de cada pilar');
if (!str_contains($preview, 'Finalizado') || !str_contains($preview, 'Atrasado')) {
    failFast('Preview não contém os status esperados');
}
ok('Preview contém os status corretos (Finalizado / Atrasado)');
if (!str_contains($preview, '#dcfce7') || !str_contains($preview, '#fee2e2')) {
    failFast('Preview não contém as cores da legenda (verde/vermelho)');
}
ok('Preview preserva as cores da grade (verde=finalizado, vermelho=atrasado)');
if (!str_contains($preview, 'JAN') || !str_contains($preview, 'DEZ')) {
    failFast('Preview não contém as colunas dos 12 meses');
}
ok('Preview contém as colunas dos 12 meses');
if (!str_contains($preview, 'size: A4 landscape')) {
    failFast('Preview não está configurado para A4 paisagem');
}
ok('Preview configurado para A4 paisagem');

// 2) Filtro por farol: só o pilar finalizado deve aparecer.
$_GET = ['route' => 'cronograma/grid_pdf', 'id' => (string)$cronogramaId, 'status_filter' => 'finalizado', 'preview' => '1'];
ob_start();
(new CronogramaController())->gridPdf();
$previewFiltrado = (string)ob_get_clean();
if (!str_contains($previewFiltrado, 'Pilar Finalizado ' . $suffix)) failFast('Filtro "finalizado" removeu o pilar finalizado');
if (str_contains($previewFiltrado, 'Pilar Atrasado ' . $suffix)) failFast('Filtro "finalizado" não removeu o pilar atrasado (filtro de farol não preservado)');
ok('Filtro de farol (status_filter) é respeitado no PDF, igual à tela');

// 3) PDF binário real.
$_GET = ['route' => 'cronograma/grid_pdf', 'id' => (string)$cronogramaId];
header_remove();
ob_start();
(new CronogramaController())->gridPdf();
$pdf = (string)ob_get_clean();
if (substr($pdf, 0, 4) !== '%PDF') failFast('grid_pdf não retornou um PDF binário válido');
ok('grid_pdf retorna um PDF binário válido');
if (strlen($pdf) <= 1200) failFast('PDF gerado tem tamanho suspeito de vazio');
ok('PDF gerado tem tamanho mínimo plausível');
if (preg_match('/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([0-9.]+)\s+([0-9.]+)\s*\]/', $pdf, $mm)) {
    if ((float)$mm[1] <= (float)$mm[2]) {
        failFast('PDF não está em orientação paisagem (MediaBox largura <= altura)');
    }
    ok('PDF confirmado em orientação paisagem (MediaBox largura > altura)');
}

echo "Cronograma grid PDF smoke tests passed.\n";
ob_end_flush();
