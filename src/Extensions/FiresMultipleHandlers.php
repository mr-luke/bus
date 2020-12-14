<?php

namespace Mrluke\Bus\Extensions;

use ReflectionClass;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Exceptions\InvalidHandler;

/**
 * Trait FiresMultipleHandlers
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Extensions
 *
 * @property \Illuminate\Contracts\Container\Container $container
 * @property array                                     $handlers
 * @property \Illuminate\Log\Logger                    $logger
 * @property array                                     $pipes
 * @property \Illuminate\Pipeline\Pipeline             $pipeline
 * @property \Mrluke\Bus\Contracts\ProcessRepository   $processRepository
 *
 * @method Process createProcess(Instruction $instruction, array $handlers)
 * @method Process pushInstructionToQueue($id, Instruction $instruction, $handler, $cleanOnSuccess)
 * @method void runSingleProcess(Process $process, Instruction $instruction, Handler $handler)
 */
trait FiresMultipleHandlers
{
    /**
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return mixed
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \ReflectionException
     */
    public function handler(Instruction $instruction)
    {
        $handlers = $this->handlers[get_class($instruction)];

        if (!is_array($handlers)) {
            throw new InvalidHandler(
                sprintf(
                    'Invalid handler for [%s]. Array of Handlers required.',
                    get_class($instruction)
                )
            );
        }

        foreach ($handlers as $h) {
            $reflection = new ReflectionClass($h);

            if (
                !$reflection->isInstantiable() ||
                !in_array(Handler::class, $reflection->getInterfaces())
            ) {
                throw new InvalidHandler('Handler must be an instance of %s', Handler::class);
            }
        }

        return $handlers;
    }

    /**
     * Run handler synchronously.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param array                             $handlerClasses
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    protected function run(Instruction $instruction, array $handlerClasses, bool $clean): Process
    {
        $process = $this->createProcess($instruction, $handlerClasses);

        $process->start();
        foreach ($handlerClasses as $class) {
            $this->runSingleProcess($process, $instruction, $this->container->make($class));
        }
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
     * @param array                             $handlerClasses
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     */
    protected function runAsync(
        Instruction $instruction,
        array $handlerClasses,
        bool $clean
    ): Process {
        $process = $this->createProcess($instruction, $handlerClasses);

        foreach ($handlerClasses as $class) {
            $this->pushInstructionToQueue($process->id(), $instruction, $class, $clean);
        }

        return $process;
    }
}
