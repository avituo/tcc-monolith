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
            $table->unsignedBigInteger('version')->default(1);
            $table->softDeletes();
            $table->unique('slug');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reserved_at', 'paid_at', 'cancelled_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropSoftDeletes();
            $table->dropColumn('version');
        });
    }
};
