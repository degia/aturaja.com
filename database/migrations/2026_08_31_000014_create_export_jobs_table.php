<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['pdf', 'excel', 'csv'])->default('csv');
            $table->enum('report', ['cash_flow', 'net_worth', 'budget_vs_actual', 'expense_breakdown', 'transactions']);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->enum('status', ['queued', 'processing', 'done', 'failed'])->default('done');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'report']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
