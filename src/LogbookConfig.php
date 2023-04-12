<?php

namespace Solvrtech\Logbook;

use Exception;

trait LogbookConfig
{
    /**
     * Get logbook API url from environment.
     *
     * @param array|null $config
     * 
     * @return string
     *
     * @throws Exception
     */
    public function getAPIUrl(array|null $config = null): string
    {
        if (null === $config)
            $config = config()->has('logging.channels.logbook') ?
                config('logging.channels.logbook') :
                null;

        if (isset($config['api']['url'])) {
            $url = $config['api']['url'];
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
     * @param array|null $config
     * 
     * @return string
     *
     * @throws Exception
     */
    public function getAPIKey(array|null $config = null): string
    {
        if (null === $config)
            $config = config()->has('logging.channels.logbook') ?
                config('logging.channels.logbook') :
                null;

        if (isset($config['api']['key']))
            return $config['api']['key'];

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
