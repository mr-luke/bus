<?php

namespace Tests\Components;

use Mrluke\Bus\SingleHandlerBus;

/**
 * Class SyncBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
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
