<?php
require __DIR__ . '/../autoload.php';

use App\Services\AvaliacaoPdfService;

class AvaliacaoPdfHttpHeadersDouble extends AvaliacaoPdfService
{
    public function __construct()
    {
    }

    public function generateToFile(int $avaliacaoId, bool $force = false): ?string
    {
        $path = sys_get_temp_dir() . '/avaliacao-http-fixture-' . $avaliacaoId . '.pdf';
        file_put_contents($path, "%PDF-1.4\n%fixture\n");
        return $path;
    }
}

$service = new AvaliacaoPdfHttpHeadersDouble();
$service->outputToBrowser(999, !empty($_GET['download']));
