<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Month extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_products', function (Blueprint $table) {
            $table->text('month6_price')->nullable();
            $table->text('month12_rrp')->nullable();
            $table->text('month12_price')->nullable();
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
