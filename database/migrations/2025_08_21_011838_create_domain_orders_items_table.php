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
        Schema::create('domain_order_items', function (Blueprint $table) {
            // id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
            $table->id();

            // shop_domain VARCHAR(255) NOT NULL
            $table->string('shop_domain')->index();

            // order_id
            $table->unsignedBigInteger('order_id')->index();

            // product_id BIGINT UNSIGNED
            $table->unsignedBigInteger('product_id')->nullable()->index();

            // variant_id BIGINT UNSIGNED
            $table->unsignedBigInteger('variant_id')->nullable()->index();

            // title VARCHAR(255)
            $table->string('title')->nullable();

            // variant_title VARCHAR(255)
            $table->string('variant_title')->nullable();

            // sku VARCHAR(100)
            $table->string('sku', 100)->nullable();

            // handle VARCHAR(255)
            $table->string('handle')->nullable();

            // vendor VARCHAR(100)
            $table->string('vendor', 100)->nullable();

            // product_type VARCHAR(100)
            $table->string('product_type', 100)->nullable();

            // image_url TEXT
            $table->text('image_url')->nullable();

            // quantity INT DEFAULT 0
            $table->integer('quantity')->default(0);

            // price DECIMAL(10, 2)
            $table->decimal('price', 10, 2)->nullable();

            // total_discount DECIMAL(10, 2)
            $table->decimal('total_discount', 10, 2)->nullable();

            // line_price DECIMAL(10, 2)
            $table->decimal('line_price', 10, 2)->nullable();

            // final_line_price DECIMAL(10, 2)
            $table->decimal('final_line_price', 10, 2)->nullable();

            // line_item_data LONGTEXT
            $table->longText('line_item_data')->nullable();

            // created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
        Schema::dropIfExists('domain_orders_items');
    }
};
