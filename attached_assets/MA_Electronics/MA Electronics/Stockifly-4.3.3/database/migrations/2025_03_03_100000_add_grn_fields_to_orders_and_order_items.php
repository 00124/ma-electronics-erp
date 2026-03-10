<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrnFieldsToOrdersAndOrderItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('parent_order_id')->unsigned()->nullable()->after('order_type');
            $table->foreign('parent_order_id')->references('id')->on('orders')->onDelete('set null')->onUpdate('cascade');
            $table->string('supplier_invoice_number', 100)->nullable()->after('notes');
            $table->string('delivery_challan_no', 100)->nullable()->after('supplier_invoice_number');
            $table->string('received_by_name', 100)->nullable()->after('delivery_challan_no');
            $table->string('received_by_signature', 255)->nullable()->after('received_by_name');
            $table->date('received_by_date')->nullable()->after('received_by_signature');
            $table->string('checked_by_name', 100)->nullable()->after('received_by_date');
            $table->string('checked_by_signature', 255)->nullable()->after('checked_by_name');
            $table->date('checked_by_date')->nullable()->after('checked_by_signature');
            $table->string('approved_by_name', 100)->nullable()->after('checked_by_date');
            $table->string('approved_by_signature', 255)->nullable()->after('approved_by_name');
            $table->date('approved_by_date')->nullable()->after('approved_by_signature');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->double('received_quantity', 10, 2)->nullable()->after('quantity');
            $table->double('short_damaged_quantity', 10, 2)->nullable()->after('received_quantity');
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
            $table->dropForeign(['parent_order_id']);
            $table->dropColumn([
                'parent_order_id', 'supplier_invoice_number', 'delivery_challan_no',
                'received_by_name', 'received_by_signature', 'received_by_date',
                'checked_by_name', 'checked_by_signature', 'checked_by_date',
                'approved_by_name', 'approved_by_signature', 'approved_by_date',
            ]);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'short_damaged_quantity']);
        });
    }
}
