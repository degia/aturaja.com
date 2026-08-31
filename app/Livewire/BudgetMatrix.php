<?php

namespace App\Livewire;

use App\Domain\Budgets\Actions\BudgetReport;
use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Livewire\Component;

class BudgetMatrix extends Component
{
    public string $periodMonth;

    /** @var array<int, string> category_id => limit input */
    public array $limits = [];

    /** @var array<int, int> category_id => alert threshold input */
    public array $thresholds = [];

    public bool $saved = false;

    public function mount(?string $periodMonth = null): void
    {
        $this->periodMonth = $periodMonth ?: now()->format('Y-m');
        $this->hydrateInputs();
    }

    public function setPeriodMonth(?string $month = null): void
    {
        $this->saved = false;
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->periodMonth = $month;
        }
        $this->hydrateInputs();
    }

    public function shift(int $direction): void
    {
        $this->saved = false;
        $this->periodMonth = Carbon::createFromFormat('!Y-m', $this->periodMonth)->addMonths($direction)->format('Y-m');
        $this->hydrateInputs();
    }

    public function copyFromPreviousMonth(): void
    {
        $this->saved = false;
        $current = Carbon::createFromFormat('!Y-m', $this->periodMonth);
        $previous = $current->copy()->subMonth()->format('Y-m-01');

        foreach (Budget::query()->forPeriod($previous)->get() as $prev) {
            $this->limits[$prev->category_id] = (string) (float) $prev->limit_amount;
            $this->thresholds[$prev->category_id] = (int) $prev->alert_threshold_percent;
        }
    }

    public function save(): void
    {
        $firstDay = Carbon::createFromFormat('!Y-m', $this->periodMonth)->startOfMonth()->format('Y-m-d');

        foreach ($this->limits as $categoryId => $limit) {
            $limit = (float) ($limit ?? 0);
            $threshold = min(100, max(1, (int) ($this->thresholds[$categoryId] ?? 80)));

            $existing = Budget::query()
                ->where('period_month', $firstDay)
                ->where('category_id', $categoryId)
                ->first();

            if ($limit > 0) {
                Budget::updateOrCreate(
                    ['period_month' => $firstDay, 'category_id' => $categoryId],
                    ['limit_amount' => $limit, 'alert_threshold_percent' => $threshold],
                );
            } elseif ($existing) {
                $existing->delete();
            }
        }

        $this->hydrateInputs();
        $this->saved = true;
    }

    public function render()
    {
        $report = new BudgetReport;
        $firstDay = Carbon::createFromFormat('!Y-m', $this->periodMonth)->startOfMonth()->format('Y-m-d');
        $rows = $report->forMonth($this->periodMonth);

        $categories = Category::query()
            ->active()
            ->expense()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();

        return view('livewire.budget-matrix', [
            'periodLabel' => Carbon::createFromFormat('!Y-m', $this->periodMonth)->translatedFormat('F Y'),
            'periodFirstDay' => $firstDay,
            'rows' => $rows,
            'totalLimit' => (float) $rows->sum('limit'),
            'totalActual' => (float) $rows->sum('actual'),
            'categories' => $categories,
            'alerts' => $report->alertsForMonth($this->periodMonth),
        ]);
    }

    private function hydrateInputs(): void
    {
        $this->limits = [];
        $this->thresholds = [];
        $firstDay = Carbon::createFromFormat('!Y-m', $this->periodMonth)->startOfMonth()->format('Y-m-d');

        foreach (Budget::query()->forPeriod($firstDay)->get() as $budget) {
            $this->limits[$budget->category_id] = (string) (float) $budget->limit_amount;
            $this->thresholds[$budget->category_id] = (int) $budget->alert_threshold_percent;
        }
    }
}
