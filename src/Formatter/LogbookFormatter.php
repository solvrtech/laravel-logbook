<?php

namespace Solvrtech\Laravel\Logbook\Formatter;

use Monolog\Formatter\FormatterInterface;
use Solvrtech\Laravel\Logbook\Model\ClientModel;
use Solvrtech\Laravel\Logbook\Model\LogModel;
use Throwable;

class LogbookFormatter implements FormatterInterface
{
    private LogModel $logModel;

    public function __construct()
    {
        $this->logModel = new LogModel();
    }

    /**
     * @inheritDoc
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    /**
     * @inheritDoc
     */
    public function format(array $record)
    {
        $this->normalizeContext($record['context']);
        $extra = $record['extra'];

        return $this->logModel
            ->setMessage($record['message'])
            ->setCode($record['level'])
            ->setLevel($record['level_name'])
            ->setChannel($record['channel'])
            ->setDatetime($record['datetime'])
            ->setAdditional(
                array_key_exists('additional', $extra) ?
                    $extra['additional'] :
                    []
            )
            ->setClient(
                array_key_exists('client', $extra) ?
                    $extra['client'] :
                    new ClientModel()
            );
    }

    /**
     *
     */
    public function normalizeContext(array $context): void
    {
        foreach ($context as $value) {
            if (is_array($value)) {
                self::normalizeContext($value);
            }

            if ($value instanceof Throwable) {
                $this->logModel->setFile(
                    "{$value->getFile()}:{$value->getLine()}"
                );

                $trace = [];
                foreach ($value->getTrace() as $val) {
                    if (isset($val['file'], $val['line'])) {
                        $trace[] = $val['file'].':'.$val['line'];
                    }
                }
                $this->logModel->setStackTrace($trace);
            }
        }
    }
}