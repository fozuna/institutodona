<?php
namespace App\Core;

class PlanilhaReader
{
    public static function load(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Arquivo não encontrado: ' . $path);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return self::loadCsv($path);
        }
        if ($ext === 'xlsx') {
            return self::loadXlsx($path);
        }
        throw new \RuntimeException('Extensão de arquivo não suportada: ' . $ext);
    }

    private static function loadCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o arquivo CSV.');
        }
        $delimiter = ';';
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        if ($commaCount > $semicolonCount) {
            $delimiter = ',';
        }
        rewind($handle);
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    private static function loadXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Extensão ZipArchive não disponível no PHP.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo XLSX.');
        }
        $sharedStrings = [];
        $sharedIndex = $zip->locateName('xl/sharedStrings.xml', \ZipArchive::FL_NOCASE);
        if ($sharedIndex !== false) {
            $xml = simplexml_load_string($zip->getFromIndex($sharedIndex));
            if ($xml !== false && isset($xml->si)) {
                foreach ($xml->si as $i => $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text .= (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string)$run->t;
                        }
                    }
                    $sharedStrings[(int)$i] = $text;
                }
            }
        }
        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml', \ZipArchive::FL_NOCASE);
        if ($sheetIndex === false) {
            $zip->close();
            throw new \RuntimeException('Planilha principal não encontrada no arquivo XLSX.');
        }
        $sheetXml = simplexml_load_string($zip->getFromIndex($sheetIndex));
        $zip->close();
        if ($sheetXml === false || !isset($sheetXml->sheetData->row)) {
            return [];
        }
        $rows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowValues = [];
            $lastColIndex = -1;
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $colLetters = preg_replace('/\d+/', '', $ref);
                $colIndex = self::colLettersToIndex($colLetters);
                if ($colIndex > $lastColIndex + 1) {
                    for ($i = $lastColIndex + 1; $i < $colIndex; $i++) {
                        $rowValues[] = '';
                    }
                }
                $value = '';
                $type = (string)$c['t'];
                if ($type === 's') {
                    $si = (int)$c->v;
                    $value = $sharedStrings[$si] ?? '';
                } else {
                    $value = isset($c->v) ? (string)$c->v : '';
                }
                $rowValues[] = $value;
                $lastColIndex = $colIndex;
            }
            $rows[] = $rowValues;
        }
        return $rows;
    }

    private static function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $num = 0;
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($letters[$i]) - 64);
        }
        return $num - 1;
    }
}

