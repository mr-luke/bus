<?php

namespace Mrluke\Bus\Extensions;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;

/**
 * Trait ResolveDependencies
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Extensions
 */
trait ResolveDependencies
{
    /**
     * Resolved instances of handler.
     *
     * @var array
     */
    private $resolved = [];

    /**
     * Resolve class based on constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string                                    $className
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function resolveClass(Container $container ,string $className)
    {
        if (!isset($this->resolved[$className])) {
            $reflection  = new ReflectionClass($className);

            $dependencies = [];
            if ($constructor = $reflection->getConstructor()) {
                foreach ($constructor->getParameters() as $p) {
                    $dependencies[] = $container->make($p->getClass()->getName());
                }
            }

            $this->resolved[$className] = empty($dependencies) ?
                new $className : $reflection->newInstanceArgs($dependencies);
        }

        return $this->resolved[$className];
    }
}
