<?php
namespace App\Core;

class VirusScanner
{
    public static function scan(string $filePath): array
    {
        $result = ['safe' => true, 'engine' => null, 'output' => null];
        $path = escapeshellarg($filePath);
        $cmds = [
            'clamscan --stdout --no-summary ' . $path,
            'clamdscan --stdout ' . $path,
        ];
        foreach ($cmds as $cmd) {
            try {
                $proc = @popen($cmd, 'r');
                if ($proc) {
                    $out = stream_get_contents($proc);
                    pclose($proc);
                    $result['engine'] = strtok($cmd, ' ');
                    $result['output'] = $out;
                    if (stripos($out, 'FOUND') !== false) {
                        $result['safe'] = false;
                    }
                    return $result;
                }
            } catch (\Throwable $e) {
            }
        }
        $result['engine'] = 'none';
        $result['output'] = 'no-engine';
        return $result;
    }
}
