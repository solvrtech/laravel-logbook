<?php

namespace Solvrtech\Laravel\Logbook\Handler;

use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Solvrtech\Laravel\Logbook\LogbookConfig;
use Symfony\Component\HttpClient\HttpClient;

class LogbookHandler extends AbstractProcessingHandler
{
    use LogbookConfig;

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
        if ($this->getMinLevel() <= $this->toIntLevel($record['level_name'])) {
            try {
                $httpClient->request(
                    'POST',
                    "{$this->getAPIUrl()}/api/log/save",
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'x-lb-token' => $this->getAPIkey(),
                            'x-lb-version' => $this->getVersion()
                        ],
                        'body' => json_encode($record['formatted']),
                    ]
                );
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Get the minimum log level allowed to be stored from environment.
     * 
     * @return int
     */
    private function getMinLevel(): int
    {
        $level = config('logbook.level');

        if (null !== $level) {
            return $this->toIntLevel($level);
        }

        return 0;
    }

    /**
     * Translate log level into int level
     * 
     * @param string $level
     * 
     * @return int
     */
    private function toIntLevel(string $level): int
    {
        $intLevel = 0;

        try {
            $intLevel = match (strtolower($level)) {
                'debug' => 0,
                'info' => 1,
                'notice' => 2,
                'warning' => 3,
                'error' => 4,
                'critical' => 5,
                'alert' => 6,
                'emergency' => 7
            };
        } catch (Exception $e) {
        }

        return $intLevel;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }
}
