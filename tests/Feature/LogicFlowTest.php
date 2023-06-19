<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mrluke\Bus\AsyncHandlerJob;
use Mrluke\Bus\Contracts\CommandBus;
use Mrluke\Bus\Contracts\Config;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\MissingConfiguration;
use Mrluke\Bus\Exceptions\RuntimeException;
use Mrluke\Bus\HandlerResult;
use Tests\AppCase;
use Tests\Components\AsyncHelloCommand;
use Tests\Components\DependencyErrorHandler;
use Tests\Components\ErrorHandler;
use Tests\Components\ForceSyncHelloHandler;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;
use Tests\Components\MultiBus;
use Tests\Components\SyncBus;

class LogicFlowTest extends AppCase
{
    public function testIfProcessCanBeCreatedViaRepository()
    {
        $config = $this->app->make(Config::class);
        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = $this->app->make(ProcessRepository::class);

        $process = \Mrluke\Bus\Process::create('bus', HelloCommand::class, [HelloHandler::class]);
        $repository->persist($process);

        $this->assertTrue(
            DB::table($config->get('table'))->where('id', $process->id())->exists()
        );
    }

    public function testIfProcessCanBeFoundViaRepository()
    {
        $config = $this->app->make(Config::class);

        $id = Str::uuid()->toString();
        DB::table($config->get('table'))->insert(
            [
                'id' => $id,
                'bus' => 'command-bus',
                'process' => HelloCommand::class,
                'status' => Process::PENDING,
                'handlers' => json_encode([HelloHandler::class]),
                'results' => json_encode([['status' => Process::PENDING]]),
                'committed_at' => CarbonImmutable::now()->getPreciseTimestamp(3)
            ]
        );

        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = $this->app->make(ProcessRepository::class);
        $process = $repository->retrieve($id);

        $this->assertInstanceOf(
            Process::class,
            $process
        );
    }

    public function testFullProcessViaRepository()
    {
        $config = $this->app->make(Config::class);

        $id = Str::uuid()->toString();
        DB::table($config->get('table'))->insert(
            [
                'id' => $id,
                'bus' => 'command-bus',
                'process' => HelloCommand::class,
                'status' => Process::NEW,
                'handlers' => json_encode([HelloHandler::class]),
                'results' => json_encode([['status' => Process::NEW]]),
                'committed_at' => CarbonImmutable::now()->getPreciseTimestamp(3)
            ]
        );

        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = $this->app->make(ProcessRepository::class);
        $process = $repository->retrieve($id);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::NEW)
                ->exists()
        );

        $process->start();
        $repository->persist($process);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::PENDING)
                ->whereNotNull('started_at')
                ->exists()
        );

        $process->applyHandlerResult(
            HelloHandler::class,
            Process::SUCCEED,
            new HandlerResult()
        );
        $repository->persist($process);

        $this->assertEquals(
            [['status' => Process::SUCCEED]],
            $process->toArray()['results']
        );

        $process->finish();
        $repository->persist($process);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::FINISHED)
                ->whereNotNull('finished_at')
                ->exists()
        );
    }

    public function testIfThrowWhenHandlerDoesntHaveTypeAnnotation()
    {
        $this->expectException(RuntimeException::class);

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([HelloCommand::class => DependencyErrorHandler::class]);

        $bus->dispatch(new HelloCommand('Hello world'));
    }

    public function testSyncCommandDispatchingWithoutClearing()
    {
        $config = $this->app->make(Config::class);

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([HelloCommand::class => HelloHandler::class]);

        $bus->persistSyncInstructions = true;
        $process = $bus->dispatch(new HelloCommand('Hello world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::FINISHED,
            $process->status()
        );

        $this->assertEquals(
            ['status' => Process::SUCCEED, 'feedback' => 'Hello world'],
            $process->results()
        );

        $this->assertTrue(
            DB::table($config->get('table'))->where('id', $process->id())->exists()
        );
    }

    public function testSyncCommandDispatchingWithClearing()
    {
        $config = $this->app->make(Config::class);

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([HelloCommand::class => HelloHandler::class]);

        $process = $bus->dispatch(new HelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::FINISHED,
            $process->status()
        );

        $this->assertEquals(
            ['status' => Process::SUCCEED, 'feedback' => 'Hello new world'],
            $process->results()
        );

        $this->assertTrue(
            !DB::table($config->get('table'))->where('id', $process->id())->exists()
        );
    }

    public function testSyncCommandDispatchingWithFail()
    {
        $this->expectException(Exception::class);

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([HelloCommand::class => ErrorHandler::class]);

        $bus->cleanWhenFinished = true;
        $bus->dispatch(new HelloCommand('An exception'));
    }

    public function testAsyncCommandDispatchesJob()
    {
        Queue::fake();

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([AsyncHelloCommand::class => HelloHandler::class]);

        $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        Queue::assertPushed(AsyncHandlerJob::class);
    }

    public function testAsyncCommandDoesntThrowOnFail()
    {
        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([AsyncHelloCommand::class => ErrorHandler::class]);

        $process = $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::NEW,
            $process->status()
        );

        $this->assertEquals(
            ['status' => Process::NEW],
            $process->results()
        );
    }

    public function testAsyncCommandDispatching()
    {
        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([AsyncHelloCommand::class => HelloHandler::class]);

        $process = $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::NEW,
            $process->status()
        );

        $this->assertEquals(
            ['status' => Process::NEW],
            $process->results()
        );
    }

    public function testIfSyncBusThrowsWhenGotAsyncCommand()
    {
        $this->expectException(MissingConfiguration::class);

        /* @var \Mrluke\Bus\Contracts\Bus $bus */
        $bus = $this->app->make(SyncBus::class);
        $bus->map([AsyncHelloCommand::class => HelloHandler::class]);

        $bus->dispatch(new AsyncHelloCommand('Hello new world'));
    }

    public function testSyncCommandFiresMultipleHandlers()
    {
        $config = $this->app->make(Config::class);

        /* @var CommandBus $bus */
        $bus = new MultiBus(
            $this->app->make(ProcessRepository::class),
            $this->app->make(Container::class),
            $this->app->make(Logger::class),
            function () {}
        );

        $bus->map([HelloCommand::class => [HelloHandler::class, ErrorHandler::class]]);

        $process = $bus->dispatch(new HelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::FINISHED,
            $process->status()
        );

        $this->assertEquals(
            [
                HelloHandler::class => ['status' => Process::SUCCEED, 'feedback' => 'Hello new world'],
                ErrorHandler::class => ['status' => Process::FAILED, 'feedback' => 'Hello new world']
            ],
            $process->results()
        );

        $this->assertTrue(
            DB::table($config->get('table'))->where('id', $process->id())->exists()
        );
    }

    public function testAsyncCommandFiresMultipleHandlers()
    {
        Queue::fake();

        $container = $this->app->make(Container::class);

        /* @var CommandBus $bus */
        $bus = new MultiBus(
            $this->app->make(ProcessRepository::class),
            $container,
            $this->app->make(Logger::class),
            function($connection = null) use ($container) {
                return $container->make(Factory::class)->connection($connection);
            }
        );

        $bus->map([AsyncHelloCommand::class => [HelloHandler::class, ErrorHandler::class]]);

        $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        Queue::assertPushed(AsyncHandlerJob::class, 2);
    }

    public function testAsyncCommandWithForcedHandlerFiresLessJobs()
    {
        Queue::fake();

        $container = $this->app->make(Container::class);

        /* @var CommandBus $bus */
        $bus = new MultiBus(
            $this->app->make(ProcessRepository::class),
            $container,
            $this->app->make(Logger::class),
            function($connection = null) use ($container) {
                return $container->make(Factory::class)->connection($connection);
            }
        );

        $bus->map([AsyncHelloCommand::class => [HelloHandler::class, ForceSyncHelloHandler::class]]);

        $process = $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        $this->assertTrue(
            $process->isPending()
        );

        Queue::assertPushed(AsyncHandlerJob::class);
    }
}
