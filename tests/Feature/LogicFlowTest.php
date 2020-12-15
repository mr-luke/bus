<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Mrluke\Bus\Contracts\Config;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Tests\AppCase;
use Tests\Components\HelloCommand;
use Tests\Components\HelloHandler;

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
}
