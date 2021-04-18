<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

/////
$port = (int)($argv[1] ?? 5353);
$bindAddress = "udp://127.0.0.1:$port";
const TYPE_A = 1;
const TTL_SEC = 3600;
const RCODE_NXDOMAIN = 3;
const RCODE_FOUND = 0;
/////

use Socket\Raw\Factory;
use yswery\DNS\Decoder;
use yswery\DNS\Encoder;
use yswery\DNS\Message;
use yswery\DNS\ResourceRecord;

$doh = new FallbackResolver();
$doh->addResolver('google', new GoogleDoh());
$doh->addResolver('cloudflare', new CloudFlareDoh());

function domainToIp(string $domainName, int $type): ?string
{
    global $doh;
    if ($type !== 1) {
        echo PHP_EOL . "----------------------------------------------------------------------";
        echo "Got not implemented type: {$type} for domain {$domainName}" . PHP_EOL;
        echo PHP_EOL . "----------------------------------------------------------------------";
        return null;
    }

    try {
        return $doh->resolve($domainName, $type);
    } catch (Exception $ex) {
        fwrite(STDERR, $ex->getMessage() . PHP_EOL);
        return null;
    }
}

function createAnswer(Message $response): Message
{
    $response->getHeader()
             ->setResponse(true)
             ->setRecursionAvailable(true)
             ->setAuthoritative(true)
             ->setRcode(RCODE_NXDOMAIN)
    ;

    foreach ($response->getQuestions() as $question) {
        if ($question->getType() !== TYPE_A) {
            continue;
        }

        $name = $question->getName();
        $ipOrNull = domainToIp($name, $question->getType());
        if ($ipOrNull === null) {
            continue;
        }

        $record = new ResourceRecord();
        $record->setName($name);
        $record->setQuestion(false);
        $record->setType($question->getType());
        $record->setTtl(TTL_SEC);
        $record->setRdata($ipOrNull);

        $response->getHeader()->setRcode(RCODE_FOUND);
        $response->addAnswer($record);
    }

    return $response;
}

$factory = new Factory();
$socket = $factory->createServer($bindAddress);

while (true) {
    $peer = null;
    $question = $socket->recvFrom(1024, MSG_OOB, $peer);
    assert($peer !== null);
    $answer = createAnswer(Decoder::decodeMessage($question));
    $buffer = Encoder::encodeMessage($answer);
    $writeBytes = $socket->sendTo($buffer, MSG_EOF, $peer);
    assert(strlen($buffer) === $writeBytes);
}
$socket->close();
