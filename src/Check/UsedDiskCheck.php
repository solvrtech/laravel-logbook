<?php

namespace Solvrtech\Logbook\Check;

use Exception;
use Solvrtech\Logbook\Exception\LogbookHealthException;
use Symfony\Component\Process\Process;
use Solvrtech\Logbook\Model\ConditionModel;

class UsedDiskCheck extends CheckService
{
    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return 'used-disk';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): ConditionModel
    {
        $condition = new ConditionModel();

        try {
            $diskSpace = self::getDiskSpace();

            $condition->setStatus(ConditionModel::OK)
                ->setMeta([
                    'usedDiskSpace' => $diskSpace,
                    'unit' => '%'
                ]);
        } catch (Exception $e) {
        }

        return $condition;
    }

    /**
     * Get available disk space on the system in percentage.
     *
     * @return int
     *
     * @throws LogbookHealthException
     */
    private function getDiskSpace(): int
    {
        $process = Process::fromShellCommandline('df -P .');
        $process->run();
        $output = $process->getOutput();

        preg_match('/(\d*)%/', $output, $matches);

        if (null === $matches)
            throw new LogbookHealthException();

        return (int) $matches[0];
    }
}
