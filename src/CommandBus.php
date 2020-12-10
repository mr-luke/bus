<?php

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\CommandBus as CommandBusContract;

/**
 * Command Bus.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
final class CommandBus extends AbstractBus implements CommandBusContract
{
    /**
     * @inheritDoc
     */
    protected function getBusName(): string
    {
        return 'command-bus';
    }
}
