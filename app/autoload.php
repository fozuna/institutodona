<?php
$env = __DIR__ . '/../.env';
if (file_exists($env)) {
    foreach (file($env) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') { continue; }
        $pos = strpos($line, '=');
        if ($pos === false) { continue; }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ((strlen($val) >= 2) && (($val[0] === '"' && $val[strlen($val)-1] === '"') || ($val[0] === "'" && $val[strlen($val)-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        putenv("$key=$val");
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}
spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Controllers\\' => __DIR__ . '/controllers/',
        'App\\Models\\' => __DIR__ . '/models/',
        'App\\Database\\' => __DIR__ . '/database/',
        'App\\Views\\' => __DIR__ . '/views/',
        'App\\Core\\' => __DIR__ . '/core/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
