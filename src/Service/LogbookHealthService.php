<?php

namespace Solvrtech\Logbook\Service;

use DateTime;
use Solvrtech\Logbook\Check\CheckInterface;
use Solvrtech\Logbook\LogbookHealth;

class LogbookHealthService
{
    private LogbookHealth $health;

    public function __construct(LogbookHealth $health)
    {
        $this->health = $health;
    }

    /**
     * Get all health check results.
     *
     * @return array
     */
    public function getResults(): array
    {
        $results = array_map(function (CheckInterface $check) {
            return $check->result();
        }, $this->health->getChecks());

        return [
            'datetime' => (new DateTime())->format('Y-m-d H:i:s'),
            'checks' => $results
        ];
    }
}
