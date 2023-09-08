<?php

namespace Solvrtech\Logbook\Transport\sync;

use Exception;
use Solvrtech\Logbook\Transport\TransportInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SyncTransport implements TransportInterface
{
    /**
     * Send log to the logbook app with synchronous behavior
     *
     * @param string $body
     * @param array $headers
     *
     * @return array
     */
    public function send(string $body, array $headers): array
    {
        $apiUrl = $headers['url'];
        unset($headers['url']);

        $httpClient = HttpClient::create();
        try {
            $httpClient->request(
                'POST',
                "{$apiUrl}/api/log/save",
                [
                    'headers' => $headers,
                    'body' => $body,
                ]
            );
        } catch (Exception|TransportExceptionInterface $exception) {
        }

        return json_decode($body, true);
    }

}
