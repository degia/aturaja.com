<?php

namespace App\Domain\Exports\Services;

use App\Domain\Budgets\Actions\BudgetReport;
use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Domain\Reports\Actions\CashFlowReport;
use App\Domain\Reports\Actions\ExpenseBreakdownReport;
use App\Models\Asset;
use App\Models\Liability;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportExportData
{
    public static function label(string $report): string
    {
        return [
            'transactions' => 'Daftar Transaksi',
            'cash_flow' => 'Arus Kas (Cash Flow)',
            'net_worth' => 'Net Worth',
            'budget_vs_actual' => 'Budget vs Actual',
            'expense_breakdown' => 'Perincian Pengeluaran',
        ][$report] ?? $report;
    }

    public function headings(string $report): array
    {
        return match ($report) {
            'transactions' => ['Tanggal', 'Tipe', 'Kategori', 'Akun', 'Catatan', 'Tag', 'Jumlah'],
            'cash_flow' => ['Periode', 'Pemasukan', 'Pengeluaran', 'Bersih'],
            'net_worth' => ['Jenis', 'Nama', 'Kategori', 'Nilai'],
            'budget_vs_actual' => ['Kategori', 'Limit', 'Realisasi', 'Penggunaan (%)', 'Status'],
            'expense_breakdown' => ['Kategori', 'Jumlah', 'Persentase (%)'],
            default => [],
        };
    }

    public function rows(string $report, ?Carbon $from, ?Carbon $to): Collection
    {
        $from = $from ? $from->copy()->startOfDay() : now()->startOfMonth();
        $to = $to ? $to->copy()->endOfDay() : now()->endOfMonth();

        return match ($report) {
            'transactions' => $this->transactions($from, $to),
            'cash_flow' => $this->cashFlow($from, $to),
            'net_worth' => $this->netWorth(),
            'budget_vs_actual' => $this->budgetVsActual($from),
            'expense_breakdown' => $this->expenseBreakdown($from, $to),
            default => collect(),
        };
    }

    private function transactions(Carbon $from, Carbon $to): Collection
    {
        return Transaction::query()
            ->with(['account:id,name', 'category:id,name', 'tags:id,name'])
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get()
            ->map(fn (Transaction $t) => [
                $t->transaction_date->format('d/m/Y'),
                $t->type_label,
                $t->category?->name ?? 'Tanpa Kategori',
                $t->account?->name ?? '-',
                $t->note ?? '',
                $t->tags->pluck('name')->implode(', '),
                $this->formatAmount($t),
            ]);
    }

    private function cashFlow(Carbon $from, Carbon $to): Collection
    {
        $report = new CashFlowReport();
        $trend = $report->trend('month', $from, $to);

        $rows = $trend->map(fn ($row) => [
            $row['label'],
            $this->num($row['income']),
            $this->num($row['expense']),
            $this->num($row['income'] - $row['expense']),
        ]);

        $totals = $report->totals($from, $to);
        $rows->push([
            'TOTAL',
            $this->num($totals['income']),
            $this->num($totals['expense']),
            $this->num($totals['net']),
        ]);

        return $rows;
    }

    private function netWorth(): Collection
    {
        $rows = collect();

        Asset::query()
            ->with('linkedAccount:id,name')
            ->orderBy('name')
            ->get()
            ->each(function (Asset $asset) use ($rows) {
                $rows->push(['Aset', $asset->name, $asset->category_label, $this->num($asset->current_value)]);
            });

        Liability::query()
            ->with('linkedAccount:id,name')
            ->orderBy('name')
            ->get()
            ->each(function (Liability $liability) use ($rows) {
                $rows->push(['Kewajiban', $liability->name, $liability->category_label, $this->num($liability->principal_remaining)]);
            });

        $totals = (new NetWorthCalculator())->calculate();
        $rows->push([]);
        $rows->push(['Total Aset', '', '', $this->num($totals['assets'])]);
        $rows->push(['Total Kewajiban', '', '', $this->num($totals['liabilities'])]);
        $rows->push(['Net Worth', '', '', $this->num($totals['net_worth'])]);

        return $rows;
    }

    private function budgetVsActual(Carbon $from): Collection
    {
        return (new BudgetReport())->forMonth($from)
            ->map(fn ($row) => [
                $row['category_name'],
                $this->num($row['limit']),
                $this->num($row['actual']),
                $this->num($row['usage']),
                $this->statusLabel($row['status']),
            ]);
    }

    private function expenseBreakdown(Carbon $from, Carbon $to): Collection
    {
        return (new ExpenseBreakdownReport())->byCategory($from, $to, 1000)
            ->map(fn ($row) => [
                $row['name'],
                $this->num($row['amount']),
                $this->num($row['percentage']),
            ]);
    }

    private function formatAmount(Transaction $transaction): string
    {
        $value = $this->num($transaction->amount);

        return match ($transaction->type) {
            'income' => '+'.$value,
            'expense' => '-'.$value,
            default => $value,
        };
    }

    private function num(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    private function statusLabel(string $status): string
    {
        return [
            'green' => 'Aman',
            'yellow' => 'Waspada',
            'red' => 'Berlebih',
        ][$status] ?? $status;
    }
}
