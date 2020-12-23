<?php

namespace Tests\Components;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\ProcessRepository;

/**
 * Class HelloHandler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class DependencyErrorHandler implements Handler
{
    /**
     * @param mixed
     * @return void
     */
    public function __construct($repository) {
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
