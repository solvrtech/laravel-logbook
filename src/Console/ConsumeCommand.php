<?php

namespace Solvrtech\Logbook\Console;

use Exception;
use Illuminate\Console\Command;
use Solvrtech\Logbook\Transport\AsyncTransportInterface;
use Solvrtech\Logbook\Transport\database\DatabaseTransport;
use Solvrtech\Logbook\Transport\redis\RedisTransport;
use Solvrtech\Logbook\Transport\TransportInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logbook:log:consume';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume logs';

    private TransportInterface $transport;

    public function __construct(
        TransportInterface $transport
    ) {
        $this->transport = $transport;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (true) {
            if (!$this->transport instanceof AsyncTransportInterface) {
                sleep(60);
                continue;
            }

            [$batch, $ids] = $this->transport->get();

            while (null !== $batch) {
                try {
                    self::send($batch, $ids);
                } catch (Exception $exception) {
                }

                [$batch, $ids] = $this->transport->get();
            }

            if ($this->transport instanceof DatabaseTransport) {
                $this->transport->delete();
            }

            sleep(60);
        }

        return Command::SUCCESS;
    }

    /**
     * Try to send the batch of logs into the logbook; if successful, mark it as sent all logs
     *
     * @param array $logs
     * @param array $ids
     */
    private function send(array $logs, array $ids): void
    {
        $headers = $logs['headers'];
        unset($headers['url']);
        $apiUrl = $logs['headers']['url'];
        unset($logs['headers']);

        $httpClient = HttpClient::create();
        try {
            $httpClient->request(
                'POST',
                "{$apiUrl}/api/log/save/batch",
                [
                    'headers' => $headers,
                    'body' => json_encode($logs),
                ]
            );
        } catch (Exception|TransportExceptionInterface $exception) {
        }

        if ($this->transport instanceof RedisTransport) {
            $this->transport->delete($ids);
        }
    }
}