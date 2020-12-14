<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingHandler;
use Mrluke\Bus\Process;

class ProcessTest extends TestCase
{
    public function testIfCancelSetsCorrectStatusWithTimestamp()
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
            $carbon
        );

        $finishedAt = $process->cancel();

        $this->assertIsInt($finishedAt);
        $this->assertEquals(ProcessContract::Canceled, $process->status());
        $this->assertTrue(
            Carbon::createFromTimestampMs($finishedAt)->isSameMinute()
        );
    }

//    public function testCreate()
//    {
//
//    }
//
    public function testIfThrowsWhenTryingToFinishPendingProcess()
    {
        $this->expectException(InvalidAction::class);

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
            $carbon
        );

        $process->finish();
    }

    public function testIfFinishSetsCorrectStatusWhenProcessIsQualified()
    {
        $carbon = $this->buildCarbonMock();
        $results = [
            'Handler'  => ['status' => ProcessContract::Succeed],
            'Handler2' => ['status' => ProcessContract::Succeed],
            'Handler3' => ['status' => ProcessContract::Failed]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::Pending,
            count($results),
            $results,
            null,
            $carbon
        );

        $finishedAt = $process->finish();

        $this->assertIsInt($finishedAt);
        $this->assertEquals(ProcessContract::Finished, $process->status());
        $this->assertTrue(
            Carbon::createFromTimestampMs($finishedAt)->isSameMinute()
        );
    }

//    public function testStart()
//    {
//
//    }
//
//    public function testFromDatabase()
//    {
//
//    }

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
