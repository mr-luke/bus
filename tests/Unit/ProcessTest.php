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
            Process::verifyStatus(ProcessContract::New),
            ProcessContract::New
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

        Process::verifySubStatus(ProcessContract::Finished);
    }

    public function testIfReturnsValidSubStatus()
    {
        $this->assertEquals(
            Process::verifySubStatus(ProcessContract::Succeed),
            ProcessContract::Succeed
        );
    }

    public function testIfProcessMeetsContract()
    {
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::New,
            1,
            ['Handler'],
            null,
            null,
            null,
            null,
            $this->buildCarbonMock()
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
            ProcessContract::New,
            1,
            ['Handler'],
            null,
            null,
            123,
            null,
            $carbon
        );

        $this->assertEquals(
            [
                'id'          => 'id',
                'bus'         => 'bus',
                'process'     => 'Process',
                'status'      => ProcessContract::New,
                'handlers'    => 1,
                'results'     => ['Handler'],
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
            ProcessContract::New,
            1,
            [$handlerName => ['status' => ProcessContract::New]],
            null,
            null,
            null,
            null,
            $this->buildCarbonMock()
        );

        $this->assertEquals(
            [$handlerName => ['status' => ProcessContract::Succeed]],
            $process->applyResult($handlerName, ProcessContract::Succeed)
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
            ProcessContract::New,
            1,
            [$handlerName => ['status' => ProcessContract::New]],
            null,
            null,
            null,
            null,
            $this->buildCarbonMock()
        );

        $process->applyResult('BadHandler', ProcessContract::Succeed);
    }

    public function testIfIsSuccessfulMethodReturnsFalseWhenProcessIsNotFinished()
    {
        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::New,
            1,
            ['Handler' => ['status' => ProcessContract::New]],
            null,
            null,
            null,
            null,
            $this->buildCarbonMock()
        );

        $this->assertFalse($process->isSuccessful());
    }

    public function testIfIsSuccessfulMethodReturnsFalseWhenFinishedWithFails()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Failed, 'message' => 'Failed'],
            'Handler2' => ['status' => ProcessContract::Succeed]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Finished,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon,
            $carbon,
            $carbon
        );

        $this->assertFalse($process->isSuccessful());
    }

    public function testIfIsSuccessfulMethodReturnsTrueWhenFinishedWithoutFails()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Succeed],
            'Handler2' => ['status' => ProcessContract::Succeed]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Finished,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon,
            $carbon,
            $carbon
        );

        $this->assertTrue($process->isSuccessful());
    }

    public function testIfQualifyAsFinishedReturnsFalseWhenThereIsPendingProcess()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Succeed],
            'Handler2' => ['status' => ProcessContract::Pending]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Pending,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $this->assertFalse($process->qualifyAsFinished());
    }

    public function testIfQualifyAsFinishedReturnsTrueWhenAllHandlersAreResolved()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Succeed],
            'Handler2' => ['status' => ProcessContract::Failed],
            'Handler3' => ['status' => ProcessContract::Succeed]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Pending,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $this->assertTrue($process->qualifyAsFinished());
    }

    public function testIfQualifyToStartReturnsTrueWhenProcessHasBeenCanceled()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::New],
            'Handler2' => ['status' => ProcessContract::New],
            'Handler3' => ['status' => ProcessContract::New]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Canceled,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $this->assertTrue($process->qualifyToStart());
    }

    public function testIfQualifyToStartReturnsFalseWhenProcessIsAlreadyStarted()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Pending],
            'Handler2' => ['status' => ProcessContract::New],
            'Handler3' => ['status' => ProcessContract::New]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Pending,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $this->assertFalse($process->qualifyToStart());
    }

    public function testIfQualifyToStartReturnsTrueWhenProcessIsNew()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::New],
            'Handler2' => ['status' => ProcessContract::New],
            'Handler3' => ['status' => ProcessContract::New]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::New,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $this->assertTrue($process->qualifyToStart());
    }

    public function testIfResultOfThrowsWhenHandlerIsUnknown()
    {
        $this->expectException(MissingHandler::class);

        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler' => ['status' => ProcessContract::New]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::New,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
        );

        $process->resultOf('Handler2');
    }

    public function testIfResultOfReturnsResultsForHandler()
    {
        $carbon = $this->buildCarbonMock();

        $handlerResult = ['status' => ProcessContract::New];
        $results = [
            'Handler' => $handlerResult
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::New,
            count($results),
            $results,
            null,
            null,
            null,
            null,
            $carbon
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
