<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('decimal_separator', 20)->default('dot');
            $table->string('thousand_separator', 20)->default('comma');
            $table->boolean('remove_decimal_with_zero')->default(true);
            $table->boolean('space_between_price_and_price_symbol')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('decimal_separator');
            $table->dropColumn('thousand_separator');
            $table->dropColumn('remove_decimal_with_zero');
            $table->dropColumn('space_between_price_and_price_symbol');
        });
    }
};
