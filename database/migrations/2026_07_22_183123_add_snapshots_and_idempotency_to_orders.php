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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('user_name_snapshot')->nullable()->after('user_id');
            $table->string('user_email_snapshot')->nullable()->after('user_name_snapshot');
            $table->uuid('idempotency_key')->nullable()->unique()->after('status');
            $table->string('idempotency_request_hash', 64)->nullable()->after('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['user_name_snapshot', 'user_email_snapshot', 'idempotency_key', 'idempotency_request_hash']);
        });
    }
};
