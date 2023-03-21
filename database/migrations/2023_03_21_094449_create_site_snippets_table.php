<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteSnippetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_snippets', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 50)->default('');
            $table->string('description')->default('Snippet');
            $table->integer('editor_type')->default(0)->comment("0-plain text,1-rich text,2-code editor");
            $table->integer('category')->default(0)->comment("category id");
            $table->boolean('cache_type')->default(0)->comment("Cache option");
            $table->mediumText('snippet')->nullable();
            $table->boolean('locked')->default(0);
            $table->text('properties')->nullable()->comment("Default Properties");
            $table->string('moduleguid', 32)->default('')->comment("GUID of module from which to import shared parameters");
            $table->integer('createdon')->default(0);
            $table->integer('editedon')->default(0);
            $table->boolean('disabled')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_snippets');
    }
}
