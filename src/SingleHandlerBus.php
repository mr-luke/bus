<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Log\Logger;
use ReflectionClass;

use Mrluke\Bus\Contracts\AsyncBus;
use Mrluke\Bus\Contracts\Bus;
use Mrluke\Bus\Contracts\ForceSync;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Contracts\ShouldBeAsync;
use Mrluke\Bus\Contracts\Trigger;
use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingConfiguration;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\Extensions\ResolveDependencies;
use Mrluke\Bus\Extensions\TranslateResults;

/**
 * Abstract for single handler Bus.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
abstract class SingleHandlerBus implements Bus
{
    use ResolveDependencies, TranslateResults;

    /** Determine if process should be delete on success.
     *
     * @var bool
     */
    public bool $cleanOnSuccess = true;

    /**
     * Determine if Bus should stop executing on exception.
     *
     * @var bool
     */
    public bool $stopOnException = false;

    /**
     * Determine if Bus should throw if there's no handler to process.
     *
     * @var bool
     */
    public bool $throwWhenNoHandler = true;

    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected Container $container;

    /**
     * The command to handler mapping for non-self-handling events.
     *
     * @var array
     */
    protected array $handlers = [];

    /**
     * Instance of app logger.
     *
     * @var \Illuminate\Log\Logger
     */
    protected Logger $logger;

    /**
     * The process repository implementations.
     *
     * @var \Mrluke\Bus\Contracts\ProcessRepository
     */
    protected ProcessRepository $processRepository;

    /**
     * The queue resolver callback.
     *
     * @var \Closure|null
     */
    protected ?Closure $queueResolver;

    /**
     * @param \Mrluke\Bus\Contracts\ProcessRepository   $repository
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Log\Logger                    $logger
     * @param \Closure|null                             $queueResolver
     */
    public function __construct(
        ProcessRepository $repository,
        Container $container,
        Logger $logger,
        $queueResolver = null
    ) {
        $this->processRepository = $repository;

        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Instruction $instruction, Trigger $trigger = null): ?Process
    {
        if (is_null($trigger) && !$instruction instanceof Trigger) {
            throw new MissingConfiguration(
                sprintf(
                    'An instruction [%s] must implements [%s] contract to trigger handlers.',
                    get_class($instruction),
                    Trigger::class
                )
            );
        }
        $trigger = is_null($trigger) ? $instruction : $trigger;

        if (!$this->hasHandler($trigger)) {
            if (!$this->throwWhenNoHandler) {
                return null;
            }

            $this->throwOnMissingHandler($trigger);
        }

        $handlers = $this->handler($trigger);
        $process = $this->createProcess($instruction, $handlers);

        $shouldBeAsync = $instruction instanceof ShouldBeAsync || $this instanceof AsyncBus;
        foreach ($handlers as $class) {
            $reflection = new ReflectionClass($class);
            if ($shouldBeAsync && !$reflection->implementsInterface(ForceSync::class)) {
                /** @var Instruction $instruction */
                $this->runAsync(
                    $process,
                    $instruction,
                    $class
                );
            } else {
                $this->run(
                    $process,
                    $instruction,
                    $class
                );
            }
        }

        return $process;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function dispatchMultiple(Trigger $trigger, array $instructions): array
    {
        $processes = [];

        foreach ($instructions as $i) {
            $processes[] = $this->dispatch($i, $trigger);
        }

        return $processes;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function hasHandler(Trigger $trigger): bool
    {
        return array_key_exists(get_class($trigger), $this->handlers);
    }

    /**
     * @inheritDoc
     */
    public function handler(Trigger $trigger): array
    {
        if (!$this->hasHandler($trigger)) {
            throw new MissingHandler(
                sprintf('Given trigger [%s] is not registered.', get_class($trigger))
            );
        }

        $handler = $this->handlers[get_class($trigger)];

        if (is_array($handler)) {
            throw new InvalidHandler(
                sprintf(
                    'Invalid handler for [%s]. Single Handler required.',
                    get_class($trigger)
                )
            );
        }

        $reflection = new ReflectionClass($handler);

        if (
            !$reflection->isInstantiable() ||
            !$reflection->implementsInterface(Handler::class)
        ) {
            throw new InvalidHandler(
                sprintf('Handler must be an instance of %s', Handler::class)
            );
        }

        return [$handler];
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function map(array $map): Bus
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }

    /**
     * Return delay.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return Carbon|null
     */
    protected function considerDelay(Instruction $instruction): ?Carbon
    {
        /* @var HasAsyncProcesses $this */
        return property_exists($instruction, 'delay') ? $instruction->delay : null;
    }

    /**
     * Return queue name.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return string|null
     */
    protected function considerQueue(Instruction $instruction): ?string
    {
        /* @var HasAsyncProcesses $this */
        return property_exists($instruction, 'queue') ? $instruction->queue : $this->onQueue();
    }

    /**
     * Create process for instruction.
     *
     * @param Trigger|string                    $trigger
     * @param                                   $handler
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    protected function createProcess($trigger, $handler): Process
    {
        return $this->processRepository->create(
            $this->getBusName(),
            $trigger instanceof Trigger ? get_class($trigger) : (string)$trigger,
            is_array($handler) ? $handler : [$handler]
        );
    }

    /**
     * Return bus name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    abstract protected function getBusName(): string;

    /**
     * Push the instruction to Queue.
     *
     * @param string                            $id
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param string                            $handlerClass
     * @param bool                              $cleanOnSuccess
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function pushInstructionToQueue(
        string $id,
        Instruction $instruction,
        string $handlerClass,
        bool $cleanOnSuccess
    ): void {
        if (!$this instanceof HasAsyncProcesses) {
            throw new MissingConfiguration(
                sprintf(
                    'To use async instructions Bus has to be an instance of either [%s] or [%s]',
                    HasAsyncProcesses::class,
                    AsyncBus::class
                )
            );
        }

        $queue = call_user_func($this->queueResolver, $this->onQueue());
        $this->verifyQueueInstance($queue);

        $delay = $this->considerDelay($instruction);
        $queueName = $this->considerQueue($instruction);

        $job = new AsyncHandlerJob($id, $instruction, $handlerClass, $cleanOnSuccess);
        if ($queueName) {
            $job->onQueue($queueName);
        }

        if ($delay) {
            $job->delay($delay);
        }

        $queue->push($job);
    }

    /**
     * Run handler synchronously.
     *
     * @param \Mrluke\Bus\Contracts\Process     $process
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param string                            $handlerClass
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \ReflectionException
     */
    protected function run(
        Process $process,
        Instruction $instruction,
        string $handlerClass
    ): void {
        if ($process->qualifyToStart()) {
            $this->processRepository->start($process);
        }

        $this->runSingleProcess(
            $process,
            $instruction,
            $this->resolveClass($this->container, $handlerClass)
        );

        if ($process->qualifyAsFinished()) {
            $this->processRepository->finish($process);

            if ($this->cleanOnSuccess) {
                $this->processRepository->delete($process);
            }
        }
    }

    /**
     * Run handler asynchronously.
     *
     * @param \Mrluke\Bus\Contracts\Process     $process
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param string                            $handlerClass
     * @return void
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function runAsync(
        Process $process,
        Instruction $instruction,
        string $handlerClass
    ): void {
        $this->pushInstructionToQueue(
            $process->id(),
            $instruction,
            $handlerClass,
            $this->cleanOnSuccess
        );
    }

    /**
     * Run single process.
     *
     * @param \Mrluke\Bus\Contracts\Process     $process
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param \Mrluke\Bus\Contracts\Handler     $handler
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     */
    protected function runSingleProcess(
        Process $process,
        Instruction $instruction,
        Handler $handler
    ): void {
        try {
            $result = $handler->handle($instruction);

            $this->processRepository->applySubResult(
                $process,
                get_class($handler),
                Process::Succeed,
                $this->processResult($result)
            );

        } catch (Exception $e) {
            $this->logger->error($e);
            $this->processRepository->applySubResult(
                $process,
                get_class($handler),
                Process::Failed,
                $e->getMessage()
            );

            if ($this->stopOnException) {
                throw $e;
            }
        }
    }

    /**
     * Throw exception when handler is missing.
     *
     * @param \Mrluke\Bus\Contracts\Trigger $trigger
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    protected function throwOnMissingHandler(Trigger $trigger): void
    {
        throw new MissingHandler(
            sprintf('Missing handler for the instruction [%s]', get_class($trigger))
        );
    }

    /**
     * Verify if given queue is proper instance.
     *
     * @param $queue
     * @return void
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     * @codeCoverageIgnore
     */
    private function verifyQueueInstance($queue): void
    {
        if (!$queue instanceof Queue) {
            throw new MissingConfiguration('Queue resolver did not return a Queue implementation.');
        }
    }
}
