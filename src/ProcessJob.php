<?php

namespace Mrluke\Bus;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Queue\InteractsWithQueue;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Extensions\TranslateResults;

class ProcessJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, TranslateResults;

    /** Determine if process should be delete on success.
     *
     * @var bool
     */
    protected $cleanOnSuccess;

    /**
     * @var \Mrluke\Bus\Contracts\Instruction
     */
    protected $instruction;

    /**
     * Handler class used to process the instruction.
     *
     * @var \Mrluke\Bus\Contracts\Handler
     */
    protected $handler;

    /**
     * The process id.
     *
     * @var string
     */
    protected $processId;

    /**
     * @param string                            $processId
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param \Mrluke\Bus\Contracts\Handler     $handler
     * @param bool                              $cleanOnSuccess
     * @retuen void
     */
    public function __construct(
        string $processId,
        Instruction $instruction,
        Handler $handler,
        bool $cleanOnSuccess,
    ) {
        $this->cleanOnSuccess = $cleanOnSuccess;
        $this->instruction = $instruction;
        $this->handler = $handler;
        $this->processId = $processId;
    }

    /**
     * Process handler in async way.
     *
     * @param \Mrluke\Bus\Contracts\ProcessRepository $repository
     * @param \Illuminate\Log\Logger                  $logger
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     */
    public function handle(ProcessRepository $repository, Logger $logger)
    {
        try {
            $result = $this->processResult(
                $this->handler->handle($this->instruction)
            );

            $process = $repository->applySubResult(
                $this->processId,
                get_class($this->handler),
                ProcessContract::Succeed,
                $result
            );

            $logger->debug(
                'Async handler succeed.',
                [
                    'process' => $this->processId,
                    'handler' => get_class($this->handler),
                    'result'  => $result
                ]
            );

        } catch (Exception $e) {
            $process = $repository->applySubResult(
                $this->processId,
                get_class($this->handler),
                ProcessContract::Failed,
                $e->getMessage()
            );

            $logger->error($e);
        }

        if ($process->qualifyAsFinished()) {
            $repository->finish($this->processId);

            $logger->debug('Process finished.', ['process' => $this->processId]);
        }
    }
}
