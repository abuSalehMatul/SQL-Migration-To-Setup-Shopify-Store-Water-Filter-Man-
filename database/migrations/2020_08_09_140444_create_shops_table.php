<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_url')->nullable();
            $table->string('name')->nullable();
            $table->string('my_shopify_domain');
            $table->enum('status', ['new', 'install', 'uninstall', 'forbidden'])->default('new');
            $table->text('shop_details_json')->nullable();
            $table->text('setting_json')->nullable();
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
        Schema::dropIfExists('shops');
    }
}
