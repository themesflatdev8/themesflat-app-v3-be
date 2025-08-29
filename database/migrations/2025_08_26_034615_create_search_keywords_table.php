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
        Schema::create('search_keywords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shop_domain', 255);
            $table->string('keyword', 255);
            $table->date('date');
            $table->integer('count')->default(1);

            $table->unique(['shop_domain', 'keyword', 'date'], 'uniq_summary');
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
        Schema::dropIfExists('search_keywords');
    }
};
