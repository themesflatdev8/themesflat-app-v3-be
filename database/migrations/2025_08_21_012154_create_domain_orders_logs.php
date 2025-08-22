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
        Schema::create('domain_orders_logs', function (Blueprint $table) {
            $table->id();

            // shopify_order_id BIGINT NOT NULL
            $table->bigInteger('shopify_order_id');

            // domain_name VARCHAR(255) NOT NULL
            $table->string('domain_name');

            // action_type VARCHAR(50)
            $table->string('action_type', 50)->nullable();

            // log_data JSON
            $table->json('log_data')->nullable();

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
        Schema::dropIfExists('domain_orders_logs');
    }
};
