<?php

namespace Solvrtech\Logbook\Transport\redis;

use Exception;
use Solvrtech\Logbook\LogbookConfig;
use Solvrtech\Logbook\Transport\AsyncTransportInterface;
use Solvrtech\Logbook\Transport\TransportInterface;

class RedisTransport implements TransportInterface, AsyncTransportInterface
{
    use LogbookConfig;

    private Connection $connection;

    public function __construct()
    {
        $this->connection = Connection::formOption($this->getRedisConfig());
    }

    /**
     * Send log to the redis with asynchronous behavior
     *
     * @param  string  $body
     * @param  array  $headers
     *
     * @return array
     */
    public function send(string $body, array $headers): array
    {
        try {
            $this->connection->add($body, $headers);
        } catch (Exception $exception) {
        }

        return json_decode($body, true);
    }

    /**
     * Retrieves log data from the redis
     *
     * @return array|null
     */
    public function get(): ?array
    {
        try {
            [$batch, $ids] = $this->connection->get();
        } catch (Exception $exception) {
            return null;
        }

        if (null === $batch) {
            return null;
        }

        return 0 < count($batch) ? [$batch, $ids] : null;
    }

    /**
     * Deletes items based on their IDs.
     *
     * @param  array|null  $ids
     */
    public function delete(?array $ids = null): void
    {
        try {
            $this->connection->ack($ids);
        } catch (Exception $exception) {
        }
    }
}
