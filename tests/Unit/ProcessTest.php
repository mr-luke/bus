<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\Process;

class ProcessTest extends TestCase
{
    public function testIfReturnsValidStatus()
    {
        $this->assertEquals(
            Process::verifyStatus(ProcessContract::NEW),
            ProcessContract::NEW
        );
    }

    public function testIfThrowsWhenStatusIsInvalid()
    {
        $this->expectException(InvalidAction::class);

        Process::verifyStatus('Bad status');
    }

    public function testIfSubStatusThrowsOnFinish()
    {
        $this->expectException(InvalidAction::class);

        Process::verifySubStatus(ProcessContract::FINISHED);
    }

    public function testIfReturnsValidSubStatus()
    {
        $this->assertEquals(
            Process::verifySubStatus(ProcessContract::SUCCEED),
            ProcessContract::SUCCEED
        );
    }

    public function testIfProcessMeetsContract()
    {
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            ['Handler'],
            1,
            null,
            $this->buildCarbonMock(),
        );

        $this->assertInstanceOf(
            ProcessContract::class,
            $process
        );
    }

    public function testIfCastToArrayCorrectly()
    {
        $carbon = $this->buildCarbonMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $carbon */
        $carbon->expects($this->once())->method('getPreciseTimestamp')->willReturn(1234567890);

        /* @var CarbonImmutable $carbon */
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            ['Handler'],
            123,
            null,
            $carbon,
        );

        $this->assertEquals(
            [
                'id'          => 'id',
                'bus'         => 'bus',
                'process'     => 'Process',
                'status'      => ProcessContract::NEW,
                'handlers'    => ['Handler'],
                'results'     => [],
                'related'     => null,
                'data'        => null,
                'pid'         => 123,
                'committedBy' => null,
                'committedAt' => 1234567890,
                'startedAt'   => null,
                'finishedAt'  => null
            ],
            $process->toArray()
        );
    }

    public function testIfApplyResultSetsStatus()
    {
        $handlerName = 'Handler';
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            [$handlerName],
            1,
            null,
            $this->buildCarbonMock()
        );

        $process->applyResult($handlerName, ProcessContract::SUCCEED);

        $this->assertEquals(
            ['status' => ProcessContract::SUCCEED],
            $process->results()
        );
    }

    public function testIfThrowsWhenApplyToUnknownHandler()
    {
        $this->expectException(MissingHandler::class);

        $handlerName = 'Handler';
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            [$handlerName],
            1,
            null,
            $this->buildCarbonMock()
        );

        $process->applyResult('BadHandler', ProcessContract::SUCCEED);
    }

    public function testIfIsSuccessfulMethodReturnsFalseWhenProcessIsNotFinished()
    {
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            ['Handler'],
            1,
            null,
            $this->buildCarbonMock()
        );

        $this->assertFalse($process->isSuccessful());
    }

    public function testIfIsSuccessfulMethodReturnsFalseWhenFinishedWithFails()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2'];
        $results = [
            ['status' => ProcessContract::FAILED, 'message' => 'Failed'],
            ['status' => ProcessContract::SUCCEED]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::FINISHED,
            $handlers,
            1,
            null,
            $carbon,
            $carbon,
            $carbon,
            $results
        );

        $this->assertFalse($process->isSuccessful());
    }

    public function testIfIsSuccessfulMethodReturnsTrueWhenFinishedWithoutFails()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2'];
        $results = [
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::SUCCEED]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::FINISHED,
            $handlers,
            1,
            null,
            $carbon,
            $carbon,
            $carbon,
            $results
        );

        $this->assertTrue($process->isSuccessful());
    }

    public function testIfQualifyAsFinishedReturnsFalseWhenThereIsPendingProcess()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2'];
        $results = [
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::PENDING]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::PENDING,
            $handlers,
            1,
            null,
            $carbon,
            null,
            null,
            $results
        );

        $this->assertFalse($process->qualifyAsFinished());
    }

    public function testIfQualifyAsFinishedReturnsTrueWhenAllHandlersAreResolved()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::FAILED],
            ['status' => ProcessContract::SUCCEED]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::PENDING,
            $handlers,
            1,
            null,
            $carbon,
            null,
            null,
            $results
        );

        $this->assertTrue($process->qualifyAsFinished());
    }

    public function testIfQualifyToStartReturnsTrueWhenProcessHasBeenCanceled()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::CANCELED,
            $handlers,
            1,
            null,
            $carbon,
            null,
            null,
            $results
        );

        $this->assertTrue($process->qualifyToStart());
    }

    public function testIfQualifyToStartReturnsFalseWhenProcessIsAlreadyStarted()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::PENDING],
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::PENDING,
            $handlers,
            1,
            null,
            $carbon,
            null,
            null,
            $results
        );

        $this->assertFalse($process->qualifyToStart());
    }

    public function testIfQualifyToStartReturnsTrueWhenProcessIsNew()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            $handlers,
            1,
            null,
            $carbon,
            null,
            null,
            $results
        );

        $this->assertTrue($process->qualifyToStart());
    }

    public function testIfResultOfThrowsWhenHandlerIsUnknown()
    {
        $this->expectException(MissingHandler::class);

        $carbon = $this->buildCarbonMock();
        $results = [
            ['status' => ProcessContract::NEW]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            ['Handler'],
            null,
            null,
            $carbon,
            null,
            null,
            $results,
        );

        $process->resultOf('Handler2');
    }

    public function testIfResultOfReturnsResultsForHandler()
    {
        $carbon = $this->buildCarbonMock();

        $handlerResult = ['status' => ProcessContract::NEW];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::NEW,
            ['Handler'],
            1,
            null,
            $carbon,
            null,
            null,
            [$handlerResult]
        );

        $this->assertEquals(
            $handlerResult,
            $process->resultOf('Handler')
        );
    }

    /**
     * Return Carbon mock.
     *
     * @return \Carbon\CarbonImmutable
     */
    protected function buildCarbonMock(): CarbonImmutable
    {
        return $this->getMockBuilder(CarbonImmutable::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
