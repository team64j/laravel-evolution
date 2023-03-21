<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_log', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('eventid')->default(0);
            $table->integer('createdon')->default(0)->index('createdon');
            $table->boolean('type')->default(1)->comment("1- information, 2 - warning, 3- error");
            $table->integer('user')->default(0)->index('user')->comment("link to user table");
            $table->boolean('usertype')->default(0)->comment("0 - manager, 1 - web");
            $table->string('source', 50)->default('');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_log');
    }
}
