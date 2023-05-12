<?php

namespace Mrluke\Bus\Extensions;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Mrluke\Bus\Exceptions\RuntimeException;

/**
 * Trait ResolveDependencies
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
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
    private array $resolved = [];

    /**
     * Resolve class based on constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string                                    $className
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     * @throws \ReflectionException
     */
    protected function resolveClass(Container $container, string $className)
    {
        if (!isset($this->resolved[$className])) {
            $reflection = new ReflectionClass($className);

            $dependencies = [];
            if ($constructor = $reflection->getConstructor()) {
                foreach ($constructor->getParameters() as $p) {
                    if (!$p->getType()) {
                        throw new RuntimeException(
                            sprintf(
                                'Cannot resolve handler [%s]. Missing type annotation of parameter [%s]',
                                $className,
                                $p->getName()
                            )
                        );
                    }
                    $dependencies[] = $container->make($p->getType()->getName());
                }
            }

            $this->resolved[$className] = empty($dependencies)
                ? new $className : $reflection->newInstanceArgs($dependencies);
        }

        return $this->resolved[$className];
    }
}
