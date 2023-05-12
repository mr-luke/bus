<?php

namespace Tests\Components;

use Exception;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;

/**
 * Class HelloHandler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class ErrorHandler implements Handler
{
    /**
     * @inheritDoc
     */
    public function handle(Instruction $instruction)
    {
        throw new Exception($instruction->greeting);
    }
}
