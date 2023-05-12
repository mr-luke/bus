<?php

namespace Tests\Unit;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Configuration\Contracts\ArrayHost;
use PHPUnit\Framework\TestCase;

use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\DatabaseProcessRepository;

class DatabaseProcessRepositoryTest extends TestCase
{
    const Table = 'processes';

    public function testIfRepositoryMeetsContract()
    {
        $repository = new DatabaseProcessRepository(
            $this->getMockBuilder(ArrayHost::class)->getMock(),
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $this->assertTrue(
            $repository instanceof ProcessRepository
        );
    }

    /**
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function testIfCountWithoutFilterReturnsAnInteger()
    {
        $expectedValue = 10;

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['where', 'count', 'delete'])
            ->getMock();

        $builder->expects($this->once())
            ->method('count')
            ->willReturn($expectedValue);

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $this->assertEquals(
            $repository->count(),
            $expectedValue
        );
    }

    /**
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function testIfCountWithFilterReturnsAnInteger()
    {
        $expectedValue = 10;

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['where', 'count', 'delete'])
            ->getMock();

        $builder->expects($this->once())
            ->method('where')
            ->with('status', Process::PENDING)
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('count')
            ->willReturn($expectedValue);

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );

        $this->assertEquals(
            $repository->count(Process::PENDING),
            $expectedValue
        );
    }

    public function testIfCountThrowsExceptionWhenFilterIsWrong()
    {
        $badStatus = 'BAD';
        $this->expectException(InvalidAction::class);

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['where', 'count', 'delete'])
            ->getMock();

        $builder->expects($this->never())
            ->method('where')
            ->with('status', $badStatus);

        $builder->expects($this->never())
            ->method('count');

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );
        $repository->count($badStatus);
    }

    public function testIfDeleteForwardActionToBuilder()
    {
        $id = 'a12-b13-c14';
        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['where', 'count', 'delete'])
            ->getMock();

        $builder->expects($this->once())
            ->method('delete')
            ->with($id);

        $repository = new DatabaseProcessRepository(
            $this->buildHostMock(),
            $this->buildConnectionMock($builder),
            $this->getMockBuilder(Guard::class)->getMock()
        );
        $repository->delete($id);
    }

    /**
     * Prepare Connection mock.
     *
     * @param \Illuminate\Database\Query\Builder $builder
     * @return \Illuminate\Database\Connection
     */
    protected function buildConnectionMock(Builder $builder): Connection
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['table'])
            ->getMock();

        $connection->expects($this->once())
            ->method('table')
            ->with($this->equalTo(self::Table))
            ->willReturn($builder);

        return $connection;
    }

    /**
     * Prepare Host mock.
     *
     * @return \Mrluke\Configuration\Contracts\ArrayHost
     */
    protected function buildHostMock(): ArrayHost
    {
        $host = $this->getMockBuilder(ArrayHost::class)->setMethods(['get', 'has', '__get'])
            ->getMock();
        $host->expects($this->once())
            ->method('get')
            ->with($this->equalTo('table'))
            ->willReturn(self::Table);

        return $host;
    }
}
