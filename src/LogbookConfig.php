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
        if (config()->has('logbook.api.url')) {
            $url = config('logbook.api.url');
            if (str_ends_with($url, '/')) {
                $url = substr($url, 0, -1);
            }

            return $url;
        }

        throw new Exception('Logbook url not found');
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
        if (config()->has('logbook.api.key')) {
            return config('logbook.api.key');
        }

        throw new Exception('Logbook key not found');
    }

    /**
     * Get app and framework version.
     * 
     * @return string
     */
    public function getVersion(): string
    {
        $version = [
            'core' => "Laravel v" . app()->version()
        ];

        if (config()->has('app.version')) {
            $appVersion = config('app.version');
            $version['app'] = is_string($appVersion) ? $appVersion : '';
        }

        return json_encode($version);
    }
}
