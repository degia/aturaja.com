<?php

namespace App\Domain\Reports\Actions;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ExpenseBreakdownReport
{
    /**
     * Agregat pengeluaran per kategori pada periode, diurutkan terbesar, sisa digabung ke "Lainnya".
     *
     * @return Collection<int, array{id: int|null, name: string, icon: string, color: string, amount: float, percentage: float}>
     */
    public function byCategory(Carbon $from, Carbon $to, int $limit = 6): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $rows = Transaction::query()
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->whereBetween('transaction_date', [$from, $to])
            ->select('category_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $grandTotal = (float) $rows->sum('total');

        if ($grandTotal <= 0) {
            return collect([]);
        }

        $ranked = $rows->sortByDesc('total')->values();

        $top = $ranked->take($limit)->map(function ($row) use ($grandTotal) {
            $category = Category::query()->find($row->category_id);

            return [
                'id' => $category?->id,
                'name' => $category?->name ?? 'Tanpa Kategori',
                'icon' => $category?->icon ?? '🏷️',
                'color' => $category?->color ?? '#9CA3AF',
                'amount' => (float) $row->total,
                'percentage' => round(((float) $row->total / $grandTotal) * 100, 1),
            ];
        });

        $restTotal = (float) $ranked->skip($limit)->sum('total');

        if ($restTotal > 0) {
            $top->push([
                'id' => null,
                'name' => 'Lainnya',
                'icon' => '⋯',
                'color' => '#C7D2CB',
                'amount' => $restTotal,
                'percentage' => round(($restTotal / $grandTotal) * 100, 1),
            ]);
        }

        return $top;
    }

    /**
     * Daftar transaksi pengeluaran (semua atau satu kategori) pada periode, untuk drill-down.
     */
    public function categoryTransactions(?int $categoryId, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return Transaction::query()
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->whereBetween('transaction_date', [$from, $to])
            ->with(['account:id,name', 'category:id,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
    }
}