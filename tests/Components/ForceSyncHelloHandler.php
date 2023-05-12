<?php

namespace Tests\Components;

use Mrluke\Bus\Contracts\ForceSync;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\ProcessRepository;

/**
 * Class HelloHandler
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class ForceSyncHelloHandler implements ForceSync, Handler
{
    /**
     * @param \Mrluke\Bus\Contracts\ProcessRepository $repository
     * @return void
     */
    public function __construct(ProcessRepository $repository) {
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
