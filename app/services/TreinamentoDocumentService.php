<?php
namespace App\Services;

use App\Core\DateHelper;
use App\Core\PdfSupport;
use App\Core\ReportBranding;
use App\Core\XlsxExport;
use Dompdf\Dompdf;
use Dompdf\Options;

class TreinamentoDocumentService
{
    public function exportEligibleXlsx(array $treinamento, array $rows): string
    {
        return XlsxExport::exportRows($rows, [
            'nome' => 'Nome Completo',
            'matricula' => 'Matricula',
            'setor' => 'Setor',
            'cargo' => 'Cargo',
            'cpf' => 'CPF',
            'email_corporativo' => 'E-mail Corporativo',
            'status_atual' => 'Status Atual',
            'status_elegibilidade' => 'Elegibilidade',
            'data_admissao' => 'Data de Admissao',
            'pre_cadastrado' => 'Pre-Cadastrado',
        ], 'treinamento-elegiveis-' . (int)$treinamento['id'] . '.xlsx', [
            'report_title' => 'Elegiveis - ' . (string)$treinamento['nome'],
            'header_title' => 'Lista de Colaboradores Elegiveis',
            'header_subtitle' => (string)$treinamento['nome'],
            'sheet_name' => 'Elegiveis',
        ]);
    }

    public function renderEligiblePdf(array $treinamento, array $rows, array $filters): string
    {
        $html = $this->wrapHtml(
            'Lista de Colaboradores Elegiveis',
            $this->filtersHtml($filters) . $this->tableHtml([
                'Nome Completo', 'Matricula', 'Setor', 'Cargo', 'CPF', 'E-mail', 'Status', 'Elegibilidade'
            ], array_map(static function (array $row): array {
                return [
                    $row['nome'] ?? '',
                    $row['matricula'] ?? '',
                    $row['setor'] ?? '',
                    $row['cargo'] ?? '',
                    $row['cpf'] ?? '',
                    $row['email_corporativo'] ?? '',
                    $row['status_atual'] ?? '',
                    $row['status_elegibilidade'] ?? '',
                ];
            }, $rows), 'Documento preparado para download e impressão.')
        , [
            'header_subtitle' => (string)$treinamento['nome'],
            'orientation' => 'landscape',
        ]);
        return $this->renderPdf($html, 'A4', 'landscape');
    }

    public function renderPresencePdf(array $agenda, array $participants): string
    {
        $sort = 'nome';
        if (!empty($_GET['sort']) && in_array((string)$_GET['sort'], ['nome', 'departamento'], true)) {
            $sort = (string)$_GET['sort'];
        }
        usort($participants, static function (array $a, array $b) use ($sort): int {
            $ka = $sort === 'departamento' ? (string)($a['departamento_nome'] ?? '') : (string)($a['colaborador_nome'] ?? '');
            $kb = $sort === 'departamento' ? (string)($b['departamento_nome'] ?? '') : (string)($b['colaborador_nome'] ?? '');
            $cmp = strcasecmp($ka, $kb);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcasecmp((string)($a['colaborador_nome'] ?? ''), (string)($b['colaborador_nome'] ?? ''));
        });

        $startDateTime = (string)($agenda['data'] ?? '');
        $startDate = $startDateTime !== '' ? substr($startDateTime, 0, 10) : '';
        $startTime = $startDateTime !== '' ? substr($startDateTime, 11, 5) : '';
        $endTime = '';
        $endDateTime = (string)($agenda['data_fim'] ?? '');
        if ($endDateTime !== '') {
            $endTime = substr($endDateTime, 11, 5);
        }
        $carga = trim((string)($agenda['carga_horaria'] ?? ''));
        if ($endTime === '' && $startDateTime !== '' && $carga !== '' && is_numeric($carga)) {
            try {
                $dt = new \DateTimeImmutable($startDateTime);
                $minutes = (int)round(((float)$carga) * 60);
                $endTime = $dt->modify('+' . max(0, $minutes) . ' minutes')->format('H:i');
            } catch (\Throwable $e) {
                $endTime = '';
            }
        }
        $dataExecucao = $startDateTime !== '' ? DateHelper::formatDateTime($startDateTime) : '';
        if ($startDateTime !== '' && $endDateTime !== '') {
            $dataExecucao .= ' até ' . DateHelper::formatDateTime($endDateTime);
        }
        $instrutor = (string)($agenda['instrutor'] ?: ($agenda['responsavel_nome'] ?? ''));
        $local = (string)($agenda['local'] ?? '');

        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Lista de Presença',
            'header_title' => 'Lista de Presença',
            'header_subtitle' => (string)($agenda['treinamento_nome'] ?? ''),
            'generated_at' => DateHelper::now(),
        ]);
        $logoUri = (string)($branding['logo_uri'] ?? '');
        $logo = $logoUri !== '' ? '<img src="' . $this->e($logoUri) . '" class="logo" alt="Logo" />' : '';

        $thead = '<tr>'
            . '<th class="col-name">Nome completo</th>'
            . '<th class="col-dep">Departamento</th>'
            . '<th class="col-sign">Assinatura</th>'
            . '</tr>';
        $tbody = '';
        foreach ($participants as $p) {
            $nome = (string)($p['colaborador_nome'] ?? '');
            $dep = (string)($p['departamento_nome'] ?? '');
            $tbody .= '<tr>'
                . '<td class="col-name">' . $this->e($nome) . '</td>'
                . '<td class="col-dep">' . $this->e($dep) . '</td>'
                . '<td class="col-sign"><span class="sign-line"></span></td>'
                . '</tr>';
        }
        if ($tbody === '') {
            $tbody = '<tr><td colspan="3">Nenhum participante pré-cadastrado para este agendamento.</td></tr>';
        }

        $header = '<div class="doc-header">'
            . '<div class="doc-header-top">' . $logo
            . '<div class="doc-title-block">'
            . '<div class="doc-title">Lista de Presença</div>'
            . '<div class="doc-subtitle">' . $this->e((string)($agenda['treinamento_nome'] ?? '')) . '</div>'
            . '</div></div>'
            . '<div class="doc-meta">'
            . '<div><strong>Título do treinamento:</strong> ' . $this->e((string)($agenda['treinamento_nome'] ?? '')) . '</div>'
            . '<div><strong>Instrutor:</strong> ' . $this->e($instrutor) . '</div>'
            . '<div><strong>Carga horária:</strong> ' . $this->e($carga !== '' ? $carga . 'h' : '—') . '</div>'
            . '<div><strong>Local:</strong> ' . $this->e($local !== '' ? $local : '—') . '</div>'
            . '<div><strong>Horário:</strong> ' . $this->e(($startTime !== '' ? $startTime : '—') . ($endTime !== '' ? (' às ' . $endTime) : '')) . '</div>'
            . '<div><strong>Data(s) de execução:</strong> ' . $this->e($dataExecucao !== '' ? $dataExecucao : '—') . '</div>'
            . '</div>'
            . '</div>';

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>'
            . '@page { margin: 20mm; }'
            . 'body{font-family:DejaVu Sans, Arial, sans-serif;color:#000;font-size:10pt;}'
            . '.doc-header{border-bottom:1px solid #000;padding-bottom:8mm;margin-bottom:6mm;}'
            . '.doc-header-top{display:flex;align-items:flex-start;gap:10mm;}'
            . '.logo{width:25mm;height:auto;object-fit:contain;}'
            . '.doc-title{font-size:16pt;font-weight:bold;line-height:1.1;}'
            . '.doc-subtitle{font-size:11pt;margin-top:2mm;}'
            . '.doc-meta{margin-top:6mm;display:grid;grid-template-columns:repeat(2,1fr);gap:3mm 8mm;font-size:10pt;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th,td{border:1px solid #000;padding:6px;vertical-align:middle;}'
            . 'th{font-size:10pt;font-weight:bold;background:#fff;text-transform:none;}'
            . 'thead{display:table-header-group;}'
            . 'tr{page-break-inside:avoid;}'
            . 'td{height:14mm;}'
            . '.col-name{width:48%;}'
            . '.col-dep{width:22%;}'
            . '.col-sign{width:30%;}'
            . '.sign-line{display:block;width:100%;border-bottom:1px solid #000;height:10mm;}'
            . '.footnote{margin-top:6mm;font-size:9pt;}'
            . '</style></head><body>'
            . $header
            . '<table><thead>' . $thead . '</thead><tbody>' . $tbody . '</tbody></table>'
            . '<div class="footnote"><strong>Assinatura:</strong> assine no campo reservado na coluna de assinatura para autenticar a presença.</div>'
            . '<script type="text/php">'
            . 'if (isset($pdf) && isset($fontMetrics)) {'
            . '$text = "Página {PAGE_NUM} de {PAGE_COUNT}";'
            . '$font = $fontMetrics->get_font("DejaVu Sans", "normal");'
            . '$size = 9;'
            . '$w = $fontMetrics->get_text_width($text, $font, $size);'
            . '$x = ($pdf->get_width() - $w) / 2;'
            . '$y = $pdf->get_height() - 28;'
            . '$pdf->page_text($x, $y, $text, $font, $size, [0,0,0]);'
            . '}'
            . '</script>'
            . '</body></html>';

        return $this->renderPdf($html, 'A4', 'portrait', true);
    }

    public function renderDashboardPdf(array $dashboard, array $filters): string
    {
        $summary = $dashboard['resumo'] ?? [];
        $cards = '<div class="summary-grid">'
            . $this->summaryCard('Treinamentos', (string)($summary['treinamentos_monitorados'] ?? 0))
            . $this->summaryCard('Inscritos', (string)($summary['total_inscritos'] ?? 0))
            . $this->summaryCard('Presentes', (string)($summary['total_presentes'] ?? 0))
            . $this->summaryCard('Certificados', (string)($summary['total_certificados'] ?? 0))
            . '</div>';

        $participacaoTable = $this->tableHtml(['Treinamento', 'Tipo', 'Instrutor', 'Inscritos', 'Presentes', 'Percentual'], array_map(static function (array $row): array {
            return [
                $row['treinamento_nome'] ?? '',
                $row['tipo_treinamento'] ?? '',
                $row['instrutor'] ?? '',
                (string)($row['total_inscritos'] ?? 0),
                (string)($row['total_presentes'] ?? 0),
                number_format((float)($row['percentual_participacao'] ?? 0), 2, ',', '.') . '%',
            ];
        }, $dashboard['participacao_treinamento'] ?? []), '');

        $setoresTable = $this->tableHtml(['Setor', 'Total Colaboradores', 'Treinados', 'Horas', 'Media', 'Percentual'], array_map(static function (array $row): array {
            return [
                $row['setor_nome'] ?? '',
                (string)($row['total_colaboradores_setor'] ?? 0),
                (string)($row['total_treinados'] ?? 0),
                number_format((float)($row['total_horas_treinamento'] ?? 0), 2, ',', '.'),
                number_format((float)($row['media_horas_por_colaborador'] ?? 0), 2, ',', '.'),
                number_format((float)($row['percentual_participacao'] ?? 0), 2, ',', '.') . '%',
            ];
        }, $dashboard['setores'] ?? []), '');

        $alerts = '';
        foreach (($dashboard['alertas_setor'] ?? []) as $row) {
            $alerts .= '<li>' . $this->e((string)($row['setor_nome'] ?? '')) . ': '
                . number_format((float)($row['percentual_participacao'] ?? 0), 2, ',', '.') . '% de participacao</li>';
        }
        if ($alerts === '') {
            $alerts = '<li>Nenhum alerta automatico no periodo selecionado.</li>';
        }

        $body = $this->filtersHtml($filters)
            . $cards
            . '<h3>Participacao por treinamento</h3>'
            . $participacaoTable
            . '<h3>Totalizadores por setor</h3>'
            . $setoresTable
            . '<h3>Alertas automaticos</h3><ul>' . $alerts . '</ul>';

        return $this->renderPdf($this->wrapHtml('Dashboard de Treinamentos', $body, [
            'header_subtitle' => 'Relatorio de acompanhamento percentual',
            'orientation' => 'landscape',
        ]), 'A4', 'landscape');
    }

    public function generateCertificateFile(array $treinamento, array $agenda, array $participant): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/pdfs/treinamentos/certificados';
        $backupDir = $dir . '/backup';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $fileName = sprintf(
            'certificado-agenda-%d-colaborador-%d.pdf',
            (int)$agenda['id'],
            (int)$participant['colaborador_id']
        );
        $path = $dir . '/' . $fileName;
        $backupPath = $backupDir . '/' . date('Ymd_His_') . $fileName;
        $pdf = $this->renderCertificatePdf($treinamento, $agenda, $participant);
        file_put_contents($path, $pdf);
        file_put_contents($backupPath, $pdf);
        return $path;
    }

    public function renderCertificatePdf(array $treinamento, array $agenda, array $participant): string
    {
        $numero = (string)($participant['certificado_numero'] ?? '');
        $codigo = (string)($participant['certificado_codigo'] ?? '');
        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Certificado de Participacao',
            'header_title' => 'Certificado',
            'header_subtitle' => (string)($treinamento['template_certificado'] ?? ''),
            'generated_at' => DateHelper::now(),
        ]);
        $logoUri = (string)($branding['logo_uri'] ?? '');
        $logo = $logoUri !== '' ? '<img src="' . $this->e($logoUri) . '" class="cert-logo" alt="Logo" />' : '';

        $quando = DateHelper::formatDateTime((string)($agenda['data'] ?? ''));
        if (!empty($agenda['data_fim'])) {
            $quando .= ' até ' . DateHelper::formatDateTime((string)($agenda['data_fim'] ?? ''));
        }
        $instrutor = (string)($agenda['instrutor'] ?: ($treinamento['assinatura_responsavel'] ?? $agenda['responsavel_nome'] ?? ''));
        $assinatura = (string)($treinamento['assinatura_responsavel'] ?? $agenda['responsavel_nome'] ?? 'Responsável');

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>'
            . '@page { margin: 0mm; }'
            . 'html,body{margin:0;padding:0;font-family:DejaVu Sans, Arial, sans-serif;color:#111827;}'
            . '.page{box-sizing:border-box;width:100%;height:100%;padding:14mm;}'
            . '.certificate{box-sizing:border-box;width:100%;height:100%;border:6px solid #7f1d1d;border-radius:18px;padding:14mm;text-align:center;background:#fff;page-break-inside:avoid;}'
            . '.top{display:flex;align-items:center;justify-content:center;gap:10mm;margin-bottom:6mm;}'
            . '.cert-logo{height:18mm;max-width:60mm;object-fit:contain;}'
            . '.certificate-title{font-size:34px;font-weight:bold;color:#7f1d1d;line-height:1.1;}'
            . '.certificate-subtitle{font-size:16px;margin-top:10mm;}'
            . '.certificate-name{font-size:26px;font-weight:bold;margin:10mm 0 8mm;color:#111827;}'
            . '.certificate-body{font-size:16px;line-height:1.6;margin:0 8mm;}'
            . '.certificate-meta{margin-top:8mm;font-size:12px;line-height:1.7;text-align:left;display:grid;grid-template-columns:repeat(2,1fr);gap:4mm 10mm;}'
            . '.signature{margin-top:10mm;display:flex;justify-content:center;}'
            . '.signature-line{border-top:1px solid #111827;width:90mm;padding-top:3mm;text-align:center;font-size:12px;}'
            . '</style></head><body>'
            . '<div class="page"><div class="certificate">'
            . '<div class="top">' . $logo . '<div class="certificate-title">Certificado</div></div>'
            . '<div class="certificate-subtitle">Certificamos que</div>'
            . '<div class="certificate-name">' . $this->e((string)($participant['colaborador_nome'] ?? '')) . '</div>'
            . '<div class="certificate-body">'
            . 'participou do treinamento <strong>' . $this->e((string)($agenda['treinamento_nome'] ?? '')) . '</strong>, '
            . 'com carga horária de <strong>' . $this->e((string)($agenda['carga_horaria'] ?? '0')) . ' hora(s)</strong>, '
            . 'previsto para <strong>' . $this->e($quando) . '</strong>.'
            . '</div>'
            . '<div class="certificate-meta">'
            . '<div><strong>Instrutor/Responsável:</strong> ' . $this->e($instrutor) . '</div>'
            . '<div><strong>Número:</strong> ' . $this->e($numero) . '</div>'
            . '<div><strong>Código de autenticação:</strong> ' . $this->e($codigo) . '</div>'
            . '<div><strong>Gerado em:</strong> ' . $this->e((string)($branding['generated_at'] ?? '')) . '</div>'
            . '</div>'
            . '<div class="signature"><div class="signature-line">' . $this->e($assinatura) . '</div></div>'
            . '</div></div>'
            . '</body></html>';

        return $this->renderPdf($html, 'A4', 'landscape');
    }

    private function summaryCard(string $label, string $value): string
    {
        return '<div class="summary-card"><div class="summary-label">' . $this->e($label) . '</div><div class="summary-value">' . $this->e($value) . '</div></div>';
    }

    private function filtersHtml(array $filters): string
    {
        $items = [];
        foreach ($filters as $key => $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $items[] = '<span><strong>' . $this->e(str_replace('_', ' ', ucfirst($key))) . ':</strong> ' . $this->e($value) . '</span>';
            }
        }
        if (empty($items)) {
            return '<p class="muted">Sem filtros adicionais aplicados.</p>';
        }
        return '<div class="filters-box">' . implode(' ', $items) . '</div>';
    }

    private function tableHtml(array $headers, array $rows, string $footer): string
    {
        $thead = '';
        foreach ($headers as $header) {
            $thead .= '<th>' . $this->e($header) . '</th>';
        }
        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $cell) {
                $tbody .= '<td>' . $this->e((string)$cell) . '</td>';
            }
            $tbody .= '</tr>';
        }
        if ($tbody === '') {
            $tbody = '<tr><td colspan="' . count($headers) . '">Nenhum registro encontrado.</td></tr>';
        }
        return '<table><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table>'
            . ($footer !== '' ? '<p class="muted">' . $this->e($footer) . '</p>' : '');
    }

    private function wrapHtml(string $title, string $body, array $options = []): string
    {
        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', array_merge([
            'report_title' => $title,
            'header_title' => $title,
            'header_subtitle' => '',
            'generated_at' => DateHelper::now(),
            'margins' => ['top' => 14, 'right' => 12, 'bottom' => 14, 'left' => 12],
        ], $options));

        $logo = $branding['logo_uri'] !== '' ? '<img src="' . $this->e($branding['logo_uri']) . '" class="logo" alt="Logo" />' : '';
        return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>'
            . '@page { margin: ' . (int)$branding['margins']['top'] . 'mm ' . (int)$branding['margins']['right'] . 'mm ' . (int)$branding['margins']['bottom'] . 'mm ' . (int)$branding['margins']['left'] . 'mm; }'
            . 'body{font-family:DejaVu Sans, Arial, sans-serif;color:#1f2937;font-size:12px;}'
            . 'h1{font-size:24px;margin:0 0 4px;}h2,h3{margin:16px 0 8px;font-size:16px;color:#7f1d1d;}'
            . 'p{margin:6px 0;}table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border:1px solid #d1d5db;padding:8px;vertical-align:top;}'
            . 'th{background:#fee2e2;color:#7f1d1d;font-size:11px;text-transform:uppercase;}'
            . '.header{display:flex;align-items:center;gap:18px;margin-bottom:18px;border-bottom:2px solid #e5e7eb;padding-bottom:12px;}'
            . '.logo{height:54px;max-width:140px;object-fit:contain;}.muted{color:#6b7280;font-size:11px;}'
            . '.filters-box{background:#f9fafb;border:1px solid #e5e7eb;padding:10px;border-radius:8px;margin:10px 0 14px;}'
            . '.filters-box span{display:inline-block;margin-right:12px;margin-bottom:6px;}'
            . '.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0 20px;}'
            . '.summary-card{border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff7ed;}'
            . '.summary-label{font-size:11px;color:#6b7280;text-transform:uppercase;}.summary-value{font-size:24px;font-weight:bold;color:#7f1d1d;}'
            . '.signature-zone{margin-top:22px;}.signature-line{margin-top:34px;padding-top:8px;border-top:1px solid #111827;width:260px;text-align:center;}'
            . '.certificate{border:6px solid #7f1d1d;border-radius:20px;padding:34px;min-height:420px;text-align:center;background:#fff;}'
            . '.certificate-title{font-size:38px;font-weight:bold;color:#7f1d1d;margin-top:6px;}'
            . '.certificate-subtitle{font-size:18px;margin-top:28px;}.certificate-name{font-size:30px;font-weight:bold;margin:28px 0;color:#111827;}'
            . '.certificate-body{font-size:18px;line-height:1.7;margin:24px 30px;}.certificate-meta{margin-top:26px;font-size:13px;line-height:1.8;}'
            . '.meta-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:14px;}.print-card{padding-left:4mm;}'
            . '</style></head><body>'
            . '<div class="header">' . $logo . '<div><h1>' . $this->e($branding['header_title']) . '</h1><p class="muted">'
            . $this->e((string)$branding['header_subtitle']) . ' • Gerado em ' . $this->e((string)$branding['generated_at']) . '</p></div></div>'
            . $body
            . '</body></html>';
    }

    private function renderPdf(string $html, string $paper = 'A4', string $orientation = 'portrait', bool $phpEnabled = false): string
    {
        if (!PdfSupport::isDompdfAvailable()) {
            throw new \RuntimeException('Dependência Dompdf indisponível.');
        }
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', $phpEnabled);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        return $dompdf->output();
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
