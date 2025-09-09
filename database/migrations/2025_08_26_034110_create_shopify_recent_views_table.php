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
        Schema::create('shopify_recent_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_id', 50);
            $table->string('user_id', 100);
            $table->string('handle', 255)->nullable();
            $table->string('domain_name', 255);
            $table->dateTime('viewed_at');
            $table->tinyInteger('position')->default(0);
            $table->unique(['product_id', 'user_id', 'domain_name'], 'unique_view');
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
        Schema::dropIfExists('shopify_recent_views');
    }
};
