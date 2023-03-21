<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_content', function (Blueprint $table) {
            $table->integer('id')->index()->primary();
            $table->string('type', 20)->default('document')->index('typeidx');
            $table->string('contentType', 50)->default('text/html');
            $table->string('pagetitle')->default('');
            $table->string('longtitle')->default('');
            $table->string('description')->default('');
            $table->string('alias', 245)->default('')->index('aliasidx');
            $table->string('link_attributes')->default('');
            $table->integer('published')->default(0);
            $table->integer('pub_date')->default(0)->index('pub');
            $table->integer('unpub_date')->default(0)->index('unpub');
            $table->integer('parent')->default(0)->index('parent');
            $table->integer('isfolder')->default(0);
            $table->text('introtext')->nullable()->comment("Used to provide quick summary of the document");
            $table->mediumText('content')->nullable();
            $table->boolean('richtext')->default(1);
            $table->integer('template')->default(0);
            $table->integer('menuindex')->default(0);
            $table->integer('searchable')->default(1);
            $table->integer('cacheable')->default(1);
            $table->integer('createdby')->default(0);
            $table->integer('createdon')->default(0);
            $table->integer('editedby')->default(0);
            $table->integer('editedon')->default(0);
            $table->integer('deleted')->default(0);
            $table->integer('deletedon')->default(0);
            $table->integer('deletedby')->default(0);
            $table->integer('publishedon')->default(0);
            $table->integer('publishedby')->default(0);
            $table->string('menutitle')->default('')->comment("Menu title");
            $table->boolean('hide_from_tree')->default(0)->comment("Disable page hit count");
            $table->boolean('haskeywords')->default(0)->comment("has links to keywords");
            $table->boolean('hasmetatags')->default(0)->comment("has links to meta tags");
            $table->boolean('privateweb')->default(0)->comment("Private web document");
            $table->boolean('privatemgr')->default(0)->comment("Private manager document");
            $table->boolean('content_dispo')->default(0)->comment("0-inline, 1-attachment");
            $table->boolean('hidemenu')->default(0)->comment("Hide document from menu");
            $table->integer('alias_visible')->default(1);
            
            $table->index(['pub_date', 'unpub_date', 'published'], 'pub_unpub_published');
            $table->index(['pub_date', 'unpub_date'], 'pub_unpub');
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
        Schema::dropIfExists('site_content');
    }
}
