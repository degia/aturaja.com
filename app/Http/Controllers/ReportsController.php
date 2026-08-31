<?php

namespace App\Http\Controllers;

use App\Domain\Reports\Actions\CashFlowReport;
use App\Domain\Reports\Actions\ExpenseBreakdownReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $month = $request->query('month', now()->format('Y-m'));

        try {
            $reference = Carbon::createFromFormat('!Y-m', $month);
        } catch (\Throwable) {
            $reference = now()->startOfMonth();
        }

        $from = $reference->copy()->startOfMonth();
        $to = $reference->copy()->endOfMonth();

        $cashFlow = new CashFlowReport;
        $expenseBreakdown = new ExpenseBreakdownReport;
        $totals = $cashFlow->totals($from, $to);

        return view('reports.index', [
            'month' => $reference->format('Y-m'),
            'totals' => $totals,
            'trend' => $cashFlow->trend('day', $from, $to),
            'expenseByCategory' => $expenseBreakdown->byCategory($from, $to),
            'expenseTotal' => (float) $totals['expense'],
        ]);
    }
}
