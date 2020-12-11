<?php

namespace Mrluke\Bus\Extensions;

use ReflectionClass;

use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Intention;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ShouldBeAsync;
use Mrluke\Bus\Exceptions\InvalidHandler;

/**
 * Trait FiresMultipleHandlers
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Extensions
 * @property array $handlers
 */
trait FiresMultipleHandlers
{
    /**
     * @param \Mrluke\Bus\Contracts\Intention $intention
     * @return mixed
     * @throws \Mrluke\Bus\Exceptions\InvalidHandler
     * @throws \ReflectionException
     */
    public function handler(Intention $intention)
    {
        $handlers = $this->handlers[get_class($intention)];

        if (!is_array($handlers)) {
            throw new InvalidHandler(
                sprintf(
                    'Invalid handler for [%s]. Array of Handlers required.',
                    get_class($intention)
                )
            );
        }

        foreach ($handlers as $h) {
            $reflection = new ReflectionClass($h);

            if (
                !$reflection->isInstantiable() ||
                !in_array(Handler::class, $reflection->getInterfaces())
            ) {
                throw new InvalidHandler('Handler must be an instance of %s', Handler::class);
            }
        }

        return $handlers;
    }

    /**
     * Run handler synchronously.
     *
     * @param \Mrluke\Bus\Contracts\Intention $intention
     * @param array                           $handlers
     * @return \Mrluke\Bus\Contracts\Process
     */
    protected function run(Intention $intention, array $handlers): Process
    {
        //TODO: Implement run method
    }

    /**
     * Run handler asynchronously.
     *
     * @param \Mrluke\Bus\Contracts\ShouldBeAsync $intention
     * @param \Mrluke\Bus\Contracts\Handler       $handler
     * @return \Mrluke\Bus\Contracts\Process
     */
    protected function runAsync(ShouldBeAsync $intention, Handler $handler): Process
    {
        //TODO: Implement runAsync method
    }
}
