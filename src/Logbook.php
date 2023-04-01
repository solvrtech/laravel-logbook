<?php

namespace Solvrtech\Laravel\Logbook;

use Closure;
use Throwable;
use Illuminate\Log\Logger;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Illuminate\Log\LogManager;
use Monolog\Logger as Monolog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Solvrtech\Laravel\Logbook\Handler\LogbookHandler;
use Solvrtech\Laravel\Logbook\Processor\LogbookProcessor;


class Logbook extends LogManager
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * @inheritDoc
     */
    public function build(array $config)
    {
        unset($this->channels['ondemand']);

        return $this->get('ondemand', $config);
    }

    /**
     * @inheritDoc
     */
    public function stack(array $channels, $channel = null)
    {
        return (new Logger(
            $this->createStackDriver(compact('channels', 'channel')),
            $this->app['events']
        ))->withContext($this->sharedContext);
    }

    /**
     * @inheritDoc
     */
    protected function createStackDriver(array $config)
    {
        if (is_string($config['channels'])) {
            $config['channels'] = explode(',', $config['channels']);
        }

        $handlers = collect($config['channels'])->flatMap(function ($channel) {
            return $channel instanceof LoggerInterface
                ? $channel->getHandlers()
                : $this->channel($channel)->getHandlers();
        })->all();

        $processors = collect($config['channels'])->flatMap(function ($channel) {
            return $channel instanceof LoggerInterface
                ? $channel->getProcessors()
                : $this->channel($channel)->getProcessors();
        })->all();

        if ($config['ignore_exceptions'] ?? false) {
            $handlers = [new WhatFailureGroupHandler($handlers)];
        }

        return new Monolog($this->parseChannel($config), $handlers, $processors);
    }

    /**
     * @inheritDoc
     */
    public function channel($channel = null)
    {
        return $this->driver($channel);
    }

    /**
     * @inheritDoc
     */
    public function driver($driver = null)
    {
        return $this->get($this->parseDriver($driver));
    }

    /**
     * @inheritDoc
     */
    protected function get($name, ?array $config = null)
    {
        try {
            return $this->channels[$name] ?? with($this->resolve($name, $config), function ($logger) use ($name) {
                return $this->channels[$name] = $this->tap($name, new Logger($logger, $this->app['events']))->withContext($this->sharedContext);
            });
        } catch (Throwable $e) {
            return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            });
        }
    }

    /**
     * @inheritDoc
     */
    protected function tap($name, Logger $logger)
    {
        foreach ($this->configurationFor($name)['tap'] ?? [] as $tap) {
            [$class, $arguments] = $this->parseTap($tap);

            $this->app->make($class)->__invoke($logger, ...explode(',', $arguments));
        }

        return $logger;
    }

    /**
     * @inheritDoc
     */
    protected function parseTap($tap)
    {
        return parent::parseTap($tap);
    }

    /**
     * @inheritDoc
     */
    protected function resolve($name, ?array $config = null)
    {
        $config ??= $this->configurationFor($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Log [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Create an instance of the logbook driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createLogbookDriver(array $config)
    {
        return new Monolog(
            $this->parseChannel($config),
            [new LogbookHandler($config, $this->level($config))],
            [new LogbookProcessor()]
        );
    }

    /**
     * @inheritDoc
     */
    protected function prepareHandlers(array $handlers)
    {
        foreach ($handlers as $key => $handler) {
            $handlers[$key] = $this->prepareHandler($handler);
        }

        return $handlers;
    }

    /**
     * @inheritDoc
     */
    protected function prepareHandler(HandlerInterface $handler, array $config = [])
    {
        if (isset($config['action_level'])) {
            $handler = new FingersCrossedHandler(
                $handler,
                $this->actionLevel($config),
                0,
                true,
                $config['stop_buffering'] ?? true
            );
        }

        if (!$handler instanceof FormattableHandlerInterface) {
            return $handler;
        }

        if (!isset($config['formatter'])) {
            $handler->setFormatter($this->formatter());
        } elseif ($config['formatter'] !== 'default') {
            $handler->setFormatter($this->app->make($config['formatter'], $config['formatter_with'] ?? []));
        }

        return $handler;
    }

    /**
     * @inheritDoc
     */
    protected function formatter()
    {
        return tap(new LineFormatter(null, $this->dateFormat, true, true), function ($formatter) {
            $formatter->includeStacktraces();
        });
    }

    /**
     * @inheritDoc
     */
    public function shareContext(array $context)
    {
        foreach ($this->channels as $channel) {
            $channel->withContext($context);
        }

        $this->sharedContext = array_merge($this->sharedContext, $context);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sharedContext()
    {
        return parent::sharedContext();
    }

    /**
     * @inheritDoc
     */
    public function flushSharedContext()
    {
        $this->sharedContext = [];

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function getFallbackChannelName()
    {
        return parent::getFallbackChannelName();
    }

    /**
     * @inheritDoc
     */
    protected function configurationFor($name)
    {
        return parent::configurationFor($name);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDriver()
    {
        return parent::getDefaultDriver();
    }

    /**
     * @inheritDoc
     */
    public function setDefaultDriver($name)
    {
        parent::setDefaultDriver($name);
    }

    /**
     * @inheritDoc
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function forgetChannel($driver = null)
    {
        $driver = $this->parseDriver($driver);

        if (isset($this->channels[$driver])) {
            unset($this->channels[$driver]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function parseDriver($driver)
    {
        $driver ??= $this->getDefaultDriver();

        if ($this->app->runningUnitTests()) {
            $driver ??= 'null';
        }

        return $driver;
    }

    /**
     * @inheritDoc
     */
    public function getChannels()
    {
        return parent::getChannels();
    }

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = []): void
    {
        $this->driver()->emergency($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = []): void
    {
        $this->driver()->alert($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = []): void
    {
        $this->driver()->critical($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = []): void
    {
        $this->driver()->error($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = []): void
    {
        $this->driver()->warning($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = []): void
    {
        $this->driver()->notice($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = []): void
    {
        $this->driver()->info($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = []): void
    {
        $this->driver()->debug($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
