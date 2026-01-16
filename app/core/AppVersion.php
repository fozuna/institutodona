<?php
namespace App\Core;

class AppVersion
{
    public static function get(): string
    {
        $env = getenv('APP_VERSION');
        if ($env && is_string($env) && trim($env) !== '') {
            return trim($env);
        }
        $file = __DIR__ . '/../../VERSION';
        if (is_file($file)) {
            $v = trim(@file_get_contents($file));
            if ($v !== '') {
                return $v;
            }
        }
        $ver = null;
        $root = __DIR__ . '/../../';
        if (is_dir($root . '.git')) {
            $hash = @exec('git rev-parse --short HEAD');
            $count = @exec('git rev-list --count HEAD');
            if ($hash || $count) {
                $ver = 'v' . date('Y.m.d') . '-' . ($count ?: '0') . '+' . ($hash ?: 'dev');
            }
        }
        if ($ver) {
            return $ver;
        }
        if (isset($GLOBALS['config']['app']['version']) && $GLOBALS['config']['app']['version']) {
            return (string)$GLOBALS['config']['app']['version'];
        }
        return 'v1.24.0';
    }
}
