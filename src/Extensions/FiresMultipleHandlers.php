<?php

namespace Mrluke\Bus\Extensions;

use Mrluke\Bus\Exceptions\MissingHandler;
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
 * @method bool hasHandler(Instruction $instruction)
 * @method Process pushInstructionToQueue($id, Instruction $instruction, $handler, $cleanOnSuccess)
 * @method mixed resolveClass($container, string $className)
 * @method void runSingleProcess(Process $process, Instruction $instruction, Handler $handler)
 */
trait FiresMultipleHandlers
{
    /**
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @return mixed
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \ReflectionException
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function handler(Instruction $instruction)
    {
        if (!$this->hasHandler($instruction)) {
            throw new MissingHandler(
                sprintf('Given instruction [%s] is not registered.', get_class($instruction))
            );
        }

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
                !$reflection->implementsInterface(Handler::class)
            ) {
                throw new InvalidHandler(
                    sprintf('Handler must be an instance of %s', Handler::class)
                );
            }
        }

        return $handlers;
    }

    /**
     * Run handler synchronously.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param array                             $handlerClass
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \ReflectionException
     */
    protected function run(Instruction $instruction, $handlerClass, bool $clean): Process
    {
        $process = $this->createProcess($instruction, $handlerClass);

        $process->start();
        foreach ($handlerClass as $class) {
            $this->runSingleProcess(
                $process,
                $instruction,
                $this->resolveClass($this->container, $class)
            );
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
     * @param array                             $handlerClass
     * @param bool                              $clean
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     */
    protected function runAsync(
        Instruction $instruction,
        $handlerClass,
        bool $clean
    ): Process {
        $process = $this->createProcess($instruction, $handlerClass);

        foreach ($handlerClass as $class) {
            $this->pushInstructionToQueue(
                $process->id(),
                $instruction,
                $class,
                $clean
            );
        }

        return $process;
    }
}
