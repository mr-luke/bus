<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Queue\InteractsWithQueue;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Extensions\ResolveDependencies;
use Mrluke\Bus\Extensions\TranslateResults;
use Throwable;

class AsyncHandlerJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, ResolveDependencies, TranslateResults;

    /**
     * Timout for processes (in seconds)
     *
     * @var int
     */
    public int $timeout;

    /** Determine if process should be deleted on success.
     *
     * @var bool
     */
    protected bool $cleanOnSuccess;

    /**
     * @var \Mrluke\Bus\Contracts\Instruction
     */
    protected Instruction $instruction;

    /**
     * Handler class used to process the instruction.
     *
     * @var string
     */
    protected string $handlerClass;

    /**
     * The process id.
     *
     * @var string
     */
    protected string $processId;

    /**
     * @param string                            $processId
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param string                            $handlerClass
     * @param bool                              $cleanOnSuccess
     * @retuen void
     */
    public function __construct(
        string      $processId,
        Instruction $instruction,
        string      $handlerClass,
        bool        $cleanOnSuccess
    ) {
        $this->cleanOnSuccess = $cleanOnSuccess;
        $this->instruction = $instruction;
        $this->handlerClass = $handlerClass;
        $this->processId = $processId;
    }

    /**
     * Process handler in async way.
     *
     * @param \Mrluke\Bus\Contracts\ProcessRepository   $repository
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Log\Logger                    $logger
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     * @throws \ReflectionException
     */
    public function handle(
        ProcessRepository $repository,
        Container         $container,
        Logger            $logger
    ): void {
        $handler = $this->resolveClass($container, $this->handlerClass);
        $process = $repository->retrieve($this->processId);

        try {
            if (!$process->isPending()) {
                $process->start();
                $repository->persist($process);
            }

            $result = $this->processResult(
                $handler->handle($this->instruction)
            );

            $process->applyHandlerResult(
                $this->handlerClass,
                ProcessContract::SUCCEED,
                $result
            );

            $logger->debug(
                'Async handler succeed.',
                [
                    'process' => $this->processId,
                    'handler' => $this->handlerClass,
                    'result' => $result
                ]
            );

        } catch (Exception $e) {
            $logger->error($e);

            $process->applyHandlerResult(
                $this->handlerClass,
                ProcessContract::FAILED,
                new HandlerResult($e->getMessage())
            );
        }

        if ($process->qualifyAsFinished()) {
            $process->finish();

            $logger->debug('Process finished.', ['process' => $this->processId]);
        }

        if (
            $process->isFinished() && $process->isSuccessful() && $this->cleanOnSuccess
        ) {
            $repository->delete($process->id());
        } else {
            $repository->persist($process);
        }
    }

    /**
     * @param \Throwable $e
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     */
    public function failed(Throwable $e): void
    {
        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = app()->make(ProcessRepository::class);

        $process = $repository->retrieve($this->processId);

        $process->applyHandlerResult(
            $this->handlerClass,
            ProcessContract::FAILED,
            new HandlerResult(
                __('bus::messages.process-disrupted', ['message' => $e->getMessage()])
            )
        );

        if ($process->qualifyAsFinished()) {
            $process->finish();
        }

        $repository->persist($process);
    }

    /**
     * @param $timeout
     */
    public function timeout($timeout): void
    {
        $this->timeout = $timeout;
    }
}
