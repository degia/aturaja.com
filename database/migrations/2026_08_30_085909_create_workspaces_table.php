<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('currency', 3)->default('IDR');
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};