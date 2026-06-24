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
            'header_title' => 'Relatório de Auditoria',
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
        $this->writeColoredText($x, $y, $text, $size, $bold, [17, 24, 39]);
    }

    private function writeColoredText(float $x, float $y, string $text, int $size = 10, bool $bold = false, array $rgb = [17, 24, 39]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = self::escapePdfText($text);
        $color = $this->pdfRgb($rgb);
        $cmd = "{$color} rg BT /{$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$safe}) Tj ET\n";
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
        $this->writeColoredText($this->marginX, $this->cursorY, $title, 13, true, [17, 24, 39]);
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

    private function drawRect(float $x, float $topY, float $w, float $h, array $fillRgb = [255, 255, 255], array $strokeRgb = [226, 232, 240], float $lineWidth = 1.0): void
    {
        $fill = $this->pdfRgb($fillRgb);
        $stroke = $this->pdfRgb($strokeRgb);
        $bottomY = $topY - $h;
        $page = $this->page();
        $page['content'] .= "{$lineWidth} w {$fill} rg {$stroke} RG {$x} {$bottomY} {$w} {$h} re B\n";
        $this->setPage($page);
    }

    private function drawPill(float $x, float $y, string $text, array $fillRgb, array $textRgb): float
    {
        $text = trim($text);
        if ($text === '') {
            return 0.0;
        }
        $width = $this->estimateWidth($text, 9) + 12.0;
        $height = 16.0;
        $this->drawRect($x, $y + 4.0, $width, $height, $fillRgb, $fillRgb, 0.6);
        $this->writeColoredText($x + 6.0, $y - 7.0, $text, 9, true, $textRgb);
        return $width;
    }

    private function drawDivider(float $y): void
    {
        $page = $this->page();
        $x1 = $this->marginX;
        $x2 = $this->pageWidth - $this->marginX;
        $page['content'] .= "0.6 w " . $this->pdfRgb([226, 232, 240]) . " RG {$x1} {$y} m {$x2} {$y} l S\n";
        $this->setPage($page);
    }

    private function drawHeader(array $data): void
    {
        $showLogo = !array_key_exists('show_logo', $data) || !empty($data['show_logo']);
        $logoW = 0.0;
        if ($showLogo && !empty($data['logo_path']) && is_file((string)$data['logo_path'])) {
            $im = $this->registerImage((string)$data['logo_path']);
            if ($im) {
                $targetW = max(48.0, (float)($data['logo_width'] ?? 96));
                $targetH = $targetW * ($im['h'] / max(1.0, $im['w']));
                $imageX = ($data['logo_position'] ?? 'left') === 'right'
                    ? ($this->pageWidth - $this->marginX - $targetW)
                    : $this->marginX;
                $this->placeImage($im['name'], $imageX, $this->cursorY - $targetH + 8, $targetW, $targetH);
                $logoW = $targetW + 12.0;
            }
        }
        $eyebrow = trim((string)($data['header_title'] ?? ''));
        $title = (string)($data['report_title'] ?? 'Relatório de Auditoria');
        $textX = ($data['logo_position'] ?? 'left') === 'right' ? $this->marginX : ($this->marginX + $logoW);
        $availableWidth = max(120.0, $this->pageWidth - $textX - $this->marginX);
        if ($eyebrow !== '' && mb_strtolower($eyebrow, 'UTF-8') !== mb_strtolower($title, 'UTF-8')) {
            foreach ($this->wrapText($eyebrow, 8, $availableWidth) as $line) {
                $this->writeColoredText($textX, $this->cursorY, $line, 8, false, [100, 116, 139]);
                $this->cursorY -= 11;
            }
            $this->cursorY -= 4;
        }
        foreach ($this->wrapText($title, 18, $availableWidth) as $line) {
            $this->writeColoredText($textX, $this->cursorY, $line, 18, true, [17, 24, 39]);
            $this->cursorY -= 22;
        }
        if (!empty($data['header_subtitle'])) {
            $this->cursorY -= 2;
            foreach ($this->wrapText((string)$data['header_subtitle'], 10, $availableWidth) as $line) {
                $this->writeColoredText($textX, $this->cursorY, $line, 10, false, [100, 116, 139]);
                $this->cursorY -= 13;
            }
        }
        $meta = 'Gerado em ' . (string)($data['generated_at'] ?? DateHelper::now());
        $this->cursorY -= 4;
        $this->writeColoredText($textX, $this->cursorY, $meta, 9, false, [100, 116, 139]);
        $this->cursorY -= 18;
        $this->drawDivider($this->cursorY + 6);
        $this->cursorY -= 16;
    }

    private function drawSummary(array $data): void
    {
        $summarySections = is_array($data['summary_sections'] ?? null) ? $data['summary_sections'] : [];
        if (!empty($summarySections)) {
            foreach ($summarySections as $section) {
                $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                if (empty($items)) {
                    continue;
                }
                $columns = max(1, min(4, (int)($section['columns'] ?? count($items))));
                $this->drawCardGrid($items, $columns);
                $this->cursorY -= 12;
            }

            $detailRows = is_array($data['detail_rows'] ?? null) ? $data['detail_rows'] : [];
            if (!empty($detailRows)) {
                $items = array_map(static function (array $row): array {
                    return [
                        'label' => (string)($row['label'] ?? ''),
                        'value' => (string)($row['value'] ?? ''),
                    ];
                }, $detailRows);
                $this->drawCardGrid($items, 2);
                $this->cursorY -= 12;
            }
            return;
        }

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
            $rows = is_array($q['rows'] ?? null) ? $q['rows'] : [];
            $images = is_array($q['images'] ?? null) ? $q['images'] : [];
            $needed = $this->estimateQuestionCardHeight($title, $rows, $images);
            $this->ensureSpace($needed + 10);
            $topY = $this->cursorY;
            $cardW = $this->pageWidth - ($this->marginX * 2);
            $this->drawRect($this->marginX, $topY, $cardW, $needed, [255, 255, 255], [226, 232, 240], 0.8);
            $innerX = $this->marginX + 14;
            $innerY = $topY - 18;

            $this->writeColoredText($innerX, $innerY, 'Questão ' . $qn, 9, false, [100, 116, 139]);
            $innerY -= 18;
            foreach ($this->wrapText($title, 12, $cardW - 28) as $line) {
                $this->writeColoredText($innerX, $innerY, $line, 12, true, [17, 24, 39]);
                $innerY -= 15;
            }
            $innerY -= 2;

            foreach ($rows as $rk => $rv) {
                $rvLines = $this->wrapText((string)$rv, 10, $cardW - 150);
                $this->writeColoredText($innerX, $innerY, (string)$rk . ':', 10, true, [17, 24, 39]);
                foreach ($rvLines as $idx => $line) {
                    $this->writeColoredText($innerX + 112, $innerY - ($idx * 13), $line, 10, false, [31, 41, 55]);
                }
                $innerY -= max(16.0, count($rvLines) * 13.0);
            }

            if (!empty($images)) {
                $innerY -= 2;
                $this->writeColoredText($innerX, $innerY, 'Anexos', 10, true, [17, 24, 39]);
                $innerY -= 14;
                $this->drawQuestionImages($images, $innerX, $innerY, $cardW - 28);
            }

            $this->cursorY -= ($needed + 10);
            $qn++;
        }
    }

    private function drawCardGrid(array $items, int $columns = 4): void
    {
        $width = $this->pageWidth - ($this->marginX * 2);
        $gap = 12.0;
        $columnW = ($width - ($gap * ($columns - 1))) / $columns;
        $layout = $this->measureGridLayout($items, $columns, $columnW);
        $height = $layout['height'];
        $this->ensureSpace($height + 2);
        $topY = $this->cursorY;
        $this->drawRect($this->marginX, $topY, $width, $height, [255, 255, 255], [226, 232, 240], 0.8);

        foreach ($items as $idx => $item) {
            $col = $idx % $columns;
            $row = intdiv($idx, $columns);
            $rowStart = $layout['padding_top'];
            for ($i = 0; $i < $row; $i++) {
                $rowStart += $layout['row_heights'][$i] + $layout['row_gap'];
            }
            $x = $this->marginX + 14 + ($col * ($columnW + $gap));
            $y = $topY - $rowStart;
            $label = (string)($item['label'] ?? '');
            $value = (string)($item['value'] ?? '');
            $this->writeColoredText($x, $y, $label, 8, false, [100, 116, 139]);
            $badge = trim((string)($item['badge'] ?? ''));
            if ($badge !== '') {
                $badgeFill = is_array($item['badge_fill'] ?? null) ? $item['badge_fill'] : [254, 226, 226];
                $badgeText = is_array($item['badge_text'] ?? null) ? $item['badge_text'] : [153, 27, 27];
                $this->drawPill($x, $y - 18, $badge, $badgeFill, $badgeText);
            } else {
                $valueLines = $this->wrapText($value, 11, $columnW - 18);
                foreach ($valueLines as $lineIdx => $line) {
                    $this->writeColoredText($x, $y - 18 - ($lineIdx * 14), $line, 11, true, [17, 24, 39]);
                }
            }
        }

        $this->cursorY -= ($height + 2);
    }

    private function estimateSectionHeight(array $items, float $columnW): float
    {
        return $this->measureGridLayout($items, max(1, count($items)), $columnW)['height'];
    }

    private function estimateQuestionCardHeight(string $title, array $rows, array $images): float
    {
        $cardW = $this->pageWidth - ($this->marginX * 2) - 28;
        $height = 34.0;
        $height += count($this->wrapText($title, 12, $cardW)) * 15.0;
        foreach ($rows as $label => $value) {
            $lines = $this->wrapText((string)$value, 10, $cardW - 112);
            $height += max(16.0, count($lines) * 13.0);
        }
        if (!empty($images)) {
            $height += 22.0 + $this->estimateImagesGridHeight($images, $cardW);
        }
        return $height + 16.0;
    }

    private function estimateImagesGridHeight(array $images, float $availableWidth): float
    {
        $cols = min(3, max(1, count($images)));
        $gap = 10.0;
        $thumbW = ($availableWidth - ($gap * ($cols - 1))) / $cols;
        $thumbH = min(120.0, $thumbW * 0.6);
        $rows = (int)ceil(count($images) / $cols);
        return ($rows * ($thumbH + 22.0)) + (($rows - 1) * 8.0);
    }

    private function drawQuestionImages(array $images, float $x, float $topY, float $availableWidth): void
    {
        $cols = min(3, max(1, count($images)));
        $gap = 10.0;
        $thumbW = ($availableWidth - ($gap * ($cols - 1))) / $cols;
        $thumbH = min(120.0, $thumbW * 0.6);
        foreach (array_values($images) as $idx => $image) {
            $col = $idx % $cols;
            $row = intdiv($idx, $cols);
            $cardX = $x + ($col * ($thumbW + $gap));
            $cardTop = $topY - ($row * ($thumbH + 30.0));
            $this->drawRect($cardX, $cardTop, $thumbW, $thumbH, [248, 250, 252], [226, 232, 240], 0.6);

            $path = '';
            $label = '';
            if (is_array($image)) {
                $path = (string)($image['path'] ?? '');
                $label = (string)($image['label'] ?? '');
            } else {
                $path = (string)$image;
            }
            $im = $this->registerImage($path);
            if ($im) {
                $scale = min($thumbW / max(1.0, $im['w']), $thumbH / max(1.0, $im['h']));
                $w = $im['w'] * $scale;
                $h = $im['h'] * $scale;
                $imgX = $cardX + (($thumbW - $w) / 2);
                $imgY = ($cardTop - (($thumbH - $h) / 2)) - $h;
                $this->placeImage($im['name'], $imgX, $imgY, $w, $h);
            }

            if ($label !== '') {
                $labelLines = $this->wrapText($label, 8, $thumbW);
                foreach (array_slice($labelLines, 0, 2) as $lineIdx => $line) {
                    $this->writeColoredText($cardX, $cardTop - $thumbH - 10 - ($lineIdx * 11), $line, 8, false, [100, 116, 139]);
                }
            }
        }
    }

    private function measureGridLayout(array $items, int $columns, float $columnW): array
    {
        $columns = max(1, $columns);
        $paddingTop = 18.0;
        $paddingBottom = 16.0;
        $rowGap = 12.0;
        $rows = (int)max(1, ceil(max(1, count($items)) / $columns));
        $rowHeights = array_fill(0, $rows, 0.0);
        foreach ($items as $idx => $item) {
            $row = intdiv($idx, $columns);
            $rowHeights[$row] = max($rowHeights[$row], $this->measureGridCellHeight($item, $columnW));
        }
        foreach ($rowHeights as $idx => $height) {
            if ($height <= 0.0) {
                $rowHeights[$idx] = 42.0;
            }
        }
        $height = $paddingTop + array_sum($rowHeights) + (($rows - 1) * $rowGap) + $paddingBottom;
        return [
            'height' => max(58.0, $height),
            'row_heights' => $rowHeights,
            'padding_top' => $paddingTop,
            'padding_bottom' => $paddingBottom,
            'row_gap' => $rowGap,
        ];
    }

    private function measureGridCellHeight(array $item, float $columnW): float
    {
        $labelHeight = 12.0;
        $contentTopGap = 18.0;
        $bottomPadding = 6.0;
        if (!empty($item['badge'])) {
            return $labelHeight + $contentTopGap + 16.0 + $bottomPadding;
        }
        $lines = $this->wrapText((string)($item['value'] ?? ''), 11, max(60.0, $columnW - 18));
        return $labelHeight + $contentTopGap + (max(1, count($lines)) * 14.0) + $bottomPadding;
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
        $producer = self::escapePdfText('SIS+ PDF UTF-8');
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
        $value = self::repairMojibake(self::normalizeUtf8($value));
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

    private static function repairMojibake(string $value): string
    {
        if ($value === '' || !preg_match('/Ã.|Â.|â[\x80-\xBF]/u', $value)) {
            return $value;
        }

        $latin = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if ($latin === false || $latin === '') {
            return $value;
        }

        $repaired = @mb_convert_encoding($latin, 'UTF-8', 'Windows-1252');
        if (!is_string($repaired) || $repaired === '') {
            return $value;
        }

        $badBefore = preg_match_all('/Ã.|Â.|â[\x80-\xBF]/u', $value) ?: 0;
        $badAfter = preg_match_all('/Ã.|Â.|â[\x80-\xBF]/u', $repaired) ?: 0;
        return $badAfter < $badBefore ? $repaired : $value;
    }

    private function pdfRgb(array $rgb): string
    {
        $r = max(0.0, min(1.0, ((float)($rgb[0] ?? 0)) / 255));
        $g = max(0.0, min(1.0, ((float)($rgb[1] ?? 0)) / 255));
        $b = max(0.0, min(1.0, ((float)($rgb[2] ?? 0)) / 255));
        return sprintf('%.3F %.3F %.3F', $r, $g, $b);
    }
}

