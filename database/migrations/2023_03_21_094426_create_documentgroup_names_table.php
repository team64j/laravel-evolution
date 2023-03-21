<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentgroupNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentgroup_names', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 245)->default('')->unique('name');
            $table->boolean('private_memgroup')->default(0)->comment("determine whether the document group is private to manager users");
            $table->boolean('private_webgroup')->default(0)->comment("determines whether the document is private to web users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentgroup_names');
    }
}
