<?php

namespace App\Domain\Reports\Actions;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class CashFlowReport
{
    private const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function totals(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $income = (float) Transaction::query()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $expense = (float) Transaction::query()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => round($income - $expense, 2),
        ];
    }

    /**
     * Rangkaian income/expense per bucket (day/week/month/year) dalam rentang tanggal.
     *
     * @return Collection<int, array{key: string, label: string, income: float, expense: float}>
     */
    public function trend(string $granularity, Carbon $from, Carbon $to): Collection
    {
        $buckets = $this->buckets($granularity, $from, $to);

        $rows = Transaction::query()
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('transaction_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(transaction_date) as day_date')
            ->selectRaw('type')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('day_date', 'type')
            ->orderBy('day_date')
            ->get();

        $indexed = $buckets->keyBy('key');
        $filler = $indexed->map(fn () => ['income' => 0.0, 'expense' => 0.0])->all();

        foreach ($rows as $row) {
            $day = Carbon::parse($row->day_date);

            foreach ($indexed as $key => $bucket) {
                if ($bucket['start']->lte($day) && $bucket['end']->gte($day)) {
                    $filler[$key][$row->type] = round(((float) $filler[$key][$row->type]) + (float) $row->total, 2);
                    break;
                }
            }
        }

        return $indexed->map(function (array $bucket) use ($filler) {
            return [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'income' => $filler[$bucket['key']]['income'],
                'expense' => $filler[$bucket['key']]['expense'],
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    private function buckets(string $granularity, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $buckets = collect();
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            switch ($granularity) {
                case 'day':
                    $start = $cursor->copy();
                    $end = $cursor->copy()->endOfDay();
                    $key = $cursor->format('Y-m-d');
                    $label = $cursor->format('d M');
                    $step = 1;
                    break;

                case 'week':
                    $start = $cursor->copy()->startOfWeek(Carbon::MONDAY);
                    $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
                    $key = $start->format('Y-W');
                    $label = $start->format('d M');
                    $step = 'week';
                    break;

                case 'year':
                    $start = $cursor->copy()->startOfYear();
                    $end = $start->copy()->endOfYear();
                    $key = (string) $start->year;
                    $label = (string) $start->year;
                    $step = 'year';
                    break;

                case 'month':
                default:
                    $start = $cursor->copy()->startOfMonth();
                    $end = $start->copy()->endOfMonth();
                    $key = $start->format('Y-m');
                    $label = $this->labelMonth($start);
                    $step = 'month';
                    break;
            }

            $buckets->push(compact('key', 'label', 'start', 'end'));

            $cursor = match ($step) {
                'week' => $start->copy()->addWeek(),
                'year' => $start->copy()->addYear(),
                'month' => $start->copy()->addMonth(),
                default => $cursor->copy()->addDay(),
            };
        }

        return $buckets;
    }

    private function labelMonth(Carbon $date): string
    {
        return self::MONTHS_ID[$date->month - 1]." '".substr((string) $date->year, 2);
    }
}