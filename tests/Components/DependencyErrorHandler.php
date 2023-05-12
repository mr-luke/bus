<?php

namespace Tests\Components;

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
class DependencyErrorHandler implements Handler
{
    /**
     * @param mixed $repository
     * @return void
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function handle(Instruction $instruction)
    {
        return $instruction->greeting;
    }
}
