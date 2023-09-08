<?php

namespace Solvrtech\Logbook\Handler;

use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Solvrtech\Logbook\Formatter\LogbookFormatter;
use Solvrtech\Logbook\LogbookConfig;
use Solvrtech\Logbook\Transport\TransportInterface;

class LogbookHandler extends AbstractProcessingHandler
{
    use LogbookConfig;

    private TransportInterface $transport;

    public function __construct(
        private array $config,
        int|string|LogLevel|Level $level = LogLevel::DEBUG,
        bool $bubble = true,
        TransportInterface $transport
    ) {
        $this->transport = $transport;
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function write(LogRecord|array $record): void
    {
        if ($this->getMinLevel() <= $this->toIntLevel($record['level_name'])) {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-lb-token' => $this->getAPIKey(),
                'x-lb-version' => $this->getVersion(),
                'x-lb-instance-id' => $this->getInstanceId(),
                'url' => $this->getAPIUrl(),
            ];

            $this->transport->send(json_encode($record['formatted']), $headers);
        }
    }

    /**
     * Get the minimum log level allowed to be stored from environment.
     *
     * @return int
     */
    private function getMinLevel(): int
    {
        return $this->toIntLevel($this->config['level']);
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
        return new LogbookFormatter();
    }
}
