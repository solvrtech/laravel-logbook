<?php

namespace Solvrtech\Logbook\Transport\redis;

use Closure;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;
use RedisSentinel;
use Solvrtech\Logbook\Exception\InvalidArgumentException;
use Solvrtech\Logbook\Exception\LogicException;
use Solvrtech\Logbook\Exception\TransportException;
use Throwable;
use function defined;
use function is_array;
use function is_string;

class Connection
{
    private const DEFAULT_OPTIONS = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'stream' => 'logs',
        'group' => 'logbook',
        'consumer' => 'consumer',
        'batch' => 15,
        'auto_setup' => true,
        'stream_max_entries' => 0,
        // any value higher than 0 defines an approximate maximum number of stream entries
        'dbindex' => 0,
        'redeliver_timeout' => 3600,
        // Timeout before redeliver messages still in pending state (seconds)
        'claim_interval' => 60000,
        // Interval by which pending/abandoned messages should be checked
        'lazy' => false,
        'auth' => null,
        'serializer' => Redis::SERIALIZER_PHP,
        'sentinel_master' => null,
        // String, master to look for (optional, default is NULL meaning Sentinel support is disabled)
        'timeout' => 0.0,
        // Float, value in seconds (optional, default is 0 meaning unlimited)
        'read_timeout' => 0.0,
        //  Float, value in seconds (optional, default is 0 meaning unlimited)
        'retry_interval' => 0,
        //  Int, value in milliseconds (optional, default is 0)
        'persistent_id' => null,
        // String, persistent connection id (optional, default is NULL meaning not persistent)
        'ssl' => null,
        // see https://php.net/context.ssl
    ];

    private Redis|RedisCluster|Closure $redis;
    private string $stream;
    private string $group;
    private string $consumer;
    private string $queue;
    private int $batch;
    private bool $autoSetup;
    private int $maxEntries;

    public function __construct(array $opts, Redis|RedisCluster $redis = null)
    {
        if (version_compare(phpversion('redis'), '4.3.0', '<')) {
            throw new LogicException('The redis transport requires php-redis 4.3.0 or higher.');
        }

        $opts += self::DEFAULT_OPTIONS;
        $host = $opts['host'];
        $port = $opts['port'];
        $auth = $opts['auth'];
        $sentinelMaster = $opts['sentinel_master'];

        if (null !== $sentinelMaster && !class_exists(RedisSentinel::class)) {
            throw new InvalidArgumentException('Redis Sentinel support requires the "redis" extension v5.2 or higher.');
        }

        if (null !== $sentinelMaster && ($redis instanceof RedisCluster || is_array($host))) {
            throw new InvalidArgumentException(
                'Cannot configure Redis Sentinel and Redis Cluster instance at the same time.'
            );
        }

        if (is_array($host) || $redis instanceof RedisCluster) {
            $hosts = is_string($host) ? [$host.':'.$port] : $host; // Always ensure we have an array
            $this->redis = static function () use ($redis, $hosts, $auth, $opts) {
                return self::initializeRedisCluster($redis, $hosts, $auth, $opts);
            };
        } else {
            if (null !== $sentinelMaster) {
                $sentinelClient = new RedisSentinel(
                    $host,
                    $port,
                    $opts['timeout'],
                    $opts['persistent_id'],
                    $opts['retry_interval'],
                    $opts['read_timeout']
                );

                if (!$address = $sentinelClient->getMasterAddrByName($sentinelMaster)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Failed to retrieve master information from master name "%s" and address "%s:%d".',
                            $sentinelMaster,
                            $host,
                            $port
                        )
                    );
                }

                [$host, $port] = $address;
            }

            $this->redis = static function () use ($redis, $host, $port, $auth, $opts) {
                return self::initializeRedis($redis ?? new Redis(), $host, $port, $auth, $opts);
            };
        }

        if (!$opts['lazy']) {
            $this->getRedis();
        }

        $key = 'stream';
        if ('' === $opts[$key]) {
            throw new InvalidArgumentException(sprintf('"%s" should be configured, got an empty string.', $key));
        }

        $this->stream = $opts['stream'];
        $this->group = $opts['group'];
        $this->consumer = $opts['consumer'];
        $this->queue = $this->stream.'__queue';
        $this->batch = $opts['batch'];
        $this->autoSetup = $opts['auto_setup'];
        $this->maxEntries = $opts['stream_max_entries'];
    }

    /**
     * @param string|string[]|null $auth
     *
     * @throws RedisClusterException
     */
    private static function initializeRedisCluster(
        ?RedisCluster $redis,
        array $hosts,
        string|array|null $auth,
        array $params
    ): RedisCluster {
        $redis ??= new RedisCluster(
            null,
            $hosts,
            $params['timeout'],
            $params['read_timeout'],
            (bool)($params['persistent'] ?? false),
            $auth,
            ...defined('Redis::SCAN_PREFIX') ? [$params['ssl'] ?? null] : []
        );
        $redis->setOption(Redis::OPT_SERIALIZER, $params['serializer']);

        return $redis;
    }

    /**
     * @param string|string[]|null $auth
     *
     * @throws RedisException
     */
    private static function initializeRedis(
        Redis $redis,
        string $host,
        int $port,
        string|array|null $auth,
        array $params
    ): Redis {
        $connect = isset($params['persistent_id']) ? 'pconnect' : 'connect';
        $redis->{$connect}(
            $host,
            $port,
            $params['timeout'],
            $params['persistent_id'],
            $params['retry_interval'],
            $params['read_timeout'],
            ...defined('Redis::SCAN_PREFIX') ? [['stream' => $params['ssl'] ?? null]] : []
        );

        $redis->setOption(Redis::OPT_SERIALIZER, $params['serializer']);

        if (null !== $auth && !$redis->auth($auth)) {
            throw new InvalidArgumentException('Redis connection failed: '.$redis->getLastError());
        }

        if (null !== $params['dbindex'] && !$redis->select($params['dbindex'])) {
            throw new InvalidArgumentException('Redis connection failed: '.$redis->getLastError());
        }

        return $redis;
    }

    private function getRedis(): Redis|RedisCluster
    {
        if ($this->redis instanceof Closure) {
            $this->redis = ($this->redis)();
        }

        return $this->redis;
    }

    public static function formOption(array $options): self
    {
        $opts = self::DEFAULT_OPTIONS;

        $opts['host'] = $options['host'];
        $opts['auth'] = $options['password'];
        $opts['port'] = $options['port'];
        $opts['stream'] = $options['stream'];
        $opts['batch'] = $options['batch'];

        return new self($opts);
    }

    /**
     * @throws RedisException
     */
    public function get(): ?array
    {
        if ($this->autoSetup) {
            $this->setup();
        }

        $redis = $this->getRedis();

        try {
            $logs = $redis->xreadgroup(
                $this->group,
                $this->consumer,
                [$this->stream => '>'],
                $this->batch
            );
        } catch (RedisException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        if (false === $logs) {
            if ($error = $redis->getLastError() ?: null) {
                $redis->clearLastError();
            }

            throw new TransportException($error ?? 'Could not read log from the redis stream.');
        }

        $batch = [];
        $ids = [];

        foreach ($logs[$this->stream] ?? [] as $key => $log) {
            $log['log'] = json_decode($log['log'], true);

            $ids[] = $key;
            $batch['headers'] = $log['log']['headers'];
            $batch['logs'][$key] = [
                'id' => $key,
                'log' => json_decode($log['log']['body'], true),
            ];
        }

        return [$batch, $ids];
    }

    /**
     * @throws RedisException
     */
    public function setup(): void
    {
        $redis = $this->getRedis();

        try {
            $redis->xgroup('CREATE', $this->stream, $this->group, 0, true);
        } catch (RedisException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        if ($redis->getLastError()) {
            $redis->clearLastError();
        }

        $this->autoSetup = false;
    }

    /**
     * @throws RedisException
     */
    public function add(string $body, array $headers): string
    {
        if ($this->autoSetup) {
            $this->setup();
        }
        $redis = $this->getRedis();

        try {
            $log = json_encode([
                'body' => $body,
                'headers' => $headers,
            ]);

            if (false === $log) {
                throw new TransportException(json_last_error_msg());
            }

            if ($this->maxEntries) {
                $added = $redis->xadd($this->stream, '*', ['log' => $log], $this->maxEntries, true);
            } else {
                $added = $redis->xadd($this->stream, '*', ['log' => $log]);
            }

            $id = $added;
        } catch (RedisException $e) {
            if ($error = $redis->getLastError() ?: null) {
                $redis->clearLastError();
            }
            throw new TransportException($error ?? $e->getMessage(), 0, $e);
        }

        if (!$added) {
            if ($error = $redis->getLastError() ?: null) {
                $redis->clearLastError();
            }
            throw new TransportException($error ?? 'Could not add a log to the redis stream.');
        }

        return $id;
    }

    /**
     * @throws RedisException
     */
    public function ack(array $ids): void
    {
        $redis = $this->getRedis();

        try {
            $acknowledged = $redis->xack($this->stream, $this->group, $ids);
            $acknowledged = $redis->xdel($this->stream, $ids) && $acknowledged;
        } catch (RedisException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        if (!$acknowledged) {
            if ($error = $redis->getLastError() ?: null) {
                $redis->clearLastError();
            }
            throw new TransportException($error ?? sprintf('Could not acknowledge redis log "%s".', $ids));
        }
    }

    /**
     * @throws RedisException
     */
    public function cleanup(): void
    {
        static $unlink = true;
        $redis = $this->getRedis();

        if ($unlink) {
            try {
                $unlink = false !== $redis->unlink($this->stream, $this->queue);
            } catch (Throwable) {
                $unlink = false;
            }
        }

        if (!$unlink) {
            $redis->del($this->stream, $this->queue);
        }
    }
}