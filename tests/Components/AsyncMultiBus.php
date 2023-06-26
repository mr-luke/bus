<?php

namespace Tests\Components;

use Mrluke\Bus\Contracts\AsyncBus;
use Mrluke\Bus\Extensions\UsesDefaultQueue;
use Mrluke\Bus\MultipleHandlerBus;

/**
 * Class SyncBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class AsyncMultiBus extends MultipleHandlerBus implements AsyncBus
{
    use UsesDefaultQueue;

    public bool $persistSyncInstructions = false;

    /**
     * @inheritDoc
     */
    protected function getBusName(): string
    {
        return 'async-multi-bus';
    }
}
