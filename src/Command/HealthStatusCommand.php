<?php

namespace Solvrtech\Laravel\Logbook\Command;

use Exception;
use Illuminate\Console\Command;
use Solvrtech\Laravel\Logbook\LogbookConfig;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HealthStatusCommand extends Command
{
    use LogbookConfig;

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
     *
     * @throws TransportExceptionInterface
     */
    public function handle(): int
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
                        'x-lb-token' => $this->getAPIkey(),
                        'x-lb-version' => $this->getVersion()
                    ]
                ]
            );
        } catch (Exception $e) {
        }

        return Command::SUCCESS;
    }
}
