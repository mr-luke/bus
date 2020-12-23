<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * Interface Bus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Bus
{
    /**
     * Dispatch an instruction due to it's requirements.
     *
     * @param \Mrluke\Bus\Contracts\Instruction $instruction
     * @param bool                              $cleanOnSuccess
     * @return \Mrluke\Bus\Contracts\Process|void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function dispatch(Instruction $instruction, bool $cleanOnSuccess = false): Process;

    /**
     * Check if an instruction has it's handler registered.
     *
     * @param \Mrluke\Bus\Contracts\Trigger $trigger
     * @return bool
     */
    public function hasHandler(Trigger $trigger): bool;

    /**
     * Return handler of given instruction.
     *
     * @param \Mrluke\Bus\Contracts\Trigger $trigger
     * @return \Mrluke\Bus\Contracts\Handler[]
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \ReflectionException
     */
    public function handler(Trigger $trigger): array;

    /**
     * Map an instruction to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map): self;

    /**
     * Set the pipes through which commands should be piped before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes): self;
}
