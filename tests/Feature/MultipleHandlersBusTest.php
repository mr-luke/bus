<?php

namespace Tests\Feature;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Logger;
use Illuminate\Pipeline\Pipeline;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\MultipleHandlerBus;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingHandler;
use Tests\Components\ErrorHandler;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;
use Tests\Components\HelloNotHandler;

class MultipleHandlersBusTest extends TestCase
{
    public function testIfThrowsWhenInstructionIsNotRegistered()
    {
        $this->expectException(MissingHandler::class);

        /* @var \Mrluke\Bus\MultipleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            MultipleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->handler(new HelloCommand('Hello world'));
    }

    public function testIfWrapsWhenMapHasNoArray()
    {
        /* @var \Mrluke\Bus\MultipleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            MultipleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => HelloHandler::class]);

        $this->assertEquals(
            [HelloHandler::class],
            $bus->handler(new HelloCommand('Hello world'))
        );
    }

    public function testIfThrowsWhenOneOfHandlersDoesntMeetContract()
    {
        $this->expectException(InvalidHandler::class);

        /* @var \Mrluke\Bus\MultipleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            MultipleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Pipeline::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([HelloCommand::class => [HelloHandler::class, HelloNotHandler::class]]);
        $bus->handler(new HelloCommand('Hello world'));
    }
}
