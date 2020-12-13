<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

use Carbon\Carbon;

/**
 * This interface makes Instruction an async one.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface HasAsyncProcesses
{
    /**
     * Return async delay.
     * @return \Carbon\Carbon|null
     */
    public function delay(): ?Carbon;

    /**
     * Return queue name.
     *
     * @return string|null
     */
    public function onQueue(): ?string;
}
