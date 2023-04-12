<?php

namespace Solvrtech\Logbook\Check;

use Solvrtech\Logbook\Model\ConditionModel;

abstract class CheckService implements CheckInterface
{
    public static function new(): static
    {
        return app(static::class);
    }

    /**
     * Get key of the check object.
     * 
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * Running check
     * 
     * @return ConditionModel
     */
    abstract public function run(): ConditionModel;

    /**
     * {@inheritDoc}
     */
    public function result(): ConditionModel
    {
        $condition = $this->run();
        $condition->setKey($this->getKey());

        return $condition;
    }
}
