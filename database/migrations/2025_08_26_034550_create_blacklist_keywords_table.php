<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blacklist_keywords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shop_domain', 255);
            $table->string('keyword', 255);
            $table->dateTime('searched_at');
            $table->string('user_ip', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();

            $table->index(['keyword', 'searched_at'], 'idx_keyword_searched_at');
            $table->index(['shop_domain', 'keyword'], 'idx_shop_keyword');
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
        Schema::dropIfExists('blacklist_keywords');
    }
};
