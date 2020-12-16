<?php

namespace Tests\Feature;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Logger;
use Illuminate\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\AbstractBus;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingHandler;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;
use Tests\Components\HelloNotHandler;

class AbstractBusTest extends TestCase
{
    public function testIfHandlerMethodThrowsWhenMultipleHandlersSet()
    {
        $this->expectException(InvalidHandler::class);

        /* @var AbstractBus $bus */
        $bus = $this->getMockForAbstractClass(
            AbstractBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => []]);

        $bus->handler(
            new HelloCommand('Hello world')
        );
    }

    public function testIfHandlerThrowsWhenInstructionIsNotRegistered()
    {
        $this->expectException(MissingHandler::class);

        /* @var AbstractBus $bus */
        $bus = $this->getMockForAbstractClass(
            AbstractBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->handler(
            new HelloCommand('Hello world')
        );
    }

    public function testIfHandlerMethodThrowsWhenHandlerMismatchInterface()
    {
        $this->expectException(InvalidHandler::class);

        /* @var AbstractBus $bus */
        $bus = $this->getMockForAbstractClass(
            AbstractBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
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
        /* @var AbstractBus $bus */
        $bus = $this->getMockForAbstractClass(
            AbstractBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => HelloHandler::class]);

        $this->assertEquals(
            HelloHandler::class,
            $bus->handler(new HelloCommand('Hello world'))
        );
    }

    public function testIfDispatchThrowsWhenHandlerIsMissing()
    {
        $this->expectException(MissingHandler::class);

        /* @var AbstractBus $bus */
        $bus = $this->getMockForAbstractClass(
            AbstractBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->dispatch(
            new HelloCommand('Hello world')
        );
    }
}
