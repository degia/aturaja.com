<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', [
                'bank_loan',
                'mortgage',
                'vehicle_loan',
                'credit_card',
                'paylater',
                'other',
            ]);
            $table->decimal('principal_remaining', 15, 2);
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('linked_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};
