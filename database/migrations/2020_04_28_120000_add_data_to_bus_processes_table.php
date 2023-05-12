<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mrluke\Bus\Contracts\Config;
use Mrluke\Configuration\Host;

/**
 * Class CreateBusProcessesTable
 *
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 */
return new class extends Migration
{
    /**
     * Instance of EventStore.
     *
     * @var \Mrluke\Configuration\Host
     */
    protected Host $config;

    public function __construct()
    {
        $this->config = app()->make(Config::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        Schema::table($this->config->get('table'), function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropColumn('related');
        });
    }
};
