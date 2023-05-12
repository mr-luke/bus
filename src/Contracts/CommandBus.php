<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * Interface CommandBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface CommandBus extends Bus
{
    /**
     * Dispatch an instruction due to it's requirements.
     *
     * @param \Mrluke\Bus\Contracts\Command $command
     * @return \Mrluke\Bus\Contracts\Process|null
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \Mrluke\Bus\Exceptions\MissingConfiguration
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function publish(Command $command): ?Process;
}
