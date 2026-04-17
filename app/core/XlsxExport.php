<?php
namespace App\Core;

class XlsxExport
{
    public static function exportPlanos(array $rows, string $filename): string
    {
        $branding = ReportBranding::aplicarBrandingRelatorio('excel', [
            'report_title' => 'Planos de Ação',
            'header_title' => 'Planos de Ação',
            'header_subtitle' => 'Exportação padronizada do sistema',
            'generated_at' => DateHelper::now(),
            'sheet_name' => 'Planos',
        ]);
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Não foi possível criar arquivo XLSX');
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
        return '<?xml version="1.0" encoding="UTF-8"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Instituto Dona Export</Application>
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
        return '<?xml version="1.0" encoding="UTF-8"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>' . self::esc($branding['report_title'] ?? 'Relatório') . '</dc:title>
  <dc:creator>Instituto Dona</dc:creator>
  <cp:lastModifiedBy>Instituto Dona</cp:lastModifiedBy>
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

    private static function workbook(array $branding): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . self::esc($branding['sheet_name'] ?? 'Relatorio') . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
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
        $columns = ['ID','Título','Descrição','Responsável','Data de Início','Data de Término','Status','Progresso','Observações'];
        $colWidths = [10,40,60,25,18,18,18,12,30];
        $title = (string)($branding['header_title'] ?? 'Relatório');
        $subtitle = trim((string)($branding['header_subtitle'] ?? ''));
        $meta = 'Gerado em ' . (string)($branding['generated_at'] ?? DateHelper::now());

        $headerRow = 4;
        $firstDataRow = 5;
        $maxR = count($rows) + 4;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
              . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
              . '<sheetPr><pageSetUpPr/></sheetPr>'
              . '<dimension ref="A1:I'.$maxR.'"/>'
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
        $xml .= '<c r="A2" t="inlineStr" s="2"><is><t>' . self::esc(trim($subtitle !== '' ? ($subtitle . ' • ' . $meta) : $meta)) . '</t></is></c>';
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
            $cells = [
                $row['id'] ?? '',
                $row['titulo'] ?? '',
                $row['descricao'] ?? '',
                $row['responsavel'] ?? '',
                isset($row['created_at']) ? substr((string)$row['created_at'],0,10) : '',
                $row['prazo'] ?? '',
                $row['status'] ?? '',
                isset($row['progresso']) ? (string)$row['progresso'] : '',
                $row['meta_unidade'] ?? '' // usando meta_unidade como Observações por ora
            ];
            foreach ($cells as $j => $val) {
                $xml .= '<c r="'.self::coord($j,$rIdx).'" t="inlineStr" s="4"><is><t>'.self::esc($val).'</t></is></c>';
            }
            $xml .= '</row>';
            $rIdx++;
        }
        $xml .= '</sheetData>'
              . '<mergeCells count="2"><mergeCell ref="A1:I1"/><mergeCell ref="A2:I2"/></mergeCells>'
              . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
              . '<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>'
              . '</worksheet>';
        return $xml;
    }

    private static function coord(int $colIndexZero, int $row): string
    {
        $colName = '';
        $n = $colIndexZero + 1;
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $colName = chr(65 + $rem) . $colName;
            $n = intdiv($n - 1, 26);
        }
        return $colName . $row;
    }
}

