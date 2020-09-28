<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('state')->nullable();
            $table->unsignedBigInteger('shop_id');
            $table->string('access_token');
            // $table->string('received_scopes');
            // $table->string('script_tag_src')->nullable();
            // $table->string('script_tag_event')->nullable();
            // $table->string('script_tag_display_scope')->nullable();
            // $table->string('script_tag_created_at')->nullable();
            // $table->string('script_tag_id')->nullable();
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
        Schema::dropIfExists('tokens');
    }
}
