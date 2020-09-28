<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopifyConnectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_connects', function (Blueprint $table) {
            $table->id();
            $table->text('category_id')->nullable();
            $table->text('shopify_category_id')->nullable();
            $table->text('product_id')->nullable();
            $table->text('shopify_product_id')->nullable();
            $table->text('connect_id')->nullable();
            $table->integer('upload_status')->default(0);
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
        Schema::dropIfExists('shopify_connects');
    }
}
