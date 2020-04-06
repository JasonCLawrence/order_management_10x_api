<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NullUserAndOrderOnDeleteInAuditLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('set null');

            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('order_id')
                    ->references('id')
                    ->on('orders');

            $table->foreign('user_id')
                    ->references('id')
                    ->on('users');
        });
    }
}
