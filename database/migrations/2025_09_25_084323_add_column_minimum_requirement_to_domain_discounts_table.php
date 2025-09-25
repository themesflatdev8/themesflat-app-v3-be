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
        Schema::table('domain_discounts', function (Blueprint $table) {
            $table->float('minimum_subtotal')->nullable()->after('minimum_requirement');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('domain_discounts', function (Blueprint $table) {
            $table->dropColumn('minimum_subtotal');
            //
        });
    }
};
