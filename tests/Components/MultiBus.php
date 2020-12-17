<?php

namespace Tests\Components;

use Mrluke\Bus\AbstractBus;
use Mrluke\Bus\Contracts\HasAsyncProcesses;
use Mrluke\Bus\Extensions\FiresMultipleHandlers;
use Mrluke\Bus\Extensions\UsesDefaultQueue;

/**
 * Class SyncBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class MultiBus extends AbstractBus implements HasAsyncProcesses
{
    use FiresMultipleHandlers, UsesDefaultQueue;

    /**
     * @inheritDoc
     */
    protected function getBusName(): string
    {
        return 'multi-bus';
    }
}
