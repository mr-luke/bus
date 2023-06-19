<?php

namespace Mrluke\Bus;

use ReflectionClass;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Trigger;
use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingHandler;

/**
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
abstract class MultipleHandlerBus extends SingleHandlerBus
{
    /**
     * @inheritDoc
     */
    public function handler(Trigger $trigger): array
    {
        if (!$this->hasHandler($trigger)) {
            throw new MissingHandler(
                sprintf('Given trigger [%s] is not registered.', get_class($trigger))
            );
        }

        $handlers = $this->handlers[get_class($trigger)];

        if (!is_array($handlers)) {
            $handlers = [$handlers];
        }

        foreach ($handlers as $h) {
            $reflection = new ReflectionClass($h);

            if (
                !$reflection->isInstantiable() ||
                !$reflection->implementsInterface(Handler::class)
            ) {
                throw new InvalidHandler(
                    sprintf('Handler must be an instance of %s', Handler::class)
                );
            }
        }

        return $handlers;
    }
}
