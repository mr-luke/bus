<?php

namespace Mrluke\Bus\Contracts;

/**
 * Interface Handler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Handler
{
    /**
     * Handle instruction business logic.
     *
     * @param  \Mrluke\Bus\Contracts\Instruction $instruction
     * @return mixed
     */
    public function handle(Instruction $instruction);
}
