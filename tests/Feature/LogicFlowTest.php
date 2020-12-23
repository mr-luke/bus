<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

use Mrluke\Bus\AsyncHandlerJob;
use Mrluke\Bus\Contracts\CommandBus;
use Mrluke\Bus\Contracts\Config;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\MissingConfiguration;
use Tests\AppCase;
use Tests\Components\AsyncHelloCommand;
use Tests\Components\DependencyErrorHandler;
use Tests\Components\ErrorHandler;
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
        $process = $repository->create('bus', HelloCommand::class, [HelloHandler::class]);

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
                'id'           => $id,
                'bus'          => 'command-bus',
                'process'      => HelloCommand::class,
                'status'       => Process::Pending,
                'results'      => json_encode(
                    [HelloHandler::class => ['status' => Process::Pending]]
                ),
                'committed_at' => CarbonImmutable::now()->getPreciseTimestamp(3)
            ]
        );

        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = $this->app->make(ProcessRepository::class);
        $process = $repository->find($id);

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
                'id'           => $id,
                'bus'          => 'command-bus',
                'process'      => HelloCommand::class,
                'status'       => Process::New,
                'results'      => json_encode(
                    [HelloHandler::class => ['status' => Process::New]]
                ),
                'committed_at' => CarbonImmutable::now()->getPreciseTimestamp(3)
            ]
        );

        /* @var \Mrluke\Bus\Contracts\ProcessRepository $repository */
        $repository = $this->app->make(ProcessRepository::class);
        $repository->find($id);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::New)
                ->exists()
        );

        $repository->start($id);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::Pending)
                ->whereNotNull('started_at')
                ->exists()
        );

        $process = $repository->applySubResult($id, HelloHandler::class, Process::Succeed);

        $this->assertEquals(
            [HelloHandler::class => ['status' => Process::Succeed]],
            $process->toArray()['results']
        );

        $repository->finish($id);

        $this->assertTrue(
            DB::table($config->get('table'))
                ->where('id', $id)->where('status', Process::Finished)
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

        $bus->cleanOnSuccess = false;
        $process = $bus->dispatch(new HelloCommand('Hello world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::Finished,
            $process->status()
        );

        $this->assertEquals(
            [HelloHandler::class => ['status' => Process::Succeed, 'feedback' => 'Hello world']],
            $process->toArray()['results']
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

        $bus->cleanOnSuccess = true;
        $process = $bus->dispatch(new HelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::Finished,
            $process->status()
        );

        $this->assertEquals(
            [HelloHandler::class => ['status' => Process::Succeed, 'feedback' => 'Hello new world']],
            $process->toArray()['results']
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

        $bus->cleanOnSuccess = true;
        $bus->dispatch(new HelloCommand('An exception'));
    }

    public function testAsyncCommandDispatchesJob()
    {
        $this->expectsJobs(AsyncHandlerJob::class);

        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([AsyncHelloCommand::class => HelloHandler::class]);

        $bus->cleanOnSuccess = true;
        $bus->dispatch(new AsyncHelloCommand('Hello new world'));
    }

    public function testAsyncCommandDoesntThrowOnFail()
    {
        /* @var CommandBus $bus */
        $bus = $this->app->make(CommandBus::class);
        $bus->map([AsyncHelloCommand::class => ErrorHandler::class]);

        $bus->cleanOnSuccess = true;
        $process = $bus->dispatch(new AsyncHelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::New,
            $process->status()
        );

        $this->assertEquals(
            [ErrorHandler::class => ['status' => Process::New]],
            $process->toArray()['results']
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
            Process::New,
            $process->status()
        );

        $this->assertEquals(
            [HelloHandler::class => ['status' => Process::New]],
            $process->toArray()['results']
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
            null
        );

        $bus->map([HelloCommand::class => [HelloHandler::class, ErrorHandler::class]]);

        $process = $bus->dispatch(new HelloCommand('Hello new world'));

        $this->assertInstanceOf(
            Process::class,
            $process
        );

        $this->assertEquals(
            Process::Finished,
            $process->status()
        );

        $this->assertEquals(
            [
                HelloHandler::class => ['status' => Process::Succeed, 'feedback' => 'Hello new world'],
                ErrorHandler::class => ['status' => Process::Failed, 'feedback' => 'Hello new world']
            ],
            $process->toArray()['results']
        );

        $this->assertTrue(
            !DB::table($config->get('table'))->where('id', $process->id())->exists()
        );
    }

    public function testAsyncCommandFiresMultipleHandlers()
    {
        $this->expectsJobs([AsyncHandlerJob::class, AsyncHandlerJob::class]);

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
    }
}
