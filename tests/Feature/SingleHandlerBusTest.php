<?php

namespace Tests\Feature;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Logger;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\SingleHandlerBus;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingHandler;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;
use Tests\Components\HelloNotHandler;

class SingleHandlerBusTest extends TestCase
{
    public function testIfHandlerMethodThrowsWhenMultipleHandlersSet()
    {
        $this->expectException(InvalidHandler::class);

        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => []]);

        $bus->handler(
            new HelloCommand('Hello world')
        );
    }

    public function testIfHandlerMethodThrowsWhenHandlerMismatchInterface()
    {
        $this->expectException(InvalidHandler::class);

        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => HelloNotHandler::class]);

        $bus->handler(
            new HelloCommand('Hello world')
        );
    }

    public function testIfHandlerMethodReturnsInstantiableHandler()
    {
        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => HelloHandler::class]);

        $this->assertEquals(
            [HelloHandler::class],
            $bus->handler(new HelloCommand('Hello world'))
        );
    }

    public function testIfDispatchThrowsWhenHandlerIsMissing()
    {
        $this->expectException(MissingHandler::class);

        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->dispatch(
            new HelloCommand('Hello world')
        );
    }

    public function testIfDispatchReturnsNullWhenHandlerIsMissingAndThrowingDisabled()
    {
        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->throwWhenNoHandler = false;
        $this->assertNull(
            $bus->dispatch(
                new HelloCommand('Hello world')
            )
        );
    }
}
