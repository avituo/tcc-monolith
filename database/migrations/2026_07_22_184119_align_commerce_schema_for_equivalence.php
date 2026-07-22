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
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
            $table->decimal('price', 14, 2)->default(0)->change();
            $table->decimal('discount', 14, 2)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_price', 14, 2)->change();
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 2)->default(0)->change();
            $table->decimal('subtotal', 14, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable(false)->change();
            $table->decimal('price', 8, 2)->default(0)->change();
            $table->decimal('discount', 8, 2)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_price', 8, 2)->change();
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0)->change();
            $table->decimal('subtotal', 10, 2)->default(0)->change();
        });
    }
};
