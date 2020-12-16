<?php

namespace Tests\Components;

use Mrluke\Bus\AbstractBus;

/**
 * Class SyncBus
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class SyncBus extends AbstractBus
{
    /**
     * @inheritDoc
     */
    protected function getBusName(): string
    {
        return 'sync-bus';
    }
}
