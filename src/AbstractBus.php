<?php

namespace Mrluke\Bus;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use ReflectionClass;

use Mrluke\Bus\Contracts\Bus;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Contracts\Intention;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Contracts\ShouldBeAsync;
use Mrluke\Bus\Exceptions\InvalidHandler;

/**
 * Abstract Bus class with basic implementations.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
abstract class AbstractBus implements Bus
{
    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The command to handler mapping for non-self-handling events.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * The pipeline instance for the bus.
     *
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * The pipes to send commands through before dispatching.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The process repository implementations.
     *
     * @var \Mrluke\Bus\Contracts\ProcessRepository
     */
    protected $processRepository;

    /**
     * The queue resolver callback.
     *
     * @var \Closure|null
     */
    protected $queueResolver;

    /**
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Closure|null                             $queueResolver
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct(Container $container, Closure $queueResolver = null)
    {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->pipeline = new Pipeline($container);

        $this->processRepository = $this->container->make(ProcessRepository::class);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Intention $intention): Process
    {
        $handler = $this->handler($intention);

        if ($intention instanceof ShouldBeAsync && $this instanceof HasAsyncProcesses) {
            return $this->runAsync($intention, $handler);
        }

        return $this->run($intention, $handler);
    }

    /**
     * @inheritDoc
     */
    public function hasHandler(Intention $intention): bool
    {
        return array_key_exists(get_class($intention), $this->handlers);
    }

    /**
     * @inheritDoc
     */
    public function handler(Intention $intention)
    {
        $handler = $this->handlers[get_class($intention)];

        if (is_array($handler)) {
            throw new InvalidHandler(
                sprintf('Invalid handler for [%s]. Single Handler required.', get_class($intention))
            );
        }

        $reflection = new ReflectionClass($handler);

        if (
            !$reflection->isInstantiable() ||
            !in_array(Handler::class, $reflection->getInterfaces())
        ) {
            throw new InvalidHandler('Handler must be an instance of %s', Handler::class);
        }

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public function map(array $map): Bus
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function pipeThrough(array $pipes): Bus
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Return bus name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    abstract protected function getBusName(): string;

    /**
     * Run handler synchronously.
     *
     * @param \Mrluke\Bus\Contracts\Intention $intention
     * @param \Mrluke\Bus\Contracts\Handler   $handler
     * @return \Mrluke\Bus\Contracts\Process
     */
    protected function run(Intention $intention, Handler $handler): Process
    {
        //TODO: Implement run method
    }

    /**
     * Run handler asynchronously.
     *
     * @param \Mrluke\Bus\Contracts\ShouldBeAsync $intention
     * @param \Mrluke\Bus\Contracts\Handler       $handler
     * @return \Mrluke\Bus\Contracts\Process
     */
    protected function runAsync(ShouldBeAsync $intention, Handler $handler): Process
    {
        //TODO: Implement runAsync method
    }
}
