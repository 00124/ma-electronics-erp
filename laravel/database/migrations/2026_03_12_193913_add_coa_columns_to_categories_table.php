<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_account_id')->nullable()->after('parent_id');
            $table->unsignedBigInteger('cogs_account_id')->nullable()->after('sales_account_id');
            $table->unsignedBigInteger('inventory_account_id')->nullable()->after('cogs_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['sales_account_id', 'cogs_account_id', 'inventory_account_id']);
        });
    }
};
