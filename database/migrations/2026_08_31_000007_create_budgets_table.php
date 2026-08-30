<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('limit_amount', 15, 2);
            $table->unsignedTinyInteger('alert_threshold_percent')->default(80);
            $table->timestamps();

            $table->unique(['workspace_id', 'period_month', 'category_id'], 'budgets_ws_month_cat_unique');
            $table->index(['workspace_id', 'period_month']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
