<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('recurring_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'transaction_date']);
            $table->index(['workspace_id', 'type']);
            $table->index('account_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};