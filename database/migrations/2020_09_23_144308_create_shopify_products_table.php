<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopifyProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_products', function (Blueprint $table) {
            $table->id();
            $table->text('handle')->nullable();
            $table->text('body_html')->nullable();
            $table->text('images')->nullable();
            $table->text('options')->nullable();
            $table->text('product_type')->nullable();
            $table->text('published_at')->nullable();
            $table->text('published_scope')->nullable();
            $table->text('tags')->nullable();
            $table->text('template_suffix')->nullable();
            $table->text('title')->nullable();
            $table->text('variants')->nullable();
            $table->text('vendor')->nullable();
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
        Schema::dropIfExists('shopify_products');
    }
}
