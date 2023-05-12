<?php

namespace Tests\Components;

use Carbon\Carbon;
use Mrluke\Bus\Contracts\Command;
use Mrluke\Bus\Contracts\ShouldBeAsync;

/**
 * Class HelloCommand
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class AsyncHelloCommand implements Command, ShouldBeAsync
{
    /**
     * @var \Carbon\Carbon|int
     */
    public Carbon|int $delay;

    /**
     * @var string
     */
    public string $greeting;

    /**
     * @var string
     */
    public string $queue = 'custom';

    public function __construct(string $greeting)
    {
        $this->greeting = $greeting;

        $this->delay = Carbon::now()->addMinutes(20);
    }
}
