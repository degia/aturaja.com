<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('net_worth_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('total_assets', 15, 2);
            $table->decimal('total_liabilities', 15, 2);
            $table->decimal('net_worth', 15, 2);
            $table->timestamps();

            $table->unique(['workspace_id', 'snapshot_date'], 'snapshots_ws_date_unique');
            $table->index(['workspace_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worth_snapshots');
    }
};
