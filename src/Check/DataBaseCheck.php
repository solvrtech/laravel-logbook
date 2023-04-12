<?php

namespace Solvrtech\Logbook\Check;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Solvrtech\Logbook\Exception\LogbookHealthException;
use Solvrtech\Logbook\Model\ConditionModel;

class DataBaseCheck extends CheckService
{
    /**
     * {@inheritDoc}
     */
    public function getKey():string
    {
        return 'database';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): ConditionModel
    {
        $condition = new ConditionModel();

        try {
            $database = self::calculateDBSize();

            $condition->setStatus(ConditionModel::OK)
                ->setMeta([
                    'databaseSize' => $database,
                    'unit' => 'Mb'
                ]);
        } catch (Exception $e) {
        }

        return $condition;
    }

    /**
     * Get database size in megabytes.
     *
     * @return array
     *
     * @throws LogbookHealthException
     */
    private function calculateDBSize(): array
    {
        $connections = self::getConnections();

        return array_map(function (ConnectionInterface $connection) {
            try {
                (array) $result =  $connection->select(
                    "SELECT table_schema '{$connection->getDatabaseName()}', ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size FROM information_schema.tables GROUP BY table_schema",
                );

                return array_sum(array_column($result, 'size'));
            } catch (Exception $e) {
                throw new LogbookHealthException();
            }
        }, $connections);
    }

    /**
     * Get all database connections.
     *
     * @return array
     *
     * @throws LogbookHealthException
     */
    private function getConnections(): array
    {
        DB::getPdo();
        $connection = DB::getConnections();

        if (0 === count($connection))
            throw new LogbookHealthException();

        return $connection;
    }
}
