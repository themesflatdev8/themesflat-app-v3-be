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
        Schema::create('domain_orders', function (Blueprint $table) {
            // id BIGINT AUTO_INCREMENT PRIMARY KEY
            $table->id();

            // domain_name VARCHAR(255) NOT NULL
            $table->string('domain_name');

            // shopify_order_id BIGINT NOT NULL
            $table->bigInteger('shopify_order_id');

            // email VARCHAR(255)
            $table->string('email')->nullable();

            // total_price DECIMAL(10,2)
            $table->decimal('total_price', 10, 2)->nullable();

            // subtotal_price DECIMAL(10,2)
            $table->decimal('subtotal_price', 10, 2)->nullable();

            // total_discounts DECIMAL(10,2)
            $table->decimal('total_discounts', 10, 2)->nullable();

            // discount_codes JSON DEFAULT NULL
            $table->json('discount_codes')->nullable();

            // created_at DATETIME, currency VARCHAR(10)
            $table->string('currency', 10)->nullable();

            // financial_status VARCHAR(100)
            $table->string('financial_status', 100)->nullable();

            // fulfillment_status VARCHAR(100)
            $table->string('fulfillment_status', 100)->nullable();

            // customer_id BIGINT DEFAULT NULL
            $table->bigInteger('customer_id')->nullable();

            // order_data JSON
            $table->json('order_data')->nullable();

            // created_at & updated_at timestamps
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
        Schema::dropIfExists('domain_orders');
    }
};
