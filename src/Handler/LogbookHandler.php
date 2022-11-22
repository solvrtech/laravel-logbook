<?php

namespace Solvrtech\Laravel\Logbook\Handler;

use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\HttpClient;

class LogbookHandler extends AbstractProcessingHandler
{
    public function __construct(
        int|string|LogLevel $level = LogLevel::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function write(LogRecord|array $record): void
    {
        $httpClient = HttpClient::create();
        try {
            $httpClient->request(
                'POST',
                "{$this->getAPIUrl()}/api/log/save",
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'token' => $this->getAPIkey(),
                    ],
                    'body' => $record['formatted'],
                ]
            );
        } catch (Exception $e) {
        }
    }

    /**
     * Get logbook API url from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    private function getAPIUrl(): string
    {
        $url = config('logbook.api.url');

        if (null === $url) {
            throw new Exception('Logbook url not found');
        }

        if ('/' === substr($url, -1)) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    /**
     * Get logbook API key from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    private function getAPIKey(): string
    {
        $key = config('logbook.api.key');

        if (null === $key) {
            throw new Exception('Logbook key not found');
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }
}
