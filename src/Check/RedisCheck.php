<?php

namespace Solvrtech\Logbook\Check;

use Exception;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Redis;
use Solvrtech\Logbook\Exception\LogbookHealthException;
use Solvrtech\Logbook\Model\ConditionModel;

class RedisCheck extends CheckService
{
    /**
     * {@inheritDoc}
     */
    public function getKey():string
    {
        return 'redis';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): ConditionModel
    {
        $condition = new ConditionModel();

        try {
            $redis = self::calculateRedisSize();

            $condition->setStatus(ConditionModel::OK)
                ->setMeta([
                    'redisSize' => $redis,
                    'unit' => 'Mb'
                ]);
        } catch (Exception $e) {
        }

        return $condition;
    }

    /**
     * Get redis size in megabytes.
     *
     * @return array
     *
     * @throws LogbookHealthException
     */
    private function calculateRedisSize(): array
    {
        $connections = self::getConnections();

        return array_map(function (Connection $connection) {
            $redisInfo = $connection->info();

            if ($connection instanceof PhpRedisConnection)
                return round(
                    $redisInfo['used_memory'] / 1048576,
                    2
                );

            if ($connection instanceof PredisConnection)
                return round(
                    $redisInfo['Memory']['used_memory'] / 1048576,
                    2
                );

            return null;
        }, $connections);
    }

    /**
     * Get all redis connections.
     *
     * @return array
     *
     * @throws LogbookHealthException
     */
    private function getConnections(): array
    {
        $client = Redis::client();
        $connections = Redis::connections();

        if (0 === count($connections))
            throw new LogbookHealthException();

        return $connections;
    }
}
