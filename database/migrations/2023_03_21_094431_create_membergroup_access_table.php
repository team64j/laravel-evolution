<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembergroupAccessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('membergroup_access', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('membergroup')->default(0);
            $table->integer('documentgroup')->default(0);
            $table->integer('context')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('membergroup_access');
    }
}
