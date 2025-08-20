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
        Schema::create('domain_discounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain_name', 255);
            $table->bigInteger('shop_id');
            $table->string('shopify_discount_id', 100);
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->string('type', 100)->nullable();
            $table->json('codes')->nullable();
            $table->string('discount_value', 100)->nullable();
            $table->string('minimum_requirement', 255)->nullable();
            $table->integer('minimum_quantity')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('applies_to', 255)->nullable();
            $table->string('purchase_type', 100)->nullable();
            $table->json('related_handles')->nullable();
            $table->json('buy_handles')->nullable();
            $table->json('get_handles')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps(); // tương đương created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('domain_discounts');
    }
};
