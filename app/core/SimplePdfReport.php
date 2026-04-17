<?php
namespace App\Core;

class SimplePdfReport
{
    private array $branding = [];
    private array $pages = [];
    private int $currentPage = -1;
    private float $cursorY = 0.0;
    private array $images = [];
    private int $imageSeq = 0;
    private float $pageWidth = 595.0;
    private float $pageHeight = 842.0;
    private float $marginX = 40.0;
    private float $topY = 800.0;
    private float $bottomY = 60.0;

    public static function fromLines(array $lines): string
    {
        $data = [
            'report_title' => 'Relatório de Auditoria',
            'generated_at' => DateHelper::now(),
            'version' => 'v1.0',
            'header' => [],
            'questions' => [],
        ];
        foreach ($lines as $l) {
            $data['questions'][] = [
                'title' => (string)$l,
                'rows' => [],
                'images' => [],
            ];
        }
        return self::fromAudit($data);
    }

    public static function fromAudit(array $data): string
    {
        $data = ReportBranding::aplicarBrandingRelatorio('pdf', $data);
        $pdf = new self();
        $pdf->branding = $data;
        $pdf->applyBrandingConfig($data);
        $pdf->addPage();
        $pdf->drawHeader($data);
        $pdf->drawSummary($data);
        $pdf->drawQuestions($data);
        return $pdf->build($data);
    }

    private function addPage(): void
    {
        $this->pages[] = ['content' => '', 'xobjects' => []];
        $this->currentPage = count($this->pages) - 1;
        $this->cursorY = $this->topY;
    }

    private function page(): array
    {
        return $this->pages[$this->currentPage];
    }

    private function setPage(array $page): void
    {
        $this->pages[$this->currentPage] = $page;
    }

    private function writeText(float $x, float $y, string $text, int $size = 10, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = self::escapePdfText($text);
        $cmd = "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$safe}) Tj ET\n";
        $page = $this->page();
        $page['content'] .= $cmd;
        $this->setPage($page);
    }

    private function applyBrandingConfig(array $data): void
    {
        $margins = is_array($data['margins'] ?? null) ? $data['margins'] : [];
        $this->marginX = (float)(((int)($margins['left'] ?? 12)) * 2.83465);
        $topMargin = (float)(((int)($margins['top'] ?? 16)) * 2.83465);
        $bottomMargin = (float)(((int)($margins['bottom'] ?? 14)) * 2.83465);
        $this->topY = $this->pageHeight - $topMargin;
        $this->bottomY = max(32.0, $bottomMargin + 18.0);
    }

    private function lineHeight(int $size): float
    {
        return max(12.0, $size + 4.0);
    }

    private function ensureSpace(float $needed): void
    {
        if (($this->cursorY - $needed) < $this->bottomY) {
            $this->addPage();
        }
    }

    private function wrapText(string $text, int $size, float $maxWidth): array
    {
        $text = trim(self::normalizeUtf8($text));
        if ($text === '') return [''];
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $w) {
            $candidate = trim($line === '' ? $w : ($line . ' ' . $w));
            if ($this->estimateWidth($candidate, $size) <= $maxWidth) {
                $line = $candidate;
            } else {
                if ($line !== '') $lines[] = $line;
                $line = $w;
            }
        }
        if ($line !== '') $lines[] = $line;
        return $lines ?: [''];
    }

    private function estimateWidth(string $text, int $size): float
    {
        return mb_strlen($text, 'UTF-8') * ($size * 0.52);
    }

    private function drawBlockTitle(string $title): void
    {
        $this->ensureSpace(26);
        $this->writeText($this->marginX, $this->cursorY, $title, 12, true);
        $this->cursorY -= 18;
    }

    private function drawRow(string $label, string $value): void
    {
        $max = $this->pageWidth - ($this->marginX * 2) - 130;
        $wrapped = $this->wrapText($value, 10, $max);
        $height = max(18, count($wrapped) * $this->lineHeight(10));
        $this->ensureSpace($height + 4);
        $y = $this->cursorY;
        $this->writeText($this->marginX, $y, $label, 10, true);
        foreach ($wrapped as $i => $line) {
            $this->writeText($this->marginX + 130, $y - ($i * $this->lineHeight(10)), $line, 10, false);
        }
        $this->cursorY -= ($height + 2);
    }

    private function drawHeader(array $data): void
    {
        $logoW = 0.0;
        if (!empty($data['logo_path']) && is_file((string)$data['logo_path'])) {
            $im = $this->registerImage((string)$data['logo_path']);
            if ($im) {
                $targetW = max(56.0, (float)($data['logo_width'] ?? 96));
                $targetH = $targetW * ($im['h'] / max(1.0, $im['w']));
                $imageX = ($data['logo_position'] ?? 'left') === 'right'
                    ? ($this->pageWidth - $this->marginX - $targetW)
                    : $this->marginX;
                $this->placeImage($im['name'], $imageX, $this->cursorY - $targetH + 8, $targetW, $targetH);
                $logoW = $targetW + 12.0;
            }
        }
        $title = (string)($data['report_title'] ?? 'Relatório de Auditoria');
        $textX = ($data['logo_position'] ?? 'left') === 'right' ? $this->marginX : ($this->marginX + $logoW);
        $this->writeText($textX, $this->cursorY, $title, 16, true);
        if (!empty($data['header_subtitle'])) {
            $this->writeText($textX, $this->cursorY - 14, (string)$data['header_subtitle'], 10, false);
        }
        $meta = 'Gerado em ' . (string)($data['generated_at'] ?? DateHelper::now());
        $metaY = !empty($data['header_subtitle']) ? $this->cursorY - 28 : $this->cursorY - 16;
        $this->writeText($textX, $metaY, $meta, 10, false);
        $this->cursorY -= !empty($data['header_subtitle']) ? 44 : 34;
    }

    private function drawSummary(array $data): void
    {
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        if (empty($header)) return;
        $this->drawBlockTitle('Dados da Auditoria');
        foreach ($header as $k => $v) {
            $this->drawRow((string)$k, (string)$v);
        }
        $this->cursorY -= 8;
    }

    private function drawQuestions(array $data): void
    {
        $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];
        if (empty($questions)) return;
        $this->drawBlockTitle('Respostas da Auditoria');
        $qn = 1;
        foreach ($questions as $q) {
            $title = (string)($q['title'] ?? ('Questão ' . $qn));
            $this->drawBlockTitle('Questão ' . $qn);
            $lines = $this->wrapText($title, 11, $this->pageWidth - ($this->marginX * 2));
            foreach ($lines as $line) {
                $this->ensureSpace(16);
                $this->writeText($this->marginX, $this->cursorY, $line, 11, true);
                $this->cursorY -= 14;
            }
            $rows = is_array($q['rows'] ?? null) ? $q['rows'] : [];
            foreach ($rows as $rk => $rv) {
                $this->drawRow((string)$rk, (string)$rv);
            }
            $images = is_array($q['images'] ?? null) ? $q['images'] : [];
            if (!empty($images)) {
                $this->drawRow('Anexos', 'Imagens anexadas: ' . count($images));
                foreach ($images as $imgPath) {
                    $im = $this->registerImage((string)$imgPath);
                    if (!$im) continue;
                    $maxW = 220.0;
                    $w = min($maxW, $im['w']);
                    $scale = $w / max(1.0, $im['w']);
                    $h = $im['h'] * $scale;
                    $this->ensureSpace($h + 22);
                    $x = $this->marginX + 130;
                    $y = $this->cursorY - $h + 6;
                    $this->placeImage($im['name'], $x, $y, $w, $h);
                    $this->cursorY -= ($h + 12);
                }
            }
            $this->cursorY -= 8;
            $qn++;
        }
    }

    private function placeImage(string $name, float $x, float $y, float $w, float $h): void
    {
        $page = $this->page();
        $page['content'] .= "q {$w} 0 0 {$h} {$x} {$y} cm /{$name} Do Q\n";
        $page['xobjects'][$name] = true;
        $this->setPage($page);
    }

    private function registerImage(string $path): ?array
    {
        if (!is_file($path)) return null;
        $real = realpath($path) ?: $path;
        if (isset($this->images[$real])) return $this->images[$real];
        $info = @\getimagesize($real);
        if (!$info || empty($info[0]) || empty($info[1])) return null;
        $mime = strtolower((string)($info['mime'] ?? ''));
        $binary = @\file_get_contents($real);
        $w = (float)$info[0];
        $h = (float)$info[1];
        if ($binary === false) return null;
        if ($mime !== 'image/jpeg') {
            $im = null;
            $canGd = \function_exists('imagejpeg');
            if (!$canGd) return null;
            if ($mime === 'image/png' && \function_exists('imagecreatefrompng')) $im = @\imagecreatefrompng($real);
            elseif ($mime === 'image/gif' && \function_exists('imagecreatefromgif')) $im = @\imagecreatefromgif($real);
            elseif ($mime === 'image/webp' && \function_exists('imagecreatefromwebp')) $im = @\imagecreatefromwebp($real);
            if (!$im) return null;
            \ob_start();
            \imagejpeg($im, null, 90);
            $binary = (string)\ob_get_clean();
            \imagedestroy($im);
            $mime = 'image/jpeg';
        }
        $name = 'Im' . (++$this->imageSeq);
        $this->images[$real] = ['name' => $name, 'mime' => $mime, 'w' => $w, 'h' => $h, 'data' => $binary];
        return $this->images[$real];
    }

    private function build(array $data): string
    {
        $generated = (string)($data['generated_at'] ?? DateHelper::now());
        $footerText = (string)($data['footer_text'] ?? '');
        $pageCount = count($this->pages);
        for ($i = 0; $i < $pageCount; $i++) {
            $footer = ReportBranding::footerLabel($i + 1, $pageCount, $generated, $footerText);
            $safe = self::escapePdfText($footer);
            $this->pages[$i]['content'] .= "BT /F1 9 Tf 1 0 0 1 {$this->marginX} 28 Tm ({$safe}) Tj ET\n";
        }

        $objects = [];
        $pageObjIds = [];
        $contentObjIds = [];
        $fontRegularId = 0;
        $fontBoldId = 0;
        $imgObjIds = [];

        $fontRegularId = 4;
        $fontBoldId = 5;
        $nextId = 6;
        foreach ($this->images as $img) {
            $imgObjIds[$img['name']] = $nextId++;
        }
        foreach ($this->pages as $_) {
            $pageObjIds[] = $nextId++;
            $contentObjIds[] = $nextId++;
        }

        $objects[1] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $kids = implode(' ', array_map(fn($id)=>$id . ' 0 R', $pageObjIds));
        $objects[2] = "2 0 obj << /Type /Pages /Kids [{$kids}] /Count " . count($pageObjIds) . " >> endobj";
        $title = self::escapePdfText((string)($data['report_title'] ?? 'Relatório'));
        $producer = self::escapePdfText('SisDoná PDF UTF-8');
        $objects[3] = "3 0 obj << /Title ({$title}) /Producer ({$producer}) >> endobj";
        $objects[$fontRegularId] = "{$fontRegularId} 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >> endobj";
        $objects[$fontBoldId] = "{$fontBoldId} 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >> endobj";

        foreach ($this->images as $img) {
            $id = $imgObjIds[$img['name']];
            $len = strlen($img['data']);
            $objects[$id] = "{$id} 0 obj << /Type /XObject /Subtype /Image /Width " . (int)$img['w'] . " /Height " . (int)$img['h'] . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$len} >> stream\n" . $img['data'] . "\nendstream endobj";
        }

        foreach ($this->pages as $idx => $page) {
            $pageId = $pageObjIds[$idx];
            $contentId = $contentObjIds[$idx];
            $xobjs = '';
            foreach (array_keys($page['xobjects']) as $xname) {
                if (isset($imgObjIds[$xname])) $xobjs .= "/{$xname} {$imgObjIds[$xname]} 0 R ";
            }
            $resources = "/Font << /F1 {$fontRegularId} 0 R /F2 {$fontBoldId} 0 R >>";
            if ($xobjs !== '') $resources .= " /XObject << {$xobjs}>>";
            $objects[$pageId] = "{$pageId} 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Resources << {$resources} >> /Contents {$contentId} 0 R >> endobj";
            $len = strlen($page['content']);
            $objects[$contentId] = "{$contentId} 0 obj << /Length {$len} >> stream\n{$page['content']}endstream endobj";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $obj . "\n";
        }
        $xrefPos = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $off = $offsets[$i] ?? 0;
            $pdf .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size {$size} /Root 1 0 R /Info 3 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";
        return $pdf;
    }

    private static function escapePdfText(string $value): string
    {
        $value = self::normalizeUtf8($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[^\P{C}\n]/u', '', $value) ?? $value;
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
        return strtr($value, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }

    private static function normalizeUtf8(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
