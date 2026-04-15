<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacoesController;
use App\Core\Security;
use App\Database\Database;

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$avaliacaoId = (int)$pdo->query('SELECT id FROM avaliacoes ORDER BY id DESC LIMIT 1')->fetchColumn();
if ($avaliacaoId <= 0) {
    echo 'NO_AVALIACAO';
    exit(0);
}

$controller = new AvaliacoesController();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['id' => $avaliacaoId];
ob_start();
$controller->show();
$html = (string)ob_get_clean();

header_remove();
$_GET = ['id' => $avaliacaoId];
ob_start();
$controller->relatorioPdf();
$inlinePdf = (string)ob_get_clean();
$inlineHeaders = headers_list();

header_remove();
$_GET = ['id' => $avaliacaoId, 'download' => 1];
ob_start();
$controller->relatorioPdf();
$downloadPdf = (string)ob_get_clean();
$downloadHeaders = headers_list();

echo json_encode([
    'show_has_no_public_open_button' => !str_contains($html, 'Abrir Formulário Público'),
    'show_has_no_plano_acao_button' => !str_contains($html, 'Plano de Ação'),
    'show_still_has_export_button' => str_contains($html, 'Exportar PDF'),
    'show_still_has_download_button' => str_contains($html, 'Baixar PDF'),
    'inline_pdf_header' => substr($inlinePdf, 0, 4),
    'download_pdf_header' => substr($downloadPdf, 0, 4),
    'inline_content_type_ok' => count(array_filter($inlineHeaders, fn($h) => stripos($h, 'Content-Type: application/pdf') === 0)) > 0,
    'inline_disposition_ok' => count(array_filter($inlineHeaders, fn($h) => stripos($h, 'Content-Disposition: inline;') === 0)) > 0,
    'download_disposition_ok' => count(array_filter($downloadHeaders, fn($h) => stripos($h, 'Content-Disposition: attachment;') === 0)) > 0,
], JSON_UNESCAPED_UNICODE);
