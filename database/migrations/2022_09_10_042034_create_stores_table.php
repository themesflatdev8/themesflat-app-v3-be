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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('store_id');
            $table->string('name');
            $table->string('shopify_domain');
            $table->string('access_token')->nullable();
            $table->string('domain');
            $table->string('shopify_plan');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('owner')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('primary_locale')->nullable();
            $table->string('currency')->nullable();
            $table->string('money_format')->nullable();
            $table->integer('app_status')->default(1)->nullable();
            $table->float('app_version')->nullable();
            $table->string('app_plan')->nullable();
            $table->bigInteger('billing_id')->nullable();
            $table->string('billing_on')->nullable();
            $table->string('cancelled_on')->nullable();
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
        Schema::dropIfExists('stores');
    }
};
