<?php

namespace Solvrtech\Logbook\Check;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Solvrtech\Logbook\Exception\LogbookHealthException;
use Solvrtech\Logbook\Model\ConditionModel;

class DataBaseCheck extends CheckService
{
    /**
     * {@inheritDoc}
     */
    public function getKey(): string
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
                    'unit' => 'Mb',
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
            if ($connection instanceof PostgresConnection) {
                return $this->checkPostgresSize($connection, $connection->getDatabaseName());
            } else {
                return $this->checkMySqlSize($connection, $connection->getDatabaseName());
            }
        }, $connections);
    }

    /**
     * Get all database connections.
     *
     * @return array
     */
    private function getConnections(): array
    {
        return [
            'default' => DB::connection(),
        ];
    }

    /**
     * Checks the size of the Postgres database.
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     *
     * @return float|int
     *
     * @throws LogbookHealthException
     */
    private function checkPostgresSize(ConnectionInterface $connection, string $dbName): float|int
    {
        try {
            $result = $connection->selectOne(
                "SELECT ROUND(pg_database_size(:dbName)/ 1048576, 2) as size",
            );

            return $result->size;
        } catch (Exception $e) {
            throw new LogbookHealthException();
        }
    }

    /**
     * Checks the size of the MySql database.
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     *
     * @return float|int
     *
     * @throws LogbookHealthException
     */
    private function checkMySqlSize(ConnectionInterface $connection, string $dbName): float|int
    {
        try {
            (array)$result = $connection->select(
                "SELECT table_schema '{$dbName}', ROUND(SUM(data_length + index_length) / 1048576, 2) as size FROM information_schema.tables GROUP BY table_schema",
            );

            return array_sum(array_column($result, 'size'));
        } catch (Exception $e) {
            throw new LogbookHealthException();
        }
    }
}
