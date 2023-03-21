<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManagerLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manager_log', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('timestamp')->default(0);
            $table->integer('internalKey')->default(0);
            $table->string('username')->nullable();
            $table->integer('action')->default(0);
            $table->string('itemid', 10)->default('0');
            $table->string('itemname')->nullable();
            $table->string('message')->default('');
            $table->string('ip', 45)->nullable();
            $table->string('useragent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manager_log');
    }
}
