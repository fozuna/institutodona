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
        $root = dirname(__DIR__, 2);
        $vendorAutoload = $root . '/vendor/autoload.php';
        $vendorDir = $root . '/vendor';
        $tmp = (string)sys_get_temp_dir();
        $tmpWritable = $tmp !== '' ? @is_writable($tmp) : false;
        $dir = $root . '/storage';
        $storageWritable = @is_writable($dir);
        return [
            'php' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memory_limit' => (string)ini_get('memory_limit'),
            'max_execution_time' => (string)ini_get('max_execution_time'),
            'open_basedir' => (string)ini_get('open_basedir'),
            'loaded_ini' => (string)php_ini_loaded_file(),
            'pdf_force_missing' => (string)getenv('PDF_FORCE_MISSING'),
            'vendor_autoload_exists' => is_file($vendorAutoload),
            'vendor_autoload_readable' => is_file($vendorAutoload) ? @is_readable($vendorAutoload) : false,
            'vendor_dir_exists' => is_dir($vendorDir),
            'vendor_dir_readable' => is_dir($vendorDir) ? @is_readable($vendorDir) : false,
            'dompdf_exists' => class_exists(\Dompdf\Dompdf::class),
            'dompdf_options_exists' => class_exists(\Dompdf\Options::class),
            'ext_mbstring' => extension_loaded('mbstring'),
            'ext_gd' => extension_loaded('gd'),
            'ext_iconv' => extension_loaded('iconv'),
            'ext_zlib' => extension_loaded('zlib'),
            'tmp_dir' => $tmp,
            'tmp_writable' => $tmpWritable,
            'storage_writable' => $storageWritable,
            'storage_free_bytes' => is_dir($dir) ? (@disk_free_space($dir) ?: null) : null,
        ];
    }

    public static function missingDompdfMessage(): string
    {
        return 'Geração de PDF indisponível no momento. Tente novamente em alguns minutos.';
    }
}
