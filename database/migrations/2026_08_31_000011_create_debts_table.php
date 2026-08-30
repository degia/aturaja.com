<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['payable', 'receivable']);
            $table->string('counterparty_name');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->date('due_date')->nullable();
            $table->enum('status', ['ongoing', 'partially_paid', 'paid']);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'direction']);
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
