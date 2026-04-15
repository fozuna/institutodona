<?php
require __DIR__ . '/../autoload.php';

use App\Services\AvaliacaoPdfService;

class AvaliacaoPdfServiceHeadersDouble extends AvaliacaoPdfService
{
    public function __construct()
    {
    }

    public function generateToFile(int $avaliacaoId, bool $force = false): ?string
    {
        $path = sys_get_temp_dir() . '/avaliacao-test-' . $avaliacaoId . '.pdf';
        file_put_contents($path, "%PDF-1.4\n%fake\n");
        return $path;
    }
}

$service = new AvaliacaoPdfServiceHeadersDouble();

header_remove();
ob_start();
$service->outputToBrowser(10, false);
$inline = (string)ob_get_clean();
$inlineHeaders = headers_list();

header_remove();
ob_start();
$service->outputToBrowser(11, true);
$download = (string)ob_get_clean();
$downloadHeaders = headers_list();

echo json_encode([
    'inline_pdf_header' => substr($inline, 0, 4),
    'download_pdf_header' => substr($download, 0, 4),
    'inline_content_type_ok' => count(array_filter($inlineHeaders, fn($h) => stripos($h, 'Content-Type: application/pdf') === 0)) > 0,
    'inline_disposition_ok' => count(array_filter($inlineHeaders, fn($h) => stripos($h, 'Content-Disposition: inline;') === 0)) > 0,
    'download_disposition_ok' => count(array_filter($downloadHeaders, fn($h) => stripos($h, 'Content-Disposition: attachment;') === 0)) > 0,
    'binary_transfer_header_ok' => count(array_filter($downloadHeaders, fn($h) => stripos($h, 'Content-Transfer-Encoding: binary') === 0)) > 0,
], JSON_UNESCAPED_UNICODE);
