<?php

require __DIR__ . '/../vendor/autoload.php';

function dd(string $msg, int $errorCode = 1): void
{
    fwrite(STDERR, $msg . PHP_EOL);
    exit($errorCode);
}

if ($argc < 2) {
    dd("Usage: $argv[0] domain.name [ google* | cloudflare ]");
}

$domain = $argv[1] . '.';
if (($argv[2] ?? 'default') === 'cloudflare') {
    $doh = new CloudFlareDoh();
} else {
    $doh = new GoogleDoh();
}
$doh->loadBlockList(__DIR__ . '/../blockListDefault.txt');
var_dump($doh->resolve($domain, 1));
