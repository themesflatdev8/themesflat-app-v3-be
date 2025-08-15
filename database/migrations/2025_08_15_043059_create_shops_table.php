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
        Schema::create('shops', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->primary();
            $table->string('shop', 255)->unique();
            $table->text('access_token');
            $table->boolean('is_active')->default(true);
            $table->string('shopify_plan');
            $table->float('app_version')->nullable();
            $table->string('app_plan')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->bigInteger('billing_id')->nullable();
            $table->string('billing_on')->nullable();
            $table->string('cancelled_on')->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamp('uninstalled_at')->nullable();
            $table->text('scope')->nullable();
            $table->text('domain')->nullable();
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
        Schema::dropIfExists('shops');
    }
};
