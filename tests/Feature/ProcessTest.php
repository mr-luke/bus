<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Process;

class ProcessTest extends TestCase
{
    const HandlerName = 'Handler';

    public function testIfCancelSetsCorrectStatusWithTimestamp()
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
            $results,
        );

        $process->cancel();

        $finishedAt = $process->toArray()['finishedAt'];

        $this->assertIsFloat($finishedAt);
        $this->assertEquals(ProcessContract::CANCELED, $process->status());
        $this->assertTrue(
            Carbon::createFromTimestampMs($finishedAt)->isSameMinute()
        );
    }

    public function testIfCreateThrowsWhenNoHandlerProvided()
    {
        $this->expectException(InvalidAction::class);

        Process::create('bus', 'Process', [], 1);
    }

    public function testIfCreateComposeProperProcess()
    {
        $process = Process::create('bus', 'Process', ['Handler'], null);

        $this->assertInstanceOf(
            ProcessContract::class,
            $process
        );

        $this->assertMatchesRegularExpression(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/',
            $process->id()
        );

        $actualData = $process->toArray();
        unset($actualData['id']);
        unset($actualData['pid']);
        unset($actualData['committedAt']);
        $this->assertEquals(
            [
                'bus'         => 'bus',
                'process'     => 'Process',
                'status'      => ProcessContract::NEW,
                'handlers'    => ['Handler'],
                'results'     => [
                    ['status' => ProcessContract::NEW]
                ],
                'related'     => null,
                'data'        => null,
                'committedBy' => null,
                'startedAt'   => null,
                'finishedAt'  => null
            ],
            $actualData
        );
    }

    public function testIfThrowsWhenTryingToFinishPendingProcess()
    {
        $this->expectException(InvalidAction::class);

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
            $results,
        );

        $process->finish();
    }

    public function testIfFinishSetsCorrectStatusWhenProcessIsQualified()
    {
        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::FAILED]
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
            $results,
        );

        $process->finish();

        $finishedAt = $process->toArray()['finishedAt'];

        $this->assertIsFloat($finishedAt);
        $this->assertEquals(ProcessContract::FINISHED, $process->status());
        $this->assertTrue(
            Carbon::createFromTimestampMs($finishedAt)->isSameMinute()
        );
    }

    public function testIfFromDatabaseThrowsWhenTimestampHasNotEnoughPrecision()
    {
        $this->expectException(InvalidArgumentException::class);

        $model = new stdClass();
        $model->id = 'id';
        $model->bus = 'bus';
        $model->process = 'Process';
        $model->status = ProcessContract::NEW;
        $model->handlers = '["handler"]';
        $model->results = '[{"status":"' . ProcessContract::NEW . '"}]';
        $model->data = null;
        $model->related = null;
        $model->pid = 123;
        $model->committed_by = 1;
        $model->committed_at = 1607857526;
        $model->started_at = null;
        $model->finished_at = null;

        Process::fromDatabase($model);
    }

    public function testIfFromDatabaseReturnsProcessCorrectlyTranslatedFromStdclass()
    {
        $process = Process::fromDatabase(
            self::createCorrectModel('id')
        );

        $this->assertInstanceOf(
            ProcessContract::class,
            $process
        );
        $this->assertEquals(
            [
                'id'          => 'id',
                'bus'         => 'bus',
                'process'     => 'Process',
                'status'      => ProcessContract::PENDING,
                'handlers'    => [self::HandlerName],
                'results'     => [
                     ['status' => ProcessContract::PENDING]
                ],
                'related'     => null,
                'data'        => null,
                'pid'         => 12345,
                'committedBy' => 1,
                'committedAt' => 1607857526000,
                'startedAt'   => 1607857566000,
                'finishedAt'  => null
            ],
            $process->toArray()
        );
    }

    public function testIfStartThrowsWhenProcessCannotBeStarted()
    {
        $this->expectException(InvalidAction::class);

        $carbon = $this->buildCarbonMock();

        $handlers = ['Handler', 'Handler2', 'Handler3'];
        $results = [
            ['status' => ProcessContract::SUCCEED],
            ['status' => ProcessContract::NEW],
            ['status' => ProcessContract::NEW]
        ];

        $process = new Process(
            'id',
            'bus',
            'Process',
            ProcessContract::PENDING,
            $handlers,
            null,
            null,
            $carbon,
            null,
            null,
            $results,
        );

        $process->start();
    }

    public function testIfStartSetsCorrectStatusWhenProcessQualifyToStart()
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
            null,
            null,
            $carbon,
            null,
            null,
            $results,
        );

        $process->start();

        $startedAt = $process->toArray()['startedAt'];

        $this->assertIsFloat($startedAt);
        $this->assertEquals(ProcessContract::PENDING, $process->status());
        $this->assertTrue(
            Carbon::createFromTimestampMs($startedAt)->isSameMinute()
        );
    }

    /**
     * Create stdClass model of correct data.
     *
     * @param string $id
     * @param string $status
     * @return \stdClass
     */
    public static function createCorrectModel(
        string $id,
        string $status = ProcessContract::PENDING
    ): stdClass {
        $model = new stdClass();
        $model->id = $id;
        $model->bus = 'bus';
        $model->process = 'Process';
        $model->status = $status;
        $model->handlers = '["'. self::HandlerName .'"]';
        $model->results = '[{"status":"' . $status . '"}]';
        $model->related = null;
        $model->data = null;
        $model->pid = 12345;
        $model->committed_by = 1;
        $model->committed_at = 1607857526000;
        $model->started_at = 1607857566000;
        $model->finished_at = null;

        return $model;
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
