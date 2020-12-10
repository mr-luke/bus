<?php

namespace Mrluke\Bus;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

use Mrluke\Bus\Contracts\Bus;
use Mrluke\Bus\Contracts\Intention;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;

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
        // TODO: Implement dispatch() method.
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
}
