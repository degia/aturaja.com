<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('type', ['cash', 'bank', 'ewallet', 'saving', 'credit_card'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('type', ['cash', 'bank', 'ewallet', 'credit_card'])->change();
        });
    }
};
