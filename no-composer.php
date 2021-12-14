<?php

$libRoot = __DIR__ . '/../lib';
$candidates = [
    'http/src'       => 'Solcloud\\Http',
    'curl/src'       => 'Solcloud\\Curl',
    'socket-raw/src' => 'Socket\\Raw',
    'yswery'         => 'yswery\\DNS',
];

spl_autoload_register(function (string $className) use ($libRoot, $candidates): void {
    if (strpos($className, '\\') === false) {
        require "$libRoot/../src/{$className}.php";
        return;
    }

    foreach ($candidates as $packagePath => $prefix) {
        if (strpos($className, $prefix) !== 0) {
            continue;
        }

        $pos = strlen($prefix);
        $path = str_replace('\\', '/', substr($className, $pos + 1));
        require "{$libRoot}/{$packagePath}/{$path}.php";
    }
});
