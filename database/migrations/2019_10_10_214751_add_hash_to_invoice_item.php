<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHashToInvoiceItem extends Migration
{
    public function up()
    {
        Schema::table('order_invoice_items', function (Blueprint $table) {
            $table->string('hash')->nullable();
        });
    }

    public function down()
    {
        Schema::table('order_invoice_items', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
}
