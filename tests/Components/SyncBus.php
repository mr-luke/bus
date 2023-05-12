<?php

namespace Tests\Components;

use Mrluke\Bus\SingleHandlerBus;

/**
 * Class SyncBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class SyncBus extends SingleHandlerBus
{
    /**
     * @inheritDoc
     */
    protected function getBusName(): string
    {
        return 'sync-bus';
    }
}
