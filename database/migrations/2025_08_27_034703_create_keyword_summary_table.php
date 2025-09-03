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
        Schema::create('keyword_summary', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shop_domain', 255);
            $table->string('keyword', 255);
            $table->date('date');
            $table->integer('count')->default(1);

            // unique key
            $table->unique(['shop_domain', 'keyword', 'date'], 'uniq_summary');

            // index
            $table->index(['keyword', 'date'], 'idx_keyword_date');

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
        Schema::dropIfExists('keyword_summary');
    }
};
