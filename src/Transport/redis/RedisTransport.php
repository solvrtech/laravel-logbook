<?php

namespace Solvrtech\Logbook\Transport\redis;

use Exception;
use RedisException;
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
     * @throws RedisException
     */
    public function send(string $body, array $headers): array
    {
        $this->connection->add($body, $headers);

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
     *
     * @return void
     * @throws RedisException
     */
    public function delete(?array $ids = null): void
    {
        $this->connection->ack($ids);
    }
}
