<?php

namespace Solvrtech\Logbook\Service;

use DateTime;
use Solvrtech\Logbook\Check\CheckInterface;
use Solvrtech\Logbook\LogbookHealth;
use Solvrtech\Logbook\Model\ConditionModel;

class LogbookHealthService
{
    private LogbookHealth $health;
    private array $needToChecks;

    public function __construct(LogbookHealth $health)
    {
        $this->health = $health;
    }

    /**
     * Get all health check results.
     * 
     * @param array $needToChecks
     * 
     * @return array
     */
    public function getResults(array $needToChecks): array
    {
        self::setNeedToChecks($needToChecks);

        $availableChecks = $this->health->getChecks();
        $checks = [];

        foreach ($availableChecks as $check) {
            $result = self::run($check);

            if (null !== $result)
                $checks[] = $result;
        }

        return [
            'datetime' => (new DateTime())->format('Y-m-d H:i:s'),
            'checks' => $checks
        ];
    }

    /**
     * Run health check.
     * 
     * @param CheckInterface $check
     * 
     * @return ConditionModel|null
     */
    private function run(CheckInterface $check): ConditionModel|null
    {
        if (in_array($check->getKey(), $this->needToChecks))
            return $check->result();

        return null;
    }

    public function setNeedToChecks(array $checks): self
    {
        $this->needToChecks = $checks;

        return $this;
    }
}
