<?php

namespace Tests\Components;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;

/**
 * Class HelloHandler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class HelloHandler implements Handler
{
    /**
     * @inheritDoc
     */
    public function handle(Instruction $instruction)
    {
        return $instruction->greeting;
    }
}
