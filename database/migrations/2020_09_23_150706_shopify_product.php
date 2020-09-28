<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShopifyProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->text('product_length')->nullable();
            $table->text('weight')->nullable();
            $table->text('manufacturer_id')->nullable();
            $table->text('height')->nullable();
            $table->text('width')->nullable();
            $table->text('out_of_stock')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('short_desc')->nullable();
            $table->text('description')->nullable();
            $table->text('price')->nullable();
            $table->text('quantity')->nullable();
            $table->text('product_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            //
        });
    }
}
