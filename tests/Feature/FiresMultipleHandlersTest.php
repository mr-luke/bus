<?php

namespace Tests\Feature;

use Mrluke\Bus\Contracts\Process;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Exceptions\InvalidHandler;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\Extensions\FiresMultipleHandlers;
use Tests\Components\ErrorHandler;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;
use Tests\Components\HelloNotHandler;

class FiresMultipleHandlersTest extends TestCase
{
    public function testIfThrowsWhenInstructionIsNotRegistered()
    {
        $this->expectException(MissingHandler::class);

        $trait = $this->getMockBuilder(FiresMultipleHandlers::class)
            ->setMethods(['hasHandler'])
            ->getMockForTrait();
        $trait->expects($this->once())
            ->method('hasHandler')
            ->willReturn(false);

        /* @var FiresMultipleHandlers $trait */
        $trait->handler(new HelloCommand('Hello world'));
    }

    public function testIfThrowsWhenMapHasNoArray()
    {
        $this->expectException(InvalidHandler::class);

        $trait = $this->getMockBuilder(FiresMultipleHandlers::class)
            ->setMethods(['hasHandler'])
            ->getMockForTrait();
        $trait->expects($this->once())
            ->method('hasHandler')
            ->willReturn(true);

        $trait->handlers = [HelloCommand::class => HelloHandler::class];

        /* @var FiresMultipleHandlers $trait */
        $trait->handler(new HelloCommand('Hello world'));
    }

    public function testIfThrowsWhenOneOfHandlersDoesntMeetContract()
    {
        $this->expectException(InvalidHandler::class);

        $trait = $this->getMockBuilder(FiresMultipleHandlers::class)
            ->setMethods(['hasHandler'])
            ->getMockForTrait();
        $trait->expects($this->once())
            ->method('hasHandler')
            ->willReturn(true);

        $trait->handlers = [HelloCommand::class => [HelloHandler::class, HelloNotHandler::class]];

        /* @var FiresMultipleHandlers $trait */
        $trait->handler(new HelloCommand('Hello world'));
    }

    public function testIfReturnsArrayOfHandlers()
    {
        $trait = $this->getMockBuilder(FiresMultipleHandlers::class)
            ->setMethods(['hasHandler'])
            ->getMockForTrait();
        $trait->expects($this->once())
            ->method('hasHandler')
            ->willReturn(true);

        $trait->handlers = [HelloCommand::class => [HelloHandler::class, ErrorHandler::class]];

        /* @var FiresMultipleHandlers $trait */
        $handlers = $trait->handler(new HelloCommand('Hello world'));

        $this->assertEquals(
            [HelloHandler::class, ErrorHandler::class],
            $handlers
        );
    }
}
