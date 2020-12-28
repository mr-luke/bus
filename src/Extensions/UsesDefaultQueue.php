<?php

namespace Mrluke\Bus\Extensions;

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
    /**
     * Async processes Queue name.
     *
     * @var string|null
     */
    public ?string $queueName = null;

    /**
     * Return queue name.
     *
     * @return string|null
     * @codeCoverageIgnore
     */
    public function onQueue(): ?string
    {
        return $this->queueName;
    }
}
