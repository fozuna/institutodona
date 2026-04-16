<?php
namespace App\Services;

use App\Core\AvaliacaoQuestionario;
use App\Core\FaturamentoFaixas;
use App\Models\AvaliacaoModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class AvaliacaoPdfService
{
    private AvaliacaoModel $model;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
    }

    public function generateToFile(int $avaliacaoId, bool $force = false): ?string
    {
        $this->lastError = null;
        try {
            $path = $this->pdfPath($avaliacaoId);
            if (!$force && is_file($path)) {
                return $path;
            }
            $item = $this->model->find($avaliacaoId);
            if (!$item) {
                $this->lastError = 'Avaliacao nao encontrada.';
                return null;
            }
            $dir = dirname($path);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Nao foi possivel criar o diretorio do PDF.');
            }
            $binary = $this->renderPdfBinary($item);
            file_put_contents($path, $binary);
            return is_file($path) ? $path : null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    public function outputToBrowser(int $avaliacaoId, bool $download = false): bool
    {
        $pdf = $this->renderBinary($avaliacaoId, true);
        if ($pdf === null) {
            return false;
        }
        if (PHP_SAPI !== 'cli') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdf));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="avaliacao-' . $avaliacaoId . '.pdf"');
        echo $pdf;
        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function renderBinary(int $avaliacaoId, bool $force = false): ?string
    {
        $path = $this->generateToFile($avaliacaoId, $force);
        if (!$path || !is_file($path)) {
            return null;
        }
        $pdf = file_get_contents($path);
        if ($pdf === false || !str_starts_with((string)$pdf, '%PDF')) {
            return null;
        }
        return (string)$pdf;
    }

    public function pdfPath(int $avaliacaoId): string
    {
        return dirname(__DIR__, 2) . '/storage/pdfs/avaliacoes/avaliacao-' . $avaliacaoId . '.pdf';
    }

    public function renderHtml(int $avaliacaoId): ?string
    {
        $item = $this->model->find($avaliacaoId);
        if (!$item) {
            return null;
        }
        return $this->buildHtml($item);
    }

    private function renderPdfBinary(array $item): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 120);
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($item), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->output();
    }

    private function buildHtml(array $item): string
    {
        $respostas = json_decode((string)($item['respostas_json'] ?? '{}'), true);
        if (!is_array($respostas)) {
            $respostas = [];
        }
        if (!isset($respostas['eu']) && (isset($respostas['financeiro']) || isset($respostas['mercado']) || isset($respostas['pessoas']) || isset($respostas['processo']))) {
            $respostas = [
                'eu' => array_map('intval', (array)($respostas['financeiro'] ?? [])),
                'lideranca' => array_map('intval', (array)($respostas['mercado'] ?? [])),
                'processo' => array_map('intval', (array)($respostas['pessoas'] ?? [])),
                'gestao' => array_map('intval', (array)($respostas['processo'] ?? [])),
            ];
        }
        $pillars = AvaliacaoQuestionario::pilares();
        $scores = [
            'eu' => (int)($item['nota_financeiro'] ?? 0),
            'lideranca' => (int)($item['nota_mercado'] ?? 0),
            'processo' => (int)($item['nota_pessoas'] ?? 0),
            'gestao' => (int)($item['nota_processo'] ?? 0),
        ];
        $totals = array_map(static fn(array $questions): int => count($questions), $pillars);
        $sumIdeal = array_sum($totals);
        $sumResult = array_sum($scores);
        $total = $sumResult;
        $reals = [];
        foreach (['realidade_financeiro', 'realidade_mercado', 'realidade_pessoas', 'realidade_processo'] as $rk) {
            if (isset($item[$rk]) && $item[$rk] !== null) {
                $reals[] = (int)$item[$rk];
            }
        }
        $realPct = $reals ? (int)round(array_sum($reals) / count($reals)) : ($sumIdeal > 0 ? (int)round(($sumResult / $sumIdeal) * 100) : 0);
        $labels = ['EU' => (int)($item['nota_financeiro'] ?? 0), 'LIDERANÇA' => (int)($item['nota_mercado'] ?? 0), 'PROCESSO' => (int)($item['nota_pessoas'] ?? 0), 'GESTÃO' => (int)($item['nota_processo'] ?? 0)];
        $empresa = !empty($item['cliente_id']) ? (string)($item['cliente_nome'] ?? '') : (string)($item['empresa_nome'] ?? '');
        $isPotencial = (int)($item['cliente_id'] ?? 0) <= 0;
        $pillarTitles = ['eu' => 'EU', 'lideranca' => 'LIDERANÇA', 'processo' => 'PROCESSO', 'gestao' => 'GESTÃO'];
        $logoSrc = $this->logoAbsolutePath();

        ob_start();
        require __DIR__ . '/../views/avaliacoes/show_pdf.php';
        return (string)ob_get_clean();
    }

    private function logoAbsolutePath(): string
    {
        $candidates = [
            dirname(__DIR__, 2) . '/public/assets/img/logovivamais.png',
            dirname(__DIR__, 2) . '/public/assets/img/logovivamais.PNG',
            dirname(__DIR__, 2) . '/public_html/assets/img/logovivamais.png',
            dirname(__DIR__, 2) . '/public_html/assets/img/logovivamais.PNG',
            dirname(__DIR__, 2) . '/public/assets/img/logobco.png',
            dirname(__DIR__, 2) . '/public_html/assets/img/logobco.png',
        ];
        foreach ($candidates as $file) {
            $real = realpath($file);
            if ($real && is_file($real)) {
                return 'file:///' . str_replace('\\', '/', ltrim($real, '\\/'));
            }
        }
        return '';
    }
}
