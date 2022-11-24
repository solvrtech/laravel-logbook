<?php

namespace Solvrtech\Laravel\Logbook;

use Exception;

trait LogbookConfig
{
    /**
     * Get logbook API url from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getAPIUrl(): string
    {
        $url = config('logbook.api.url');

        if (null === $url) {
            throw new Exception('Logbook url not found');
        }

        if ('/' === substr($url, -1)) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    /**
     * Get logbook API key from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getAPIKey(): string
    {
        $key = config('logbook.api.key');

        if (null === $key) {
            throw new Exception('Logbook key not found');
        }

        return $key;
    }

    /**
     * Get app and framework version.
     * 
     * @return string
     */
    public function getVersion(): string
    {
        $vesion = [
            'laravel' => app()->version()
        ];

        $appVersion = config('app.version');
        if (null !== $appVersion && is_string($appVersion)) {
            $vesion['app'] = $appVersion;
        }

        return json_encode($vesion);
    }
}
