<?php

namespace Mrluke\Bus\Extensions;

use Carbon\Carbon;

/**
 * Adds default queue for processing.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Extensions
 */
trait UsesDefaultQueue
{
    protected $delay = null;

    protected $queue = null;

    /**
     * Return async delay.
     * @return \Carbon\Carbon|null
     * @codeCoverageIgnore
     */
    public function delay(): ?Carbon
    {
        return $this->delay;
    }

    /**
     * Return queue name.
     *
     * @return string|null
     * @codeCoverageIgnore
     */
    public function onQueue(): ?string
    {
        return $this->queue;
    }
}
