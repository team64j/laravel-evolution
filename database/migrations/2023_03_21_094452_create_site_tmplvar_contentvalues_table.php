<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteTmplvarContentvaluesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_tmplvar_contentvalues', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('tmplvarid')->default(0)->index('idx_tmplvarid')->comment("Template Variable id");
            $table->integer('contentid')->default(0)->index('idx_id')->comment("Site Content Id");
            $table->mediumText('value')->nullable();
            
            $table->unique(['tmplvarid', 'contentid'], 'ix_tvid_contentid');
            ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_tmplvar_contentvalues');
    }
}
