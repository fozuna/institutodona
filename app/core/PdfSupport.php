<?php
namespace App\Core;

final class PdfSupport
{
    public static function newErrorId(): string
    {
        try {
            return bin2hex(random_bytes(5));
        } catch (\Throwable $e) {
            return (string)time();
        }
    }

    public static function isDompdfAvailable(): bool
    {
        if ((string)getenv('PDF_FORCE_MISSING') === '1') {
            return false;
        }
        return class_exists(\Dompdf\Dompdf::class) && class_exists(\Dompdf\Options::class);
    }

    public static function dompdfDiagnostics(): array
    {
        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $tmp = (string)sys_get_temp_dir();
        $tmpWritable = $tmp !== '' ? @is_writable($tmp) : false;
        $dir = dirname(__DIR__, 2) . '/storage';
        $storageWritable = @is_writable($dir);
        return [
            'php' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memory_limit' => (string)ini_get('memory_limit'),
            'max_execution_time' => (string)ini_get('max_execution_time'),
            'pdf_force_missing' => (string)getenv('PDF_FORCE_MISSING'),
            'vendor_autoload_exists' => is_file($vendorAutoload),
            'dompdf_exists' => class_exists(\Dompdf\Dompdf::class),
            'dompdf_options_exists' => class_exists(\Dompdf\Options::class),
            'ext_mbstring' => extension_loaded('mbstring'),
            'ext_gd' => extension_loaded('gd'),
            'ext_iconv' => extension_loaded('iconv'),
            'ext_zlib' => extension_loaded('zlib'),
            'tmp_dir' => $tmp,
            'tmp_writable' => $tmpWritable,
            'storage_writable' => $storageWritable,
        ];
    }

    public static function missingDompdfMessage(): string
    {
        return 'Geração de PDF indisponível no momento. Tente novamente em alguns minutos.';
    }
}
