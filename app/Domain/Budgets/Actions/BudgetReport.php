<?php

namespace App\Domain\Budgets\Actions;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class BudgetReport
{
    /**
     * Rekap budget vs actual untuk satu periode bulan (on-the-fly).
     *
     * @return Collection<int, array{
     *     budget_id: int,
     *     category_id: int,
     *     category_name: string,
     *     icon: string,
     *     color: string,
     *     limit: float,
     *     actual: float,
     *     usage: float,
     *     status: 'green'|'yellow'|'red',
     *     alert: bool,
     * }>
     */
    public function forMonth(Carbon|string $periodMonth): Collection
    {
        $monthStart = Carbon::parse($periodMonth)->startOfMonth()->format('Y-m-d');
        $monthEnd = Carbon::parse($periodMonth)->endOfMonth()->format('Y-m-d');

        $budgets = Budget::query()
            ->with('category:id,name,icon,color')
            ->forPeriod($monthStart)
            ->orderBy('id')
            ->get();

        if ($budgets->isEmpty()) {
            return collect([]);
        }

        $actuals = Transaction::query()
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->select('category_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->map(function (Budget $budget) use ($actuals) {
            $actual = (float) ($actuals[$budget->category_id] ?? 0);
            $limit = (float) $budget->limit_amount;
            $usage = static::usage($actual, $limit);
            $status = static::statusFor($usage);

            return [
                'budget_id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name ?? 'Tanpa Kategori',
                'icon' => $budget->category?->icon ?? '🏷️',
                'color' => $budget->category?->color ?? '#9CA3AF',
                'limit' => $limit,
                'actual' => $actual,
                'usage' => $usage,
                'status' => $status,
                'alert' => $usage >= $budget->alert_threshold_percent,
            ];
        })->values();
    }

    /**
     * Hanya item budget yang memicu alert (realisasi >= threshold per kategori).
     */
    public function alertsForMonth(Carbon|string $periodMonth): Collection
    {
        return $this->forMonth($periodMonth)->filter(fn (array $row) => $row['alert'])->values();
    }

    /**
     * Persentase pemakaian budget. Batas 0 => dianggap belum terpakai (0%).
     */
    public static function usage(float $actual, float $limit): float
    {
        if ($limit <= 0) {
            return 0.0;
        }

        return round(($actual / $limit) * 100, 2);
    }

    /**
     * Status warna berdasarkan usage: green (<70%), yellow (70-99%), red (>=100%).
     */
    public static function statusFor(float $usage): string
    {
        if ($usage >= 100) {
            return 'red';
        }

        if ($usage >= 70) {
            return 'yellow';
        }

        return 'green';
    }
}
