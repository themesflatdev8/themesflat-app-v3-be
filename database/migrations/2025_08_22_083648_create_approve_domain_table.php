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
        Schema::create('approve_domain', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain_name', 255);
            $table->string('email_domain', 255)->nullable();
            $table->integer('valid_days')->default(30);
            $table->string('security', 255)->nullable();
            $table->dateTime('created_security')->nullable();
            $table->string('status', 20)->default('pending');
            $table->char('status_security', 1)->default('0');
            $table->char('active_page', 1)->default('0');
            $table->dateTime('created_active')->nullable();
            $table->char('OTP', 4)->nullable();
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
        Schema::dropIfExists('approve_domains');
    }
};
