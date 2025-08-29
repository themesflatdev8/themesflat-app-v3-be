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
        Schema::create('sold_records', function (Blueprint $table) {
            $table->bigIncrements('id'); // INT AUTO_INCREMENT -> bigIncrements
            $table->string('domain_name', 255);
            $table->string('product_id', 255);
            $table->text('product_name');
            $table->float('product_price');
            $table->float('price_coupon');
            $table->integer('product_unit');
            $table->float('total');
            $table->string('order_id', 255);
            $table->dateTime('order_date');
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
        Schema::dropIfExists('sold_records');
    }
};
