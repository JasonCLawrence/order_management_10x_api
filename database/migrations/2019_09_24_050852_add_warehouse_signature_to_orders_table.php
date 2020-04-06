<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarehouseSignatureToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('warehouse_signed')->default(false);
            $table->string('warehouse_signature_data')->nullable();
            $table->string('warehouse_signee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('warehouse_signature_data');
            $table->dropColumn('warehouse_signed');
            $table->dropColumn('warehouse_signee');
        });
    }
}
