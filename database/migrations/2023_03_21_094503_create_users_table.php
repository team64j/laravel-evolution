<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('username', 100)->default('')->unique('username');
            $table->string('password', 100)->default('');
            $table->string('cachepwd', 100)->default('')->comment("Store new unconfirmed password");
            $table->string('refresh_token')->nullable();
            $table->string('access_token')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->string('verified_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
