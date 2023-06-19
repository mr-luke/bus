<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\DatabaseProcessRepository;
use Mrluke\Bus\Exceptions\MissingProcess;
use Mrluke\Bus\Exceptions\RuntimeException;
use Mrluke\Configuration\Contracts\ArrayHost;
use PHPUnit\Framework\TestCase;

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

        $repository->retrieve($id);
    }

    public function testIfFindReturnsCorrectInstanceOfProcess()
    {
        $builder = $this->buildBuilderMockWithFind(self::ExistingId);

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $process = $repository->retrieve(self::ExistingId);

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(self::ExistingId, $process->id());
    }

    public function testIfPersistStoresNewProcess()
    {
        $process = new \Mrluke\Bus\Process(
            'test-id',
            'bus',
            'TestProcess',
            Process::NEW,
            ['TestHandler'],
            123,
            null,
            now()->toImmutable(),
        );

        $payload = $process->toDatabase();

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('insert')
            ->with($payload)
            ->willReturn(1);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->persist($process);
    }

    public function testIfThrowsWhenInsertFail()
    {
        $this->expectException(RuntimeException::class);

        $process = new \Mrluke\Bus\Process(
            'test-id',
            'bus',
            'TestProcess',
            Process::NEW,
            ['TestHandler'],
            123,
            null,
            now()->toImmutable(),
        );

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('insert')
            ->withAnyParameters()
            ->willReturn(0);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->persist($process);
    }

    public function testIfPersistUpdatesOldProcess()
    {
        $process = new \Mrluke\Bus\Process(
            'test-id',
            'bus',
            'TestProcess',
            Process::NEW,
            ['TestHandler'],
            123,
            null,
            now()->toImmutable(),
            null,
            null,
            [],
            null,
            null,
            true,
        );

        $data = $process->toDatabase();
        $payload = [
            'data' => $data['data'],
            'results' => $data['results'],
            'related' => $data['related'],
            'status' => $data['status'],
            'started_at' => $data['started_at'],
            'finished_at' => $data['finished_at'],
        ];

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('where')
            ->with('id', $data['id'])
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->with($payload)
            ->willReturn(1);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->persist($process);
    }

    public function testIfThrowsWhenUpdatesFail()
    {
        $this->expectException(RuntimeException::class);

        $process = new \Mrluke\Bus\Process(
            'test-id',
            'bus',
            'TestProcess',
            Process::NEW,
            ['TestHandler'],
            123,
            null,
            now()->toImmutable(),
            null,
            null,
            [],
            null,
            null,
            true,
        );

        $data = $process->toDatabase();

        $builder = $this->buildBuilderMock();
        /* @var \PHPUnit\Framework\MockObject\MockObject $builder */
        $builder->expects($this->once())
            ->method('where')
            ->with('id', $data['id'])
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('update')
            ->withAnyParameters()
            ->willReturn(0);

        /* @var \Illuminate\Database\Query\Builder $builder */
        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(1),
            $this->buildConnectionMock($builder, 1),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $repository->persist($process);
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
        string $status = Process::PENDING
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
