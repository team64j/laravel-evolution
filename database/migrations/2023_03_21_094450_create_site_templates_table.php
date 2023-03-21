<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_templates', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('templatename', 50)->default('');
            $table->string('templatealias')->nullable();
            $table->string('description')->default('Template');
            $table->integer('editor_type')->default(0)->comment("0-plain text,1-rich text,2-code editor");
            $table->integer('category')->default(0)->comment("category id");
            $table->string('icon')->default('')->comment("url to icon file");
            $table->integer('template_type')->default(0)->comment("0-page,1-content");
            $table->mediumText('content')->nullable();
            $table->boolean('locked')->default(0);
            $table->boolean('selectable')->default(1);
            $table->integer('editedon')->default(0);
            $table->integer('createdon')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_templates');
    }
}
