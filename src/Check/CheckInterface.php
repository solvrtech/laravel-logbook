<?php

namespace Solvrtech\Logbook\Check;

use Solvrtech\Logbook\Model\ConditionModel;

interface CheckInterface
{
    /**
     * Returns the health checks result.
     * 
     * @return ConditionModel
     */
    public function result(): ConditionModel;
}
