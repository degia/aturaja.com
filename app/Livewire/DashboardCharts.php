<?php

namespace App\Livewire;

use App\Domain\Reports\Actions\CashFlowReport;
use App\Domain\Reports\Actions\ExpenseBreakdownReport;
use Carbon\Carbon;
use Livewire\Component;

class DashboardCharts extends Component
{
    public string $granularity = 'month';

    public string $anchor = '';

    public string $customFrom = '';

    public string $customTo = '';

    public ?int $selectedCategoryId = null;

    public function mount(): void
    {
        $this->anchor = now()->format('Y-m');
    }

    public function setGranularity(): void
    {
        $this->selectedCategoryId = null;
    }

    public function shift(int $direction): void
    {
        if ($this->granularity === 'year') {
            $year = (int) ($this->anchor ?: now()->year) + $direction;
            $this->anchor = (string) $year;
        } elseif ($this->granularity === 'custom') {
            $month = now()->copy();
            foreach (['customFrom', 'customTo'] as $field) {
                if ($this->{$field}) {
                    $month = Carbon::parse($this->{$field});
                    break;
                }
            }
            $this->customFrom = $month->copy()->addMonths($direction)->startOfMonth()->format('Y-m-d');
            $this->customTo = $month->copy()->addMonths($direction)->endOfMonth()->format('Y-m-d');
        } else {
            $ref = Carbon::createFromFormat('!Y-m', $this->anchor ?: now()->format('Y-m'))->addMonths($direction);
            $this->anchor = $ref->format('Y-m');
        }

        $this->selectedCategoryId = null;
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function render()
    {
        [$from, $to] = $this->range();
        $length = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($length - 1);

        $cashFlow = new CashFlowReport;
        $breakdown = new ExpenseBreakdownReport;

        $totals = $cashFlow->totals($from, $to);
        $previous = $cashFlow->totals($prevFrom, $prevTo);

        $trendGranularity = $this->granularity === 'day' ? 'day' : 'month';
        $trend = $cashFlow->trend($trendGranularity, $from, $to);

        $topExpenses = $breakdown->byCategory($from, $to, 6);

        $breakdownLabels = $topExpenses->pluck('name')->all();
        $breakdownValues = $topExpenses->pluck('amount')->map(fn ($v) => round((float) $v, 2))->all();
        $breakdownColors = $topExpenses->pluck('color')->all();

        $drilldown = [];
        if ($this->selectedCategoryId) {
            $drilldown['category'] = $topExpenses->firstWhere('id', $this->selectedCategoryId);
            $drilldown['transactions'] = $breakdown->categoryTransactions($this->selectedCategoryId, $from, $to);
        }

        return view('livewire.dashboard-charts', [
            'periodLabel' => $this->periodLabel($from, $to),
            'summary' => [
                'income' => $totals['income'],
                'expense' => $totals['expense'],
                'net' => $totals['net'],
                'incomeChange' => $this->changePercent($previous['income'], $totals['income']),
                'expenseChange' => $this->changePercent($previous['expense'], $totals['expense']),
            ],
            'trendLabels' => $trend->pluck('label')->all(),
            'trendIncome' => $trend->pluck('income')->map(fn ($v) => round((float) $v, 2))->all(),
            'trendExpense' => $trend->pluck('expense')->map(fn ($v) => round((float) $v, 2))->all(),
            'breakdownLabels' => $breakdownLabels,
            'breakdownValues' => $breakdownValues,
            'breakdownColors' => $breakdownColors,
            'topExpenses' => $topExpenses,
            'drilldown' => $drilldown,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $now = now();

        return match ($this->granularity) {
            'day' => [
                Carbon::createFromFormat('!Y-m', $this->anchor ?: $now->format('Y-m'))->startOfMonth(),
                Carbon::createFromFormat('!Y-m', $this->anchor ?: $now->format('Y-m'))->endOfMonth(),
            ],
            'year' => [
                Carbon::create((int) ($this->anchor ?: $now->year), 1, 1),
                Carbon::create((int) ($this->anchor ?: $now->year), 12, 31),
            ],
            'custom' => [
                $this->customFrom ? Carbon::parse($this->customFrom)->startOfDay() : $now->copy()->subMonths(11)->startOfMonth(),
                $this->customTo ? Carbon::parse($this->customTo)->endOfDay() : $now->copy()->endOfMonth(),
            ],
            default => [
                Carbon::createFromFormat('!Y-m', $this->anchor ?: $now->format('Y-m'))->startOfMonth()->subMonths(11),
                Carbon::createFromFormat('!Y-m', $this->anchor ?: $now->format('Y-m'))->endOfMonth(),
            ],
        };
    }

    private function periodLabel(Carbon $from, Carbon $to): string
    {
        $fmt = fn (Carbon $d): string => $d->format('d M Y');

        return $from->format('Y-m') === $to->format('Y-m')
            ? $fmt($from).' – '.$to->endOfMonth()->format('d M Y')
            : $fmt($from).' – '.$fmt($to);
    }

    private function changePercent(float $previous, float $current): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
