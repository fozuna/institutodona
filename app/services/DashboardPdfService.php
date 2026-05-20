<?php
namespace App\Services;

use App\Core\DateHelper;
use App\Core\ReportBranding;
use Dompdf\Dompdf;
use Dompdf\Options;

class DashboardPdfService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function outputToBrowser(array $payload, bool $download = false): bool
    {
        $this->lastError = null;
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
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 120);
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($payload), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->output();
    }

    private function buildHtml(array $payload): string
    {
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $pdfMeta = is_array($payload['pdf'] ?? null) ? $payload['pdf'] : [];

        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Dashboard',
            'header_title' => 'Dashboard',
            'header_subtitle' => 'Visão consolidada de desempenho',
            'logo_position' => 'left',
            'logo_width' => 108,
            'margins' => ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12],
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

