<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\CommandBus as CommandBusContract;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Extensions\UsesDefaultQueue;

/**
 * Command Bus.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class CommandBus extends AbstractBus implements CommandBusContract, HasAsyncProcesses
{
    use UsesDefaultQueue;

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected function getBusName(): string
    {
        return 'command-bus';
    }
}
