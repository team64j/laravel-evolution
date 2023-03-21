<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 50)->default('');
            $table->string('description')->default('');
            $table->integer('manage_metatags')->default(0)->comment("manage site meta tags and keywords");
            $table->integer('edit_doc_metatags')->default(0)->comment("edit document meta tags and keywords");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_roles');
    }
}
