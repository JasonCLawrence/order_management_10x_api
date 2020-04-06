<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_invoice_items', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('item');
            $table->string('description')->nullable();
            $table->integer('quantity');
            $table->bigInteger('price');

            $table->integer('order_id')->unsigned()->index();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_invoice_items');
    }
}
