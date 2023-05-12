<?php

namespace Mrluke\Bus\Contracts;

/**
 * Interface Handler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
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
     * @throws \Exception
     */
    public function handle(Instruction $instruction);
}
