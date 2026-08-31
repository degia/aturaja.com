<?php

namespace App\Http\Controllers;

use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Domain\Reports\Actions\CashFlowReport;
use App\Models\Account;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $from = now()->startOfMonth();
        $to = now();
        $previousFrom = $from->copy()->subMonth()->startOfMonth();
        $previousTo = $from->copy()->subDay()->endOfDay();

        $cashFlow = new CashFlowReport;
        $totals = $cashFlow->totals($from, $to);
        $previous = $cashFlow->totals($previousFrom, $previousTo);

        $netWorth = (new NetWorthCalculator)->calculate();

        $savingBalance = (float) Account::query()
            ->active()
            ->where('type', 'saving')
            ->sum('balance');

        $activeBalance = (float) Account::query()
            ->active()
            ->whereIn('type', Account::ACTIVE_BALANCE_TYPES)
            ->sum('balance');

        return view('dashboard.index', [
            'currentWorkspace' => auth()->user()->currentWorkspace(),
            'kpis' => [
                'income' => $totals['income'],
                'expense' => $totals['expense'],
                'net' => $totals['net'],
                'netWorth' => $netWorth['net_worth'],
                'saving' => $savingBalance,
                'activeBalance' => $activeBalance,
                'incomeDelta' => $previous['income'] > 0 ? round((($totals['income'] - $previous['income']) / $previous['income']) * 100, 1) : null,
                'expenseDelta' => $previous['expense'] > 0 ? round((($totals['expense'] - $previous['expense']) / $previous['expense']) * 100, 1) : null,
            ],
            'recentTransactions' => Transaction::query()
                ->with(['account:id,name', 'category:id,name,icon,color'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
