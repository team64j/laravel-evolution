<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteTmplvarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_tmplvars', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('type', 50)->default('');
            $table->string('name', 50)->default('');
            $table->string('caption', 80)->default('');
            $table->string('description')->default('');
            $table->integer('editor_type')->default(0)->comment("0-plain text,1-rich text,2-code editor");
            $table->integer('category')->default(0)->comment("category id");
            $table->boolean('locked')->default(0);
            $table->text('elements')->nullable();
            $table->integer('rank')->default(0)->index('indx_rank');
            $table->string('display', 32)->default('')->comment("Display Control");
            $table->text('display_params')->nullable()->comment("Display Control Properties");
            $table->text('default_text')->nullable();
            $table->integer('editedon')->default(0);
            $table->integer('createdon')->default(0);
            $table->text('properties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_tmplvars');
    }
}
