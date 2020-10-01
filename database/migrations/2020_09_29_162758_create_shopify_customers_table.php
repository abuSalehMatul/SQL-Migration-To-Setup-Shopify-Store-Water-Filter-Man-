<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopifyCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_customers', function (Blueprint $table) {
            $table->id();
            $table->text('user_name')->nullable();
            $table->text('password')->nullable();
            $table->text('email')->nullable();
            $table->text('member_id');
            $table->text('tag')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('phone')->nullable();
            $table->text('mobile')->nullable();
            $table->text('current_status')->nullable();
            $table->text('date_added')->nullable();
            $table->text('order_count')->nullable();
            $table->text('order_value')->nullable();
            $table->text('spl_offer')->nullable();
            $table->text('guest_status')->nullable();
            $table->text('addresses')->nullable();
            $table->text('company')->nullable();
            $table->integer('status_update')->default(0);
            $table->text("shopify_id")->nullable();
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
        Schema::dropIfExists('shopify_customers');
    }
}
