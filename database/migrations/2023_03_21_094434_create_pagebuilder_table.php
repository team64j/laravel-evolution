<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagebuilderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagebuilder', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id');
            $table->string('container')->nullable();
            $table->string('title')->nullable();
            $table->string('config');
            $table->mediumText('values');
            $table->unsignedTinyInteger('visible')->default(1);
            $table->unsignedSmallInteger('index');
            
            $table->index(['document_id', 'container'], 'document_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagebuilder');
    }
}
