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
     * Dispatch an intention due to it's requirements.
     *
     * @param \Mrluke\Bus\Contracts\Intention $intention
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function dispatch(Intention $intention): Process;

    /**
     * Check if an intention has it's handler registered.
     *
     * @param \Mrluke\Bus\Contracts\Intention $intention
     * @return bool
     */
    public function hasHandler(Intention $intention): bool;

    /**
     * Map an intention to a handler.
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
