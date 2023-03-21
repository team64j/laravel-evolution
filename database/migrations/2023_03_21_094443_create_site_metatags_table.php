<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteMetatagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_metatags', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 50)->default('');
            $table->string('tag', 50)->default('')->comment("tag name");
            $table->string('tagvalue')->default('');
            $table->boolean('http_equiv')->default(0)->comment("1 - use http_equiv tag style, 0 - use name");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_metatags');
    }
}
