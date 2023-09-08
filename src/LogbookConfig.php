<?php

namespace Solvrtech\Logbook;

use Exception;

trait LogbookConfig
{
    /**
     * Returns LogBook API URL from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getAPIUrl(): string
    {
        $config = config('logbook');

        if (isset($config['api']['url'])) {
            $url = $config['api']['url'];
            if (str_ends_with($url, '/')) {
                $url = substr($url, 0, -1);
            }

            return $url;
        }

        throw new Exception('Logbook API url not found');
    }

    /**
     * Returns LogBook API key from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getAPIKey(): string
    {
        $config = config('logbook');

        if (isset($config['api']['key'])) {
            return $config['api']['key'];
        }

        throw new Exception('Logbook API key was not found');
    }

    /**
     * Returns LogBook instance id from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getInstanceId(): string
    {
        $config = config('logbook');

        if (isset($config['instance_id'])) {
            return $config['instance_id'];
        }

        throw new Exception('Logbook instance_id was not found');
    }

    /**
     * Returns app and framework version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        $version = [
            'core' => "Laravel v".app()->version(),
        ];

        if (config()->has('app.version')) {
            $appVersion     = config('app.version');
            $version['app'] = is_string($appVersion) ? $appVersion : '';
        }

        return json_encode($version);
    }

    /**
     * Get the Redis configuration.
     *
     * @return array
     * @throws Exception
     */
    public function getRedisConfig(): array
    {
        $config = config('logbook');

        if ( ! isset($config['options']['redis'])) {
            throw new Exception('Logbook transport not found');
        }

        $redis             = $config['options']['redis'];
        $redis['password'] = ! empty($redis['password']) ? $redis['password'] : null;

        return $redis;
    }

    /**
     * Get the batch limit for database operations.
     *
     * @return int
     */
    public function databaseBatchLimit(): int
    {
        $config = config('logbook');

        return $config['options']['database']['batch'];
    }
}
