<?php

namespace Solvrtech\Logbook\Transport;

interface AsyncTransportInterface
{
    /**
     * Retrieves log data from redis or database
     *
     * @return array|null
     */
    public function get(): ?array;

    /**
     * Deletes log items based on their IDs.
     *
     * @param  array|null  $ids
     *
     * @return void
     */
    public function delete(?array $ids = null): void;
}
