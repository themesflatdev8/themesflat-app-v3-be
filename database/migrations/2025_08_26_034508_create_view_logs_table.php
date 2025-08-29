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
        Schema::create('view_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_id', 50);
            $table->string('user_id', 100);
            $table->string('handle', 255)->nullable();
            $table->string('domain_name', 255);
            $table->dateTime('viewed_at');
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
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
        Schema::dropIfExists('view_logs');
    }
};
