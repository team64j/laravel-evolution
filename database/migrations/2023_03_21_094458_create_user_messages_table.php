<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_messages', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('type', 15)->default('');
            $table->string('subject', 60)->default('');
            $table->text('message')->nullable();
            $table->integer('sender')->default(0);
            $table->integer('recipient')->default(0);
            $table->boolean('private')->default(0);
            $table->integer('postdate')->default(0);
            $table->boolean('messageread')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_messages');
    }
}
