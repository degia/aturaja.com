<?php

namespace App\Domain\Transactions\Jobs;

use App\Models\RecurringRule;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class GenerateRecurringTransactions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $previousWorkspace = session('current_workspace_id');

        try {
            RecurringRule::withoutGlobalScopes()
                ->where('is_active', true)
                ->where('next_run_date', '<=', today())
                ->orderBy('workspace_id')
                ->chunkById(50, function (Collection $rules) {
                    foreach ($rules->groupBy('workspace_id') as $workspaceId => $group) {
                        session(['current_workspace_id' => $workspaceId]);

                        foreach ($group as $rule) {
                            $this->generate($rule);
                        }
                    }
                });
        } finally {
            session(['current_workspace_id' => $previousWorkspace]);
        }
    }

    private function generate(RecurringRule $rule): void
    {
        $transaction = Transaction::create([
            'account_id' => $rule->account_id,
            'category_id' => $rule->category_id,
            'transfer_to_account_id' => $rule->transfer_to_account_id,
            'type' => $rule->type,
            'amount' => $rule->amount,
            'transaction_date' => $rule->next_run_date,
            'note' => $rule->note,
            'recurring_rule_id' => $rule->id,
        ]);

        if ($transaction->exists) {
            $rule->advanceNextRunDate();
        }
    }
}