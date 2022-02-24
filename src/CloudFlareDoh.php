<?php

declare(strict_types=1);

use Solcloud\Http\Contract\IRequestDownloader;
use Solcloud\Http\Request;

class CloudFlareDoh extends GoogleDoh
{
    protected string $dohServerIp = '1.1.1.1';
    protected string $dohDnsCommonName = 'cloudflare-dns.com';
    protected string $baseUrl = 'https://1.1.1.1/dns-query?';

    // https://developers.cloudflare.com/1.1.1.1/dns-over-https/json-format
    // almost same format as google so extend

    public function __construct(IRequestDownloader $downloader = null, Request $request = null)
    {
        parent::__construct($downloader, $request);

        $headers = $this->request->getHeaders();
        $headers[] = 'accept: application/dns-json'; // need accept header for json
        $this->request->setHeaders($headers);
    }
}
