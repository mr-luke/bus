<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * Interface Bus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Bus
{
    /**
     * Dispatch an instruction due to it's requirements.
     *
     * @param \Mrluke\Bus\Contracts\Instruction  $instruction
     * @param \Mrluke\Bus\Contracts\Trigger|null $trigger
     * @return \Mrluke\Bus\Contracts\Process|null
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     * @throws \ReflectionException
     */
    public function dispatch(Instruction $instruction, Trigger $trigger = null): ?Process;

    /**
     * Dispatch an instruction due to it's requirements.
     *
     * @param \Mrluke\Bus\Contracts\Trigger       $trigger
     * @param \Mrluke\Bus\Contracts\Instruction[] $instructions
     * @return array
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     * @throws \ReflectionException
     */
    public function dispatchMultiple(
        Trigger $trigger,
        array $instructions
    ): array;

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
     * @param array $map
     * @return $this
     */
    public function map(array $map): self;
}
