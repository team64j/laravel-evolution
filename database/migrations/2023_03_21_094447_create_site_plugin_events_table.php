<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitePluginEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_plugin_events', function (Blueprint $table) {
            $table->integer('pluginid')->default(0);
            $table->integer('evtid')->default(0);
            $table->integer('priority')->default(0)->comment("determines plugin run order");
            
            $table->primary(['pluginid', 'evtid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_plugin_events');
    }
}
