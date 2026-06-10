<?php
namespace App\Core;

class XlsxExport
{
    public static function exportPlanos(array $rows, string $filename): string
    {
        $branding = ReportBranding::aplicarBrandingRelatorio('excel', [
            'report_title' => 'Planos de AÃ§Ã£o',
            'header_title' => 'Planos de AÃ§Ã£o',
            'header_subtitle' => 'ExportaÃ§Ã£o padronizada do sistema',
            'generated_at' => DateHelper::now(),
            'sheet_name' => 'Planos',
        ]);
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('NÃ£o foi possÃ­vel criar arquivo XLSX');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('docProps/app.xml', self::appXml());
        $zip->addFromString('docProps/core.xml', self::coreXml($branding));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($branding));
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet1($rows, $branding));

        $zip->close();
        return $path;
    }

    public static function exportRows(array $rows, array $columns, string $filename, array $branding = []): string
    {
        $branding = ReportBranding::aplicarBrandingRelatorio('excel', array_merge([
            'report_title' => 'RelatÃ³rio',
            'header_title' => 'RelatÃ³rio',
            'header_subtitle' => 'ExportaÃ§Ã£o padronizada do sistema',
            'generated_at' => DateHelper::now(),
            'sheet_name' => 'Relatorio',
        ], $branding));
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('NÃ£o foi possÃ­vel criar arquivo XLSX');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('docProps/app.xml', self::appXml());
        $zip->addFromString('docProps/core.xml', self::coreXml($branding));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($branding));
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::genericSheet($rows, $columns, $branding));

        $zip->close();
        return $path;
    }

    public static function exportTemplateWorkbook(array $sheets, string $filename, array $branding = []): string
    {
        $sheets = array_values(array_filter($sheets, static fn($s): bool => is_array($s) && !empty($s['columns'])));
        if (empty($sheets)) {
            throw new \RuntimeException('Nenhuma planilha informada para exportação.');
        }

        $sheetNames = [];
        foreach ($sheets as $i => $sheet) {
            $name = trim((string)($sheet['name'] ?? ''));
            if ($name === '') {
                $name = 'Sheet' . ($i + 1);
            }
            $sheetNames[] = mb_substr($name, 0, 31);
        }

        $branding = ReportBranding::aplicarBrandingRelatorio('excel', array_merge([
            'report_title' => 'Modelo de Importação',
            'generated_at' => DateHelper::now(),
        ], $branding));

        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $entries = [
            '[Content_Types].xml' => self::contentTypesForSheets(count($sheetNames)),
            '_rels/.rels' => self::rels(),
            'docProps/app.xml' => self::appXml(),
            'docProps/core.xml' => self::coreXml($branding),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsForSheets(count($sheetNames)),
            'xl/workbook.xml' => self::workbookForSheets($sheetNames),
            'xl/styles.xml' => self::styles(),
        ];
        foreach ($sheets as $i => $sheet) {
            $sheetIndex = $i + 1;
            $columns = (array)$sheet['columns'];
            $rows = is_array($sheet['rows'] ?? null) ? $sheet['rows'] : [];
            $entries['xl/worksheets/sheet' . $sheetIndex . '.xml'] = self::templateSheet($rows, $columns);
        }

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Não foi possível criar arquivo XLSX');
            }
            foreach ($entries as $name => $data) {
                $zip->addFromString((string)$name, (string)$data);
            }
            $zip->close();
        } else {
            self::zipWriteStored($path, $entries);
        }
        return $path;
    }

    private static function zipWriteStored(string $path, array $entries): void
    {
        $offset = 0;
        $cd = '';
        $out = '';
        $now = getdate();
        $dosTime = (($now['hours'] ?? 0) << 11) | (($now['minutes'] ?? 0) << 5) | (int)(($now['seconds'] ?? 0) / 2);
        $dosDate = ((($now['year'] ?? 1980) - 1980) << 9) | (($now['mon'] ?? 1) << 5) | ($now['mday'] ?? 1);

        foreach ($entries as $name => $data) {
            $name = str_replace('\\', '/', (string)$name);
            $data = (string)$data;
            $crc = crc32($data);
            $size = strlen($data);
            $nameLen = strlen($name);

            $lh = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0);
            $out .= $lh . $name . $data;

            $cdh = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0, 0, 0, 0, 0, $offset);
            $cd .= $cdh . $name;

            $offset += strlen($lh) + $nameLen + $size;
        }

        $out .= $cd;
        $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($cd), $offset, 0);
        $out .= $eocd;
        file_put_contents($path, $out);
    }

    private static function esc($s): string
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
    }

    private static function contentTypesForSheets(int $count): string
    {
        $count = max(1, (int)$count);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        for ($i = 1; $i <= $count; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        $xml .= '</Types>';
        return $xml;
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    }

    private static function appXml(): string
    {
        $appName = self::esc(AppBrand::EXPORT_APP);
        return '<?xml version="1.0" encoding="UTF-8"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>' . $appName . '</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <Company></Company>
  <LinksUpToDate>false</LinksUpToDate>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>16.0300</AppVersion>
</Properties>';
    }

    private static function coreXml(array $branding): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $creator = self::esc(AppBrand::displayName());
        return '<?xml version="1.0" encoding="UTF-8"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>' . self::esc($branding['report_title'] ?? 'RelatÃ³rio') . '</dc:title>
  <dc:creator>' . $creator . '</dc:creator>
  <cp:lastModifiedBy>' . $creator . '</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>
</cp:coreProperties>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private static function workbookRelsForSheets(int $count): string
    {
        $count = max(1, (int)$count);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $count; $i++) {
            $xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $xml .= '<Relationship Id="rId' . ($count + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private static function workbook(array $branding): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . self::esc($branding['sheet_name'] ?? 'Relatorio') . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
    }

    private static function workbookForSheets(array $sheetNames): string
    {
        $sheetNames = array_values(array_filter(array_map('strval', $sheetNames), static fn(string $v): bool => trim($v) !== ''));
        if (empty($sheetNames)) {
            $sheetNames = ['Sheet1'];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        foreach ($sheetNames as $i => $name) {
            $sheetId = $i + 1;
            $xml .= '<sheet name="' . self::esc(mb_substr($name, 0, 31)) . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';
        }
        $xml .= '</sheets></workbook>';
        return $xml;
    }

    private static function styles(): string
    {
        // s=0: default; s=1: report title; s=2: report meta; s=3: header; s=4: bordered cells
        return '<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><sz val="11"/></font>
    <font><b/><sz val="16"/></font>
    <font><sz val="10"/><color rgb="FF6B7280"/></font>
    <font><b/><sz val="12"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF9FAFB"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDDE1E6"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="5">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
  </cellXfs>
</styleSheet>';
    }

    private static function sheet1(array $rows, array $branding): string
    {
        [$columns, $keys, $colWidths] = self::buildPlanosColumns($rows);
        return self::renderSheetXml($rows, $columns, $keys, $colWidths, $branding);
    }

    private static function genericSheet(array $rows, array $columns, array $branding): string
    {
        $keys = array_keys($columns);
        $labels = array_values($columns);
        $widths = [];
        foreach ($keys as $key) {
            $widths[] = self::guessWidth($key);
        }
        return self::renderSheetXml($rows, $labels, $keys, $widths, $branding);
    }

    private static function templateSheet(array $rows, array $columns): string
    {
        $keys = array_keys($columns);
        $labels = array_values($columns);
        $widths = [];
        foreach ($keys as $key) {
            $widths[] = self::guessWidth($key);
        }
        return self::renderTemplateSheetXml($rows, $labels, $keys, $widths);
    }

    private static function renderTemplateSheetXml(array $rows, array $columns, array $keys, array $colWidths): string
    {
        $maxR = max(1, count($rows) + 1);
        $lastColName = self::coordCol(max(count($columns) - 1, 0));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="A1:' . $lastColName . $maxR . '"/>';
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<cols>';
        $colCount = count($columns);
        for ($i = 0; $i < $colCount; $i++) {
            $w = $colWidths[$i] ?? 15;
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        $xml .= '<row r="1">';
        foreach ($columns as $j => $label) {
            $xml .= '<c r="' . self::coord($j, 1) . '" t="inlineStr" s="3"><is><t>' . self::esc($label) . '</t></is></c>';
        }
        $xml .= '</row>';
        $rIdx = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rIdx . '">';
            $cells = [];
            foreach ($keys as $key) {
                $cells[] = self::formatCell($key, $row[$key] ?? null);
            }
            foreach ($cells as $j => $val) {
                $xml .= '<c r="' . self::coord($j, $rIdx) . '" t="inlineStr" s="4"><is><t>' . self::esc($val) . '</t></is></c>';
            }
            $xml .= '</row>';
            $rIdx++;
        }
        $xml .= '</sheetData>';
        $xml .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
        $xml .= '</worksheet>';
        return $xml;
    }

    private static function renderSheetXml(array $rows, array $columns, array $keys, array $colWidths, array $branding): string
    {
        $title = (string)($branding['header_title'] ?? 'RelatÃ³rio');
        $subtitle = trim((string)($branding['header_subtitle'] ?? ''));
        $meta = 'Gerado em ' . (string)($branding['generated_at'] ?? DateHelper::now());

        $headerRow = 4;
        $firstDataRow = 5;
        $maxR = count($rows) + 4;
        $lastColName = self::coordCol(max(count($columns) - 1, 0));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
              . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
              . '<sheetPr><pageSetUpPr/></sheetPr>'
              . '<dimension ref="A1:' . $lastColName . $maxR . '"/>'
              . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
              . '<cols>';
        $colCount = count($columns);
        for ($i=0; $i<$colCount; $i++) {
            $w = $colWidths[$i] ?? 15;
            $xml .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.$w.'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        $xml .= '<row r="1" ht="24" customHeight="1">';
        $xml .= '<c r="A1" t="inlineStr" s="1"><is><t>' . self::esc($title) . '</t></is></c>';
        $xml .= '</row>';
        $xml .= '<row r="2" ht="18" customHeight="1">';
        $xml .= '<c r="A2" t="inlineStr" s="2"><is><t>' . self::esc(trim($subtitle !== '' ? ($subtitle . ' â€¢ ' . $meta) : $meta)) . '</t></is></c>';
        $xml .= '</row>';
        $xml .= '<row r="3"></row>';
        // Header row (s=3)
        $xml .= '<row r="' . $headerRow . '">';
        foreach ($columns as $j => $label) {
            $xml .= '<c r="'.self::coord($j,$headerRow).'" t="inlineStr" s="3"><is><t>'.self::esc($label).'</t></is></c>';
        }
        $xml .= '</row>';
        // Data rows (s=4)
        $rIdx = $firstDataRow;
        foreach ($rows as $row) {
            $xml .= '<row r="'.$rIdx.'">';
            $cells = [];
            foreach ($keys as $key) {
                $cells[] = self::formatCell($key, $row[$key] ?? null);
            }
            foreach ($cells as $j => $val) {
                $xml .= '<c r="'.self::coord($j,$rIdx).'" t="inlineStr" s="4"><is><t>'.self::esc($val).'</t></is></c>';
            }
            $xml .= '</row>';
            $rIdx++;
        }
        $xml .= '</sheetData>'
              . '<mergeCells count="2"><mergeCell ref="A1:' . $lastColName . '1"/><mergeCell ref="A2:' . $lastColName . '2"/></mergeCells>'
              . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
              . '<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>'
              . '</worksheet>';
        return $xml;
    }

    private static function buildPlanosColumns(array $rows): array
    {
        $preferred = [
            'id',
            'id_cliente',
            'cliente_nome',
            'titulo',
            'descricao',
            'meta_valor',
            'meta_unidade',
            'responsavel',
            'fase',
            'status',
            'progresso',
            'prazo',
            'created_at',
            'updated_at',
        ];

        $labels = [
            'id' => 'ID',
            'id_cliente' => 'ID Cliente',
            'cliente_nome' => 'Cliente',
            'titulo' => 'TÃ­tulo',
            'descricao' => 'DescriÃ§Ã£o',
            'meta_valor' => 'Meta / Objetivo',
            'meta_unidade' => 'Origem',
            'responsavel' => 'ResponsÃ¡vel',
            'fase' => 'Fase',
            'status' => 'Status',
            'progresso' => 'Progresso (%)',
            'prazo' => 'Prazo',
            'created_at' => 'Data de CriaÃ§Ã£o',
            'updated_at' => 'Data de AtualizaÃ§Ã£o',
        ];

        $widths = [
            'id' => 10,
            'id_cliente' => 12,
            'cliente_nome' => 28,
            'titulo' => 36,
            'descricao' => 54,
            'meta_valor' => 42,
            'meta_unidade' => 24,
            'responsavel' => 28,
            'fase' => 12,
            'status' => 18,
            'progresso' => 14,
            'prazo' => 16,
            'created_at' => 20,
            'updated_at' => 20,
        ];

        $available = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $available[$key] = true;
            }
        }
        if (empty($available)) {
            $available = array_fill_keys($preferred, true);
        }

        $keys = [];
        foreach ($preferred as $key) {
            if (isset($available[$key])) {
                $keys[] = $key;
                unset($available[$key]);
            }
        }
        $extraKeys = array_keys($available);
        sort($extraKeys);
        $keys = array_merge($keys, $extraKeys);

        $columns = [];
        $colWidths = [];
        foreach ($keys as $key) {
            $columns[] = $labels[$key] ?? self::humanizeKey($key);
            $colWidths[] = $widths[$key] ?? self::guessWidth($key);
        }

        return [$columns, $keys, $colWidths];
    }

    private static function formatCell(string $key, $value): string
    {
        if ($value === null) {
            return '';
        }
        if (in_array($key, ['created_at', 'updated_at'], true)) {
            $raw = trim((string)$value);
            if ($raw === '') {
                return '';
            }
            return DateHelper::formatDateTime($raw);
        }
        if ($key === 'prazo') {
            return DateHelper::formatDate((string)$value);
        }
        if ($key === 'progresso') {
            return trim((string)$value) === '' ? '' : ((string)$value . '%');
        }
        return (string)$value;
    }

    private static function humanizeKey(string $key): string
    {
        $label = str_replace('_', ' ', trim($key));
        return $label === '' ? 'Campo' : mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }

    private static function guessWidth(string $key): int
    {
        if (str_contains($key, 'descricao') || str_contains($key, 'texto')) {
            return 40;
        }
        if (str_contains($key, 'data') || str_contains($key, 'prazo')) {
            return 18;
        }
        return 22;
    }

    private static function coord(int $colIndexZero, int $row): string
    {
        return self::coordCol($colIndexZero) . $row;
    }

    private static function coordCol(int $colIndexZero): string
    {
        $colName = '';
        $n = $colIndexZero + 1;
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $colName = chr(65 + $rem) . $colName;
            $n = intdiv($n - 1, 26);
        }
        return $colName;
    }
}


