<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteModuleDepobjTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_module_depobj', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('module')->default(0);
            $table->integer('resource')->default(0);
            $table->integer('type')->default(0)->comment("10-chunks, 20-docs, 30-plugins, 40-snips, 50-tpls, 60-tvs");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_module_depobj');
    }
}
