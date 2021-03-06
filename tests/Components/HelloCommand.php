<?php

namespace Tests\Components;

use Mrluke\Bus\Contracts\Command;

/**
 * Class HelloCommand
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @package Tests\Components
 * @codeCoverageIgnore
 */
class HelloCommand implements Command
{
    /**
     * @var string
     */
    public $greeting;

    public function __construct(string $greeting)
    {
        $this->greeting = $greeting;
    }
}
