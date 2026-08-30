<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'ewallet', 'credit_card']);
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedTinyInteger('billing_date')->nullable();
            $table->unsignedTinyInteger('due_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};