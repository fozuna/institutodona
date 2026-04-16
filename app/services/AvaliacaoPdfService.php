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

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
    }

    public function generateToFile(int $avaliacaoId, bool $force = false): ?string
    {
        $path = $this->pdfPath($avaliacaoId);
        if (!$force && is_file($path)) {
            return $path;
        }
        $item = $this->model->find($avaliacaoId);
        if (!$item) {
            return null;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, $this->renderPdfBinary($item));
        return is_file($path) ? $path : null;
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
        $options->set('dpi', 300);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($item), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
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
        $brand = [
            'navy' => '#11264f',
            'orange' => '#d9480f',
            'orangeSoft' => '#f97316',
            'grayBorder' => '#d9dde6',
            'grayText' => '#516078',
            'bg' => '#f3f4f6',
            'badgeBg' => '#FEF3C7',
            'badgeText' => '#92400E',
        ];
        $logo = $this->logoDataUri();
        $meta = [
            'empresa' => (string)($item['empresa_nome'] ?: ($item['cliente_nome'] ?? '—')),
            'nome' => (string)($item['nome'] ?? '—'),
            'whatsapp' => (string)($item['whatsapp'] ?? '—'),
            'email' => (string)($item['email'] ?? '—'),
            'funcionarios' => (string)($item['numero_funcionarios'] ?? '0'),
            'lideres' => (string)($item['numero_lideres'] ?? '0'),
            'faturamento' => FaturamentoFaixas::descricao($item['faturamento_faixa_id'] ?? null, $item['faturamento_medio_anual'] ?? null),
            'decisor' => !empty($item['tomador_decisao']) ? 'Sim' : 'Não',
            'origem' => ((string)($item['origem_cadastro'] ?? '') === 'potencial_cliente') ? 'Potencial cliente' : 'Cliente efetivo',
            'data' => !empty($item['created_at']) ? date('d/m/Y H:i', strtotime((string)$item['created_at'])) : '—',
        ];
        $scores = [
            'eu' => (int)($item['nota_financeiro'] ?? 0),
            'lideranca' => (int)($item['nota_mercado'] ?? 0),
            'processo' => (int)($item['nota_pessoas'] ?? 0),
            'gestao' => (int)($item['nota_processo'] ?? 0),
        ];
        $totals = array_map(static fn(array $questions): int => count($questions), $pillars);
        $sumIdeal = array_sum($totals);
        $sumResult = array_sum($scores);
        $sumPercent = $sumIdeal > 0 ? (int)round(($sumResult / $sumIdeal) * 100) : 0;

        ob_start();
        require __DIR__ . '/../views/avaliacoes/pdf.php';
        return (string)ob_get_clean();
    }

    private function logoDataUri(): string
    {
        if (!extension_loaded('gd')) {
            return '';
        }
        $file = dirname(__DIR__, 2) . '/public_html/assets/img/logobco.png';
        if (!is_file($file)) {
            return '';
        }
        $contents = file_get_contents($file);
        if ($contents === false) {
            return '';
        }
        return 'data:image/png;base64,' . base64_encode($contents);
    }
}
