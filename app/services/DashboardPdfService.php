<?php
namespace App\Services;

use App\Core\DateHelper;
use App\Core\ReportBranding;
use Dompdf\Dompdf;
use Dompdf\Options;

class DashboardPdfService
{
    private ?string $lastError = null;
    private int $lastPageCount = 0;
    private ?array $lastLayout = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastPageCount(): int
    {
        return $this->lastPageCount;
    }

    public function getLastLayout(): ?array
    {
        return $this->lastLayout;
    }

    public function outputToBrowser(array $payload, bool $download = false): bool
    {
        $this->lastError = null;
        $this->lastPageCount = 0;
        $this->lastLayout = null;
        try {
            $pdf = $this->renderPdfBinary($payload);
            if ($pdf === '' || !str_starts_with($pdf, '%PDF')) {
                $this->lastError = 'PDF inválido gerado.';
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
            header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="dashboard.pdf"');
            echo $pdf;
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function renderPdfBinary(array $payload): string
    {
        $variants = [
            [
                'name' => 'normal',
                'branding_margins' => ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12],
                'layout' => [
                    'base_font' => 9,
                    'title_font' => 14,
                    'value_font' => 18,
                    'grid_pad' => 4,
                    'card_pad_y' => 7,
                    'card_pad_x' => 9,
                    'header_pad_y' => 7,
                    'header_pad_x' => 9,
                    'header_margin_bottom' => 8,
                    'bar_height' => 8,
                    'logo_max_height' => 32,
                    'footer_bottom_mm' => 8,
                ],
            ],
            [
                'name' => 'compact',
                'branding_margins' => ['top' => 12, 'right' => 10, 'bottom' => 12, 'left' => 10],
                'layout' => [
                    'base_font' => 8,
                    'title_font' => 13,
                    'value_font' => 16,
                    'grid_pad' => 3,
                    'card_pad_y' => 6,
                    'card_pad_x' => 8,
                    'header_pad_y' => 6,
                    'header_pad_x' => 8,
                    'header_margin_bottom' => 6,
                    'bar_height' => 7,
                    'logo_max_height' => 28,
                    'footer_bottom_mm' => 7,
                ],
            ],
        ];

        $bestPdf = '';
        $bestPages = 0;
        $bestLayout = null;

        foreach ($variants as $variant) {
            $res = $this->renderWithVariant($payload, $variant);
            if ($res['pdf'] === '') {
                continue;
            }
            if ($bestPdf === '' || $res['pages'] < $bestPages) {
                $bestPdf = $res['pdf'];
                $bestPages = $res['pages'];
                $bestLayout = $variant;
            }
            if ($res['pages'] <= 1) {
                break;
            }
        }

        $this->lastPageCount = $bestPages;
        $this->lastLayout = $bestLayout;
        return $bestPdf;
    }

    private function renderWithVariant(array $payload, array $variant): array
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 120);
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($payload, $variant), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pages = 0;
        try {
            $pages = (int)$dompdf->getCanvas()->get_page_count();
        } catch (\Throwable $e) {
            $pages = 0;
        }
        return ['pdf' => $dompdf->output(), 'pages' => $pages > 0 ? $pages : 999];
    }

    private function buildHtml(array $payload, array $variant): string
    {
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $pdfMeta = is_array($payload['pdf'] ?? null) ? $payload['pdf'] : [];
        $margins = is_array($variant['branding_margins'] ?? null) ? $variant['branding_margins'] : ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12];
        $layout = is_array($variant['layout'] ?? null) ? $variant['layout'] : [];

        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Dashboard',
            'header_title' => 'Dashboard',
            'header_subtitle' => 'Visão consolidada de desempenho',
            'logo_position' => 'left',
            'logo_width' => 108,
            'margins' => $margins,
            'footer_text' => 'Relatório do sistema',
            'generated_at' => DateHelper::now(),
        ]);

        $logoSrc = $branding['logo_uri'] ?? '';
        $periodLabel = ((string)($filters['month_start'] ?? '')) . ' até ' . ((string)($filters['month_end'] ?? ''));
        $empresaLabel = (string)($pdfMeta['cliente_label'] ?? '');

        $cron = is_array($payload['cronograma'] ?? null) ? $payload['cronograma'] : [];
        $bib = is_array($payload['biblioteca'] ?? null) ? $payload['biblioteca'] : [];
        $plano = is_array($payload['planoacao'] ?? null) ? $payload['planoacao'] : [];
        $aud = is_array($payload['auditorias'] ?? null) ? $payload['auditorias'] : [];
        $ind = is_array($payload['indicadores'] ?? null) ? $payload['indicadores'] : [];
        $tre = is_array($payload['treinamentos'] ?? null) ? $payload['treinamentos'] : [];

        $cronPct = (float)($cron['pct'] ?? 0.0);
        $indPct = (float)($ind['media_atingimento_pct'] ?? 0.0);
        $audPct = $aud['media_conformidade_pct'] !== null ? (float)$aud['media_conformidade_pct'] : null;
        $trePct = (float)($tre['participacao_pct'] ?? 0.0);

        $cronBy = is_array($cron['by_status'] ?? null) ? $cron['by_status'] : [];
        $planoBy = is_array($plano['by_status'] ?? null) ? $plano['by_status'] : [];

        ob_start();
        require __DIR__ . '/../views/dashboard/pdf.php';
        return (string)ob_get_clean();
    }
}
