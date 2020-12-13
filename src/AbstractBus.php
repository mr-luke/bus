<?php

namespace Mrluke\Bus;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Log\Logger;
use Illuminate\Pipeline\Pipeline;
use ReflectionClass;

use Mrluke\Bus\Contracts\Bus;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Contracts\ShouldBeAsync;
use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingConfiguration;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\Extensions\TranslateResults;

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
    use TranslateResults;

    /** Determine if process should be delete on success.
     *
     * @var bool
     */
    protected $cleanOnSuccess = true;

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
     * Instance of app logger.
     *
     * @var \Illuminate\Log\Logger
     */
    protected $logger;

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
     * Return queue connection name.
     *
     * @var null
     */
    protected $queueConnection = null;

    /**
     * The queue resolver callback.
     *
     * @var \Closure|null
     */
    protected $queueResolver;

    /**
     * @param \Mrluke\Bus\Contracts\ProcessRepository   $repository
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Pipeline\Pipeline             $pipeline
     * @param \Illuminate\Log\Logger                    $logger
     * @param \Closure|null                             $queueResolver
     */
    public function __construct(
        ProcessRepository $repository,
        Container $container,
        Pipeline $pipeline,
        Logger $logger,
        $queueResolver = null
    ) {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->pipeline = $pipeline;
        $this->logger = $logger;

        $this->processRepository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Instruction $instruction, bool $cleanOnSuccess = null): Process
    {
        if (!$this->hasHandler($instruction)) {
            throw new MissingHandler(
                sprintf('Missing handler for the instruction [%s]', get_class($instruction))
            );
        }

        $handler = $this->handler($instruction);

        if ($instruction instanceof ShouldBeAsync && $this instanceof HasAsyncProcesses) {
            /** @var Instruction $instruction */
            return $this->runAsync(
                $instruction,
                $handler,
                $this->considerCleaning($cleanOnSuccess)
            );
        }

        return $this->run($instruction, $handler, $this->considerCleaning($cleanOnSuccess));
    }

    /**
     * @inheritDoc
     */
    public function hasHandler(Instruction $instruction): bool
    {
        return array_key_exists(get_class($instruction), $this->handlers);
    }

    /**
     * @inheritDoc
     */
    public function handler(Instruction $instruction)
    {
        $handler = $this->handlers[get_class($instruction)];

        if (is_array($handler)) {
            throw new InvalidHandler(
                sprintf(
                    'Invalid handler for [%s]. Single Handler required.',
                    get_class($instruction)
                )
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
     * Consider if the process should be delete on success.
     *
     * @param bool|null $clean
     * @return bool
     */
    protected function considerCleaning(?bool $clean): bool
    {
        return $clean !== null ? $clean : $this->cleanOnSuccess;
    }

    /**
     * Return delay.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return Carbon|null
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function considerDelay(Instruction $instruction): ?string
    {
        if (!$this instanceof HasAsyncProcesses) {
            throw new MissingConfiguration(
                sprintf(
                    'To use async instructions Bus has to be an instance of [%s]',
                    HasAsyncProcesses::class
                )
            );
        }

        return property_exists($instruction, 'delay') ? $instruction->delay : $this->delay();
    }

    /**
     * Return queue name.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return string|null
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function considerQueue(Instruction $instruction): ?string
    {
        if (!$this instanceof HasAsyncProcesses) {
            throw new MissingConfiguration(
                sprintf(
                    'To use async instructions Bus has to be an instance of [%s]',
                    HasAsyncProcesses::class
                )
            );
        }

        return property_exists($instruction, 'queue') ? $instruction->queue : $this->onQueue();
    }

    /**
     * Create process for instruction.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param                                   $handler
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    protected function createProcess(Instruction $instruction, $handler): Process
    {
        return $this->processRepository->create(
            $this->getBusName(),
            get_class($instruction),
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
     * @param \Mrluke\Bus\Contracts\Handler     $handler
     * @param bool                              $cleanOnSuccess
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function pushInstructionToQueue(
        string $id,
        Instruction $instruction,
        Handler $handler,
        bool $cleanOnSuccess
    ): void {
        $queue = call_user_func($this->queueResolver, $this->queueConnection);

        if (!$queue instanceof Queue) {
            throw new MissingConfiguration('Queue resolver did not return a Queue implementation.');
        }

        $delay = $this->considerDelay($instruction);
        $queueName = $this->considerQueue($instruction);

        $job = new ProcessJob($id, $instruction, $handler, $cleanOnSuccess);
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
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param string                            $handlerClass
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    protected function run(Instruction $instruction, string $handlerClass, bool $clean): Process
    {
        $handler = $this->container->make($handlerClass);
        $process = $this->createProcess($instruction, $handlerClass);

        $process->start();
        $this->runSingleProcess($process, $instruction, $handler);
        $process->finish();

        if ($clean) {
            $this->processRepository->delete($process->id());
        }

        return $process;
    }

    /**
     * Run handler asynchronously.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param \Mrluke\Bus\Contracts\Handler     $handler
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function runAsync(
        Instruction $instruction,
        Handler $handler,
        bool $clean
    ): Process {
        $process = $this->createProcess($instruction, $handler);
        $this->pushInstructionToQueue($process->id(), $instruction, $handler, $clean);

        return $process;
    }

    /**
     * Run single process.
     *
     * @param \Mrluke\Bus\Contracts\Process     $process
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param \Mrluke\Bus\Contracts\Handler     $handler
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    protected function runSingleProcess(
        Process $process,
        Instruction $instruction,
        Handler $handler
    ): void {
        try {
            $result = $this->pipeline->send($instruction)
                ->through($this->pipes)
                ->then(
                    function($instruction) use ($handler) {
                        return $handler->handle($instruction);
                    }
                );

            $process->applyResult(
                get_class($handler),
                Process::Succeed,
                $this->processResult($result)
            );

        } catch (Exception $e) {
            $process->applyResult(get_class($handler), Process::Failed, $e->getMessage());
            $this->logger->error($e);
        }
    }
}
