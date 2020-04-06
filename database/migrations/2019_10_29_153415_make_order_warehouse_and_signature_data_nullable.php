<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeOrderWarehouseAndSignatureDataNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->datetime('warehouse_datetime')->nullable()->change();
            $table->decimal('warehouse_lat')->nullable()->change();
            $table->decimal('warehouse_long')->nullable()->change();

            $table->decimal('signature_lat')->nullable()->change();
            $table->decimal('signature_long')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    { }
}
