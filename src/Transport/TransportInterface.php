<?php

namespace Solvrtech\Logbook\Transport;

interface TransportInterface
{
    /**
     * Send log to the specified endpoint with flexibility for synchronous or asynchronous behavior
     *
     * @param string $body
     * @param array $headers
     *
     * @return array
     */
    public function send(string $body, array $headers): array;
}
