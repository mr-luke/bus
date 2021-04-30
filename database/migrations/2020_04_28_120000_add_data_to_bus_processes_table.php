<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mrluke\Bus\Contracts\Config;

/**
 * Class CreateBusProcessesTable
 *
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 */
class AddDataToBusProcessesTable extends Migration
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
        Schema::table($this->config->get('table'), function (Blueprint $table) {
            $table->json('data')->nullable()->after('results');
            $table->json('related')->nullable()->after('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->config->get('table'), function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropColumn('related');
        });
    }
}
