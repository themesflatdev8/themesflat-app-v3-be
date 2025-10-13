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
        Schema::table('response', function (Blueprint $table) {
            $table->unique(
                ['shop_domain', 'api_name', 'param', 'expire_time'],
                'uniq_shop_api_param_expire'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('response', function (Blueprint $table) {
            $table->dropUnique('uniq_shop_api_param_expire');
        });
    }
};
