<?php

declare(strict_types=1);

abstract class Doh implements IResolve
{
    private int $maxDomainLength = 70;
    private array $blockList = []; // Maybe use some from https://firebog.net/
    private string $hostname;
    private string $log;
    private array $cache = [];
    private int $cacheTypeLimit = 300;
    private int $cacheCreatedAt = 0;
    private int $cacheTimeSecLimit = 3600;

    public function __construct()
    {
        $this->hostname = gethostname();
    }

    protected function dd(string $msg, int $level = 9)
    {
        if ($level > 5) {
            fwrite(STDERR, $this->log . $msg . PHP_EOL);
        }
        return null;
    }

    protected function resolverNotAvailable(string $msg = 'Resolver not available'): void
    {
        throw new ResolverNotAvailable(__CLASS__ . " - $msg");
    }

    public abstract function resolveSpecific(string $domain, int $type): ?string;

    public function resolve(string $domain, int $type): ?string
    {
        $this->log = "Got: '{$domain}':\t\t";

        $domainLength = strlen($domain);
        if ($domainLength > $this->maxDomainLength) {
            return $this->dd("Wtf is this big crap");
        }
        if (strtolower($domain) === strtolower($this->hostname . '.')) {
            $this->dd('Use /etc/hosts');
            return '127.0.0.1';
        }
        if (strtolower($domain) === strtolower('localhost.')) {
            $this->dd('Use /etc/hosts');
            return '127.0.0.1';
        }
        if ($domainLength < 4) {
            return $this->dd("Wtf is this small crap");
        }
        if (substr_count($domain, '.') < 2) {
            return $this->dd("Looks like bad domain", 3);
        }
        foreach ($this->blockList as $blockDomain) {
            if ($blockDomain . '.' === $domain) {
                return $this->dd("Blocklist hit", 2);
            }
        }
        if (!preg_match('~^[-.a-z0-9]+$~', $domain)) {
            return $this->dd("Use ascii");
        }
        if (strpos($domain, 'xn--') !== false) {
            return $this->dd("No to IDNA punycode");
        }

        $cacheHit = $this->cacheGet($domain, $type);
        if ($cacheHit) {
            $this->dd('Cache hit');
            return $cacheHit;
        }

        $hit = $this->resolveSpecific($domain, $type);
        if ($hit) {
            $this->cacheSave($domain, $type, $hit);
        }
        return $hit;
    }

    protected function cacheGet(string $domain, int $type): ?string
    {
        $this->warmupCache();
        return $this->cache[$type][$domain] ?? null;
    }

    protected function warmupCache(): void
    {
        $time = time();
        if (($this->cacheCreatedAt + $this->cacheTimeSecLimit) > $time) {
            return;
        }

        $this->cache = [];
        $this->cacheCreatedAt = $time;
    }

    protected function cacheSave(string $domain, int $type, string $hit): void
    {
        $cache = &$this->cache[$type];
        if ($cache === null) {
            $cache = [];
        }
        if (count($cache) >= $this->cacheTypeLimit) {
            array_shift($cache);
        }
        $cache[$domain] = $hit;
    }

    public function getMaxDomainLength(): int
    {
        return $this->maxDomainLength;
    }

    public function loadBlockList(?string $blockListPath = null): void
    {
        if ($blockListPath) {
            $this->blockList = explode("\n", file_get_contents($blockListPath));
        } else {
            $this->blockList = [];
        }
    }

}
