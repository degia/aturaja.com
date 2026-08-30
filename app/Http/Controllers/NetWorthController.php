<?php

namespace App\Http\Controllers;

use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Liability;
use App\Models\NetWorthSnapshot;

class NetWorthController extends Controller
{
    public function __invoke()
    {
        $calculator = new NetWorthCalculator();
        $totals = $calculator->calculate();

        $snapshots = NetWorthSnapshot::query()
            ->orderBy('snapshot_date')
            ->get();

        return view('networth.index', [
            'totals' => $totals,
            'assets' => Asset::with('linkedAccount:id,name')->orderBy('name')->get(),
            'liabilities' => Liability::with('linkedAccount:id,name')->orderBy('name')->get(),
            'accounts' => Account::query()->whereIn('type', ['cash', 'bank', 'ewallet'])->orderBy('name')->get(),
            'creditAccounts' => Account::query()->where('type', 'credit_card')->orderBy('name')->get(),
            'snapshotLabels' => $snapshots->pluck('snapshot_date')->map(fn ($d) => $d->format('M y'))->all(),
            'snapshotAssets' => $snapshots->pluck('total_assets')->map(fn ($v) => round((float) $v, 2))->all(),
            'snapshotLiabilities' => $snapshots->pluck('total_liabilities')->map(fn ($v) => round((float) $v, 2))->all(),
            'snapshotNetWorth' => $snapshots->pluck('net_worth')->map(fn ($v) => round((float) $v, 2))->all(),
        ]);
    }
}
