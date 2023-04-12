<?php

namespace Solvrtech\Logbook\Check;

use Exception;
use Illuminate\Support\Facades\Cache;
use Ketut\RandomString\Random;
use Solvrtech\Logbook\Model\ConditionModel;

class CacheCheck extends CheckService
{
    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return 'cache';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): ConditionModel
    {
        $condition = new ConditionModel();

        try {
            $status = self::canStoringItem() ?
                ConditionModel::OK :
                ConditionModel::FAILED;

            $condition->setStatus($status);
        } catch (Exception $e) {
        }

        return $condition;
    }

    /**
     * Try to store an item in the cache.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function canStoringItem(): bool
    {
        $expectedValue = (new Random)->lowercase()->length(5)->generate();
        $key = 'logbook-health.check.' . self::getKey();

        Cache::put($key, $expectedValue);
        $actualValue = Cache::get($key);

        return $expectedValue === $actualValue;
    }
}
