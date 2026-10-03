<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal HTTP/1.1 client over a raw socket, which allows to send the request body slowly or to stall in the middle of
 * it, which is not possible with Guzzle. The connection is closed by the server after the response.
 */
class RawHttpRequest
{
    /**
     * @var resource
     */
    private $socket;

    private int $sentBodyBytes = 0;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $method, string $uri, array $headers, private int $contentLength)
    {
        $parsedUrl = \Safe\parse_url($_ENV['API_DOMAIN']);
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 80;
        $this->socket = \Safe\stream_socket_client(sprintf('tcp://%s:%d', $host, $port), $errorCode, $errorMessage, 10);

        $headers = array_merge(
            ['Host' => $host, 'Connection' => 'close', 'Content-Length' => (string) $contentLength],
            $headers
        );
        $head = sprintf("%s %s HTTP/1.1\r\n", $method, $uri);
        foreach ($headers as $name => $value) {
            $head .= sprintf("%s: %s\r\n", $name, $value);
        }
        $this->write($head."\r\n");
    }

    /**
     * @return bool false if the server already closed the connection
     */
    public function sendBody(string $bytes): bool
    {
        $this->sentBodyBytes += strlen($bytes);

        return $this->write($bytes);
    }

    /**
     * Sends the remaining body bytes, i.e. the ones which were not sent yet, in $parts parts with a pause in between.
     */
    public function sendBodySlowly(string $body, int $parts, float $pauseInSeconds): bool
    {
        $partLength = (int) ceil(strlen($body) / $parts);
        $result = true;
        foreach (str_split($body, $partLength) as $index => $part) {
            if ($index > 0) {
                usleep((int) ($pauseInSeconds * 1_000_000));
            }
            $result = $this->sendBody($part) && $result;
        }

        return $result;
    }

    /**
     * Returns null if the server closed the connection without answering.
     */
    public function readResponse(int $timeoutInSeconds = 60): ?ResponseInterface
    {
        stream_set_timeout($this->socket, $timeoutInSeconds);
        $raw = '';
        while (!feof($this->socket)) {
            $chunk = fread($this->socket, 65536);
            if (false === $chunk || '' === $chunk) {
                if (stream_get_meta_data($this->socket)['timed_out']) {
                    break;
                }
                continue;
            }
            $raw .= $chunk;
        }
        @fclose($this->socket);

        if ('' === $raw) {
            return null;
        }

        return Message::parseResponse($raw);
    }

    public function close(): void
    {
        @fclose($this->socket);
    }

    public function getSentBodyBytes(): int
    {
        return $this->sentBodyBytes;
    }

    private function write(string $bytes): bool
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $result = @fwrite($this->socket, substr($bytes, $written));
            if (false === $result || 0 === $result) {
                return false;
            }
            $written += $result;
        }

        return true;
    }
}
