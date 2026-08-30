<?php

namespace Tests\Feature;

use App\Domain\Transactions\Jobs\GenerateRecurringTransactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringRule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringJobTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private Account $account;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $user->id,
            'currency' => 'IDR',
        ]);
        $user->workspaces()->attach($this->workspace->id, ['role' => 'owner']);

        session(['current_workspace_id' => $this->workspace->id]);

        $this->account = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        $this->category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
    }

    public function test_job_generates_transaksi_untuk_aturan_yang_jatuh_tempo_dan_maju_menjadi_berikutnya(): void
    {
        $rule = RecurringRule::create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => today()->subMonth(),
            'next_run_date' => today(),
            'is_active' => true,
        ]);

        (new GenerateRecurringTransactions)->handle();

        $transaction = $rule->transactions()->first();

        $this->assertSame(1, $rule->fresh()->transactions()->count());
        $this->assertSame('100000.00', (string) $transaction->amount);
        $this->assertEquals($rule->id, $transaction->recurring_rule_id);
        $this->assertTrue($rule->fresh()->next_run_date->gt(today()));
        $this->assertSame('-100000.00', (string) $this->account->fresh()->balance);
    }

    public function test_job_melampaui_periode_aturan_mengnonaktifkan_atas_aturan(): void
    {
        $rule = RecurringRule::create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 50_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => today()->subMonths(2),
            'end_date' => today(),
            'next_run_date' => today(),
            'is_active' => true,
        ]);

        (new GenerateRecurringTransactions)->handle();

        $this->assertSame(1, $rule->refresh()->transactions()->count());
        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_job_mengabaikan_aturan_nonaktif(): void
    {
        RecurringRule::create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 50_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => today(),
            'next_run_date' => today(),
            'is_active' => false,
        ]);

        (new GenerateRecurringTransactions)->handle();

        $this->assertSame(0, Transaction::query()->count());
    }
}