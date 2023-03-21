<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_values', function (Blueprint $table) {
            $table->id();
            $table->integer('tmplvarid')->default(0)->index('user_values_tmplvarid_index');
            $table->integer('userid')->default(0)->index('user_values_userid_index');
            $table->mediumText('value')->nullable();
            
            $table->unique(['tmplvarid', 'userid'], 'user_values_tmplvarid_userid_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_values');
    }
}
