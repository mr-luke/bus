<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Mrluke\Bus\HandlerResult;
use Mrluke\Configuration\Contracts\ArrayHost;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\DatabaseProcessRepository;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingProcess;

class DatabaseProcessRepositoryTest extends TestCase
{
    const ExistingId = 'existing';

    const Table = 'processes';

    public function testIfFindThrowsOnMissingProcess()
    {
        $this->expectException(MissingProcess::class);

        $id = 'non-existing';

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->find($id);
    }

    public function testIfFindReturnsCorrectInstanceOfProcess()
    {
        $builder = $this->buildBuilderMockWithFind(self::ExistingId);

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->find(self::ExistingId);

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(self::ExistingId, $process->id());
    }

    public function testIfApplySubResultSetsHandlerStatusCorrectly()
    {
        $feedback = 'Test message';

        $builder = $this->buildBuilderMockWithFind(self::ExistingId);
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('where')
            ->with('id', self::ExistingId)
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(2),
            $this->buildConnectionMock($builder, 2),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->applySubResult(
            self::ExistingId,
            ProcessTest::HandlerName,
            Process::Succeed,
            new HandlerResult($feedback)
        );

        $this->assertEquals(
            [ProcessTest::HandlerName => ['status' => Process::Succeed, 'feedback' => $feedback]],
            $process->toArray()['results']
        );
    }

    public function testIfCancelThrowsOnPendingProcess()
    {
        $this->expectException(InvalidAction::class);

        $builder = $this->buildBuilderMockWithFind(self::ExistingId);
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->cancel(self::ExistingId);
    }

    public function testIfCancelSetsProperStatusToProcess()
    {
        $builder = $this->buildBuilderMockWithFind(self::ExistingId, Process::New);
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('where')
            ->with('id', self::ExistingId)
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(2),
            $this->buildConnectionMock($builder, 2),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->cancel(self::ExistingId);

        $this->assertEquals(
            Process::Canceled,
            $process->status()
        );
    }

    public function testIfCreateThrowsWhenNoHandlersProvided()
    {
        $this->expectException(InvalidAction::class);

        $builder = $this->buildBuilderMock();
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(0),
            $this->buildConnectionMock($builder, 0),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->create('bus', 'Process', []);
    }

    public function testIfCreateThrowsWhenCannotInsertToDB()
    {
        $this->expectException(Exception::class);

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('insert')
            ->withAnyParameters()
            ->willReturn(false);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->create('bus', 'Process', [ProcessTest::HandlerName]);
    }

    public function testIfCreateReturnsProcessInstance()
    {
        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('insert')
            ->withAnyParameters()
            ->willReturn(true);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->create('bus', 'Process', [ProcessTest::HandlerName]);

        $this->assertInstanceOf(
            Process::class,
            $process
        );
    }

    public function testIfFinishThrowsWhenAlreadyFinished()
    {
        $this->expectException(InvalidAction::class);

        $builder = $this->buildBuilderMockWithFind(self::ExistingId, Process::Finished);
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->finish(self::ExistingId);
    }

    public function testIfFinishSetsCorrectStatusOnProcess()
    {
        $process = ProcessTest::createCorrectModel(self::ExistingId);
        $process->results = '{"' . ProcessTest::HandlerName . '":{"status":"' . Process::Succeed . '"}}';

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('find')
            ->with(self::ExistingId)
            ->willReturn($process);

        $builder->expects($this->once())
            ->method('where')
            ->with('id', self::ExistingId)
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(2),
            $this->buildConnectionMock($builder, 2),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->finish(self::ExistingId);

        $this->assertEquals(
            Process::Finished,
            $process->status()
        );
    }

    public function testIfStartThrowsWhenAlreadyPending()
    {
        $this->expectException(InvalidAction::class);

        $builder = $this->buildBuilderMockWithFind(self::ExistingId);
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->start(self::ExistingId);
    }

    public function testIfStartSetsProperStatusOnProcess()
    {
        $builder = $this->buildBuilderMockWithFind(self::ExistingId, Process::New);
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('where')
            ->with('id', self::ExistingId)
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(2),
            $this->buildConnectionMock($builder, 2),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->start(self::ExistingId);

        $this->assertEquals(
            Process::Pending,
            $process->status()
        );
    }

    /**
     * Prepare Builder mock.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildBuilderMock(): Builder
    {
        return $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Prepare Builder mock with definition of find.
     *
     * @param string $id
     * @param string $status
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildBuilderMockWithFind(
        string $id,
        string $status = Process::Pending
    ): Builder {
        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(ProcessTest::createCorrectModel($id, $status));

        return $builder;
    }

    /**
     * Prepare Connection mock.
     *
     * @param \Illuminate\Database\Query\Builder $builder
     * @param int                                $count
     * @return \Illuminate\Database\Connection
     */
    protected function buildConnectionMock(Builder $builder, int $count = 1): Connection
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            //->setMethods(['table'])
            ->getMock();

        $connection->expects($this->exactly($count))
            ->method('table')
            ->with($this->equalTo(self::Table))
            ->willReturn($builder);

        return $connection;
    }

    /**
     * Prepare Host mock.
     *
     * @param int $count
     * @return \Mrluke\Configuration\Contracts\ArrayHost
     */
    protected function buildHostMock(int $count = 1): ArrayHost
    {
        $host = $this->getMockBuilder(ArrayHost::class)->setMethods(['get', 'has', '__get'])
            ->getMock();
        $host->expects($this->exactly($count))
            ->method('get')
            ->with($this->equalTo('table'))
            ->willReturn(self::Table);

        return $host;
    }
}
