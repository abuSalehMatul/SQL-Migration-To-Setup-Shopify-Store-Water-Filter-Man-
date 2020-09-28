<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->text('date_added')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_key')->nullable();
            $table->text('seo_key')->nullable();
            $table->text('description')->nullable();
            $table->text('shopify_collection_id')->nullable();
            $table->text('body_html')->nullable();

            $table->text('images')->nullable();
            $table->text('title')->nullable();
            $table->text('handle');
            $table->integer('status_upload')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collections');
    }
}
