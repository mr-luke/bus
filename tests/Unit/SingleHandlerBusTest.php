<?php

namespace Tests\Unit;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Logger;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Contracts\Command;
use Mrluke\Bus\Contracts\Handler;
use Mrluke\Bus\Contracts\Instruction;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\MissingConfiguration;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\SingleHandlerBus;

class SingleHandlerBusTest extends TestCase
{
    public function testIfHandlerThrowsWhenInstructionIsNotRegistered()
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

        $bus->handler(
            $this->createMock(Command::class)
        );
    }

    public function testIfDispatchThrowsWhenInstructionIsNotTrigger()
    {
        $this->expectException(MissingConfiguration::class);

        /* @var SingleHandlerBus $bus */
        $bus = $this->getMockForAbstractClass(
            SingleHandlerBus::class,
            [
                $this->createMock(ProcessRepository::class),
                $this->createMock(Container::class),
                $this->createMock(Logger::class)
            ]
        );

        $bus->map([Instruction::class, Handler::class]);

        $bus->dispatch(
            $this->createMock(Instruction::class)
        );
    }
}
