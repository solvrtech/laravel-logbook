<?php

namespace Solvrtech\Laravel\Logbook\Command;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\HttpClient\HttpClient;

class HealthStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logbook:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send client app health status to logbook app';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $httpClient = HttpClient::create();
        try {
            $httpClient->request(
                'POST',
                "{$this->getAPIUrl()}/api/health-status",
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'token' => $this->getAPIkey(),
                    ],
                    'body' => "",
                ]
            );
        } catch (Exception $e) {
        }

        return Command::SUCCESS;
    }

    /**
     * Get logbook API url from environment.
     *
     * @return string
     *
     * @throws Exception
     */
    private function getAPIUrl(): string
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
    private function getAPIKey(): string
    {
        $key = config('logbook.api.key');

        if (null === $key) {
            throw new Exception('Logbook key not found');
        }

        return $key;
    }
}
