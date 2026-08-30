<?php

namespace App\Domain\NetWorth\Jobs;

use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Models\NetWorthSnapshot;
use App\Models\Workspace;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SnapshotMonthlyNetWorth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly ?int $workspaceId = null,
        private readonly ?string $snapshotDate = null,
    ) {
    }

    public function handle(): void
    {
        $date = $this->snapshotDate ? Carbon::parse($this->snapshotDate) : now();
        $day = $date->copy()->endOfMonth()->format('Y-m-d');

        $workspaces = $this->workspaceId
            ? Workspace::query()->whereKey($this->workspaceId)->get()
            : Workspace::all();

        foreach ($workspaces as $workspace) {
            $this->snapshotForWorkspace($workspace->id, $day);
        }
    }

    private function snapshotForWorkspace(int $workspaceId, string $day): void
    {
        session(['current_workspace_id' => $workspaceId]);

        $totals = (new NetWorthCalculator())->calculate();

        NetWorthSnapshot::query()
            ->where('workspace_id', $workspaceId)
            ->where('snapshot_date', $day)
            ->delete();

        NetWorthSnapshot::create([
            'workspace_id' => $workspaceId,
            'snapshot_date' => $day,
            'total_assets' => $totals['assets'],
            'total_liabilities' => $totals['liabilities'],
            'net_worth' => $totals['net_worth'],
        ]);
    }
}
