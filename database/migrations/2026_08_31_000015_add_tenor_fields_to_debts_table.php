<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->tinyInteger('installments_count')->nullable()->after('due_date');
            $table->date('first_due_date')->nullable()->after('installments_count');
            $table->decimal('installment_amount', 15, 2)->nullable()->after('first_due_date');
            $table->foreignId('default_account_id')->nullable()->after('installment_amount')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_account_id');
            $table->dropColumn(['installments_count', 'first_due_date', 'installment_amount']);
        });
    }
};