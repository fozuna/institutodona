<?php
namespace App\Core;

final class ReportBranding
{
    public static function aplicarBrandingRelatorio(string $tipo, array $documento = []): array
    {
        $base = [
            'report_title' => (string)($documento['report_title'] ?? 'Relatório'),
            'header_title' => (string)($documento['header_title'] ?? ($documento['report_title'] ?? 'Relatório')),
            'header_subtitle' => (string)($documento['header_subtitle'] ?? ''),
            'generated_at' => (string)($documento['generated_at'] ?? DateHelper::now()),
            'logo_position' => self::normalizeLogoPosition((string)($documento['logo_position'] ?? 'left')),
            'logo_width' => max(36, (int)($documento['logo_width'] ?? 96)),
            'margins' => self::normalizeMargins($documento['margins'] ?? []),
            'logo_path' => self::logoAbsolutePath(),
            'logo_uri' => self::logoFileUri(),
            'footer_text' => (string)($documento['footer_text'] ?? ''),
        ];

        if ($tipo === 'excel') {
            $base['sheet_name'] = self::sanitizeSheetName((string)($documento['sheet_name'] ?? 'Relatorio'));
        }

        return $base;
    }

    public static function footerLabel(int $page, int $totalPages, string $generatedAt, string $extra = ''): string
    {
        $parts = ['Página ' . $page . '/' . $totalPages, $generatedAt];
        $extra = trim($extra);
        if ($extra !== '') {
            $parts[] = $extra;
        }
        return implode('  •  ', $parts);
    }

    public static function logoAbsolutePath(): string
    {
        foreach (self::logoCandidates() as $file) {
            $real = realpath($file);
            if ($real && is_file($real)) {
                return $real;
            }
        }
        return '';
    }

    public static function logoFileUri(): string
    {
        $path = self::logoAbsolutePath();
        if ($path === '') {
            return '';
        }
        return 'file:///' . str_replace('\\', '/', ltrim($path, '\\/'));
    }

    private static function logoCandidates(): array
    {
        $root = dirname(__DIR__, 2);
        return [
            $root . '/public/assets/img/logovivamais.png',
            $root . '/public/assets/img/logovivamais.PNG',
            $root . '/public_html/assets/img/logovivamais.png',
            $root . '/public_html/assets/img/logovivamais.PNG',
            $root . '/public/assets/img/logobco.png',
            $root . '/public_html/assets/img/logobco.png',
        ];
    }

    private static function normalizeMargins(array $margins): array
    {
        return [
            'top' => max(10, (int)($margins['top'] ?? 16)),
            'right' => max(10, (int)($margins['right'] ?? 12)),
            'bottom' => max(10, (int)($margins['bottom'] ?? 14)),
            'left' => max(10, (int)($margins['left'] ?? 12)),
        ];
    }

    private static function normalizeLogoPosition(string $position): string
    {
        return in_array($position, ['left', 'right'], true) ? $position : 'left';
    }

    private static function sanitizeSheetName(string $name): string
    {
        $name = trim(preg_replace('/[\\\\\\/*?:\[\]]+/', ' ', $name) ?? $name);
        if ($name === '') {
            return 'Relatorio';
        }
        return mb_substr($name, 0, 31, 'UTF-8');
    }
}
