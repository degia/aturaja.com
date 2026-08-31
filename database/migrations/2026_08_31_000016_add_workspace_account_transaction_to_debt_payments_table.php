<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->after('debt_id')->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
};