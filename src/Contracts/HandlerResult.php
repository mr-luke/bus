<?php

namespace Mrluke\Bus\Contracts;

/**
 * Interface HandlerReslt
 *
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface HandlerResult
{
    /**
     * An array of related processes.
     *
     * @return array
     */
    public function getRelated(): array;

    /**
     * Complex object or data returned by handler.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Simpler string message returned by handler.
     *
     * @return string
     */
    public function getFeedback(): string;
}
