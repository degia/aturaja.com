<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', [
                'cash_bank',
                'investment_stock',
                'investment_mutual_fund',
                'investment_gold',
                'investment_crypto',
                'real_estate',
                'vehicle',
                'other',
            ]);
            $table->decimal('current_value', 15, 2);
            $table->foreignId('linked_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('last_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
