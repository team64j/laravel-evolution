<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemEventnamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_eventnames', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 50)->default('')->unique('name');
            $table->boolean('service')->default(0)->comment("System Service number");
            $table->string('groupname', 20)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_eventnames');
    }
}
