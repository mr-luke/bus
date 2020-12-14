<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Mrluke\Bus\Contracts\Config;
use Mrluke\Bus\Contracts\Process;

/**
 * Class CreateBusProcessesTable
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 */
class CreateBusProcessesTable extends Migration
{
    /**
     * Instance of EventStore.
     *
     * @var Config
     */
    protected $config;

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct()
    {
        $this->config = app()->make(Config::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            $this->config->get('table'),
            function(Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('bus');
                $table->string('process');
                $table->enum(
                    'status',
                    [
                        Process::New,
                        Process::Pending,
                        Process::Finished,
                        Process::Canceled
                    ]
                )->default(Process::New);
                $table->unsignedInteger('handlers')->default(1);
                $table->json('results')->nullable();
                $table->{$this->config->get('users.primary.type')}('committed_by')->nullable();
                $table->timestamp('committed_at', 6);
                $table->timestamp('started_at', 6)->nullable();
                $table->timestamp('finished_at', 6)->nullable();

                $table->foreign('committed_by')
                    ->references($this->config->get('users.primary.name'))
                    ->on($this->config->get('users.table'))
                    ->onDelete('SET NULL');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            $this->config->get('table'),
            function(Blueprint $table) {
                $table->dropForeign(
                    $this->config->get('table') . '_committed_by'
                );
            }
        );

        Schema::dropIfExists($this->config->get('table'));
    }
}
