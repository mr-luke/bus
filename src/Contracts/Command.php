<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * A Command that can be processed by CommandBus.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Command extends Intention {}
