<?php

declare(strict_types=1);

use Solcloud\Curl\CurlRequest;
use Solcloud\Http\Contract\IRequestDownloader;
use Solcloud\Http\Exception\HttpException;
use Solcloud\Http\Request;
use Solcloud\Http\Response;

class GoogleDoh extends Doh
{
    protected string $dohServerIp = '8.8.8.8';
    protected string $dohDnsCommonName = 'dns.google';
    protected string $baseUrl = 'https://8.8.8.8/resolve?';

    protected Request $request;
    protected IRequestDownloader $downloader;

    public function __construct(IRequestDownloader $downloader = null, Request $request = null)
    {
        parent::__construct();

        $this->request = $request ?? new Request();
        $this->request->setConnectionTimeoutSec(1);
        $this->request->setRequestTimeoutSec(2);
        $this->request->setIncludeCertificatesInfo(true);
        $this->downloader = $downloader ?? new CurlRequest();
    }

    public function resolveSpecific(string $domain, int $type): ?string
    {
        // https://developers.google.com/speed/public-dns/docs/doh/json

        $paddingCount = $this->getMaxDomainLength() - strlen($domain);
        $params = http_build_query([
            'name'               => $domain,
            'type'               => '1', // Type A
            // 'cd' => '0', // disable disabling checking = enable check basically (default)
            //'ct' => 'application/x-javascript' // json (default)
            'edns_client_subnet' => '0.0.0.0/0', // tracking :D
            'random_padding'     => str_repeat('a', $paddingCount),
        ]);
        $this->request->setUrl($this->baseUrl . $params);

        try {
            $response = $this->downloader->fetchResponse($this->request);
            $this->checkResponse($response);
        } catch (InvalidArgumentException $ex) {
            return $this->dd($ex->getMessage());
        } catch (HttpException $ex) {
            $this->resolverNotAvailable($ex->getMessage());
        }

        $json = json_decode($response->getBody(), true);
        if (!is_array($json)) {
            return $this->dd("Not json");
        }
        if (($json['Status'] ?? false) !== 0) {
            return $this->dd("Not success status response");
        }

        foreach ($json['Answer'] ?? [] as $answer) {
            if (1 !== $answer['type']) {
                continue;
            }

            $ip = (string)$answer['data'];
            if (strlen($ip) > 15) {
                return null;
            }
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return null;
            }

            $this->dd(get_class($this) . ": GET OK");
            return $ip;
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    private function checkResponse(Response $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new HttpException("Not 200 code response");
        }

        if ($response->getLastIp() !== $this->dohServerIp) {
            throw new InvalidArgumentException("Response from different ip");
        }

        $responseCommonName = $response->getCertificates()[0]['Subject'] ?? false;
        if (!is_string($responseCommonName) || strpos($responseCommonName, "CN = {$this->dohDnsCommonName}") === false) {
            throw new InvalidArgumentException("Certificate common name mismatch");
        }

        if (strlen($response->getBody()) > 8096) {
            throw new InvalidArgumentException("Response body too long");
        }
    }

}
