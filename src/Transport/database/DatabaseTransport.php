<?php

namespace Solvrtech\Logbook\Transport\database;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Solvrtech\Logbook\Exception\TransportException;
use Solvrtech\Logbook\LogbookConfig;
use Solvrtech\Logbook\Transport\AsyncTransportInterface;
use Solvrtech\Logbook\Transport\TransportInterface;

class DatabaseTransport implements TransportInterface, AsyncTransportInterface
{
    use LogbookConfig;

    private $log_table = 'logbook_logs';

    /**
     * Send log to the database with asynchronous behavior
     *
     * @param string $body
     * @param array $headers
     *
     * @return array
     */
    public function send(string $body, array $headers): array
    {
        try {
            $this->createLogsTable();
            DB::table($this->log_table)->insert([
                'logs' => json_encode(
                    [
                        'body' => $body,
                        'headers' => $headers,
                    ]
                ),
            ]);
        } catch (Exception $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return json_decode($body, true);
    }

    /**
     * Creates the logs table.
     */
    private function createLogsTable(): void
    {
        if (!Schema::hasTable('logbook_logs')) {
            Schema::create('logbook_logs', function (Blueprint $table) {
                $table->id();
                $table->json('logs');
                $table->timestamp('sent_at')->nullable();
            });
        }
    }

    /**
     * Retrieves log data from the database.
     *
     * @return array|null
     */
    public function get(): ?array
    {
        $this->createLogsTable();
        $response = DB::table($this->log_table)
            ->whereNull('sent_at')
            ->limit($this->databaseBatchLimit())
            ->get();

        if (null === $response) {
            return null;
        }

        $batch = [];
        $ids = [];

        foreach ($response as $key => $item) {
            $item->logs = json_decode($item->logs, true);
            $ids[] = $item->id;
            $batch['headers'] = $item->logs['headers'];
            $batch['logs'][$key] = [
                'id' => $item->id,
                'log' => json_decode($item->logs['body'], true),
            ];
        }

        //mark as sent
        $this->markAsSent($ids);

        return 0 < count($batch) ? [$batch, $ids] : null;
    }

    /**
     * Marks logs as sent based on their IDs.
     *
     * @param array $ids
     *
     * @return void
     */
    private function markAsSent(array $ids): void
    {
        foreach ($ids as $id) {
            DB::table($this->log_table)->where('id', $id)->update([
                'sent_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Deletes sent logs from the database.
     *
     * @param array|null $ids
     *
     * @return void
     */
    public function delete(?array $ids = null): void
    {
        DB::table($this->log_table)->whereNotNull('sent_at')->delete();
    }
}
