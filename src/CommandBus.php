<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\Command;
use Mrluke\Bus\Contracts\CommandBus as CommandBusContract;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Extensions\UsesDefaultQueue;

/**
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class CommandBus extends SingleHandlerBus implements CommandBusContract, HasAsyncProcesses
{
    use UsesDefaultQueue;

    public bool $cleanWhenFinished = true;

    public bool $persistSyncInstructions = false;

    public bool $stopOnException = true;

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function publish(Command $command): ?Process
    {
        return $this->dispatch($command);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected function getBusName(): string
    {
        return 'command-bus';
    }
}
