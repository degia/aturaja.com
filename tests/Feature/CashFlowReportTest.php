<?php

namespace Tests\Feature;

use App\Domain\Reports\Actions\CashFlowReport;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowReportTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private Category $incomeCat;

    private Category $expenseCat;

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

        $account = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        $this->incomeCat = Category::create(['name' => 'Gaji', 'type' => 'income']);
        $this->expenseCat = Category::create(['name' => 'Makanan', 'type' => 'expense']);

        $this->make(account: $account, category: $this->incomeCat, type: 'income', amount: 5_000_000, on: '2026-01-05');
        $this->make(account: $account, category: $this->expenseCat, type: 'expense', amount: 1_000_000, on: '2026-01-10');
        $this->make(account: $account, category: $this->expenseCat, type: 'expense', amount: 500_000, on: '2026-01-25');
        $this->make(account: $account, category: $this->expenseCat, type: 'expense', amount: 300_000, on: '2026-02-03');
        $this->make(account: $account, category: $this->expenseCat, type: 'expense', amount: 700_000, on: '2026-01-08');

        // Transfer TIDAK boleh ikut dihitung income/expense
        $other = Account::create(['name' => 'Bank', 'type' => 'bank', 'balance' => 0]);
        Transaction::create([
            'account_id' => $account->id,
            'transfer_to_account_id' => $other->id,
            'type' => 'transfer',
            'amount' => 999_000,
            'transaction_date' => '2026-01-15',
            'note' => 'pindah',
        ]);
    }

    private function make(Account $account, Category $category, string $type, float $amount, string $on): void
    {
        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $on,
            'note' => 'test',
        ]);
    }

    public function test_totals_sesuai_sum_manual_di_periode(): void
    {
        $result = (new CashFlowReport())->totals(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $manualIncome = (float) Transaction::query()
            ->where('type', 'income')
            ->whereBetween('transaction_date', ['2026-01-01 00:00:00', '2026-01-31 23:59:59'])
            ->sum('amount');

        $this->assertSame(5_000_000.0, $result['income']);
        $this->assertSame(2_200_000.0, $result['expense']); // 1jt + 500rb + 700rb (transaksi Feb tidak ikut)
        $this->assertSame(2_800_000.0, $result['net']);
        $this->assertSame($manualIncome, $result['income']);
    }

    public function test_totals_mengabaikan_transfer(): void
    {
        $result = (new CashFlowReport())->totals(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertArrayNotHasKey('transfer', $result);
        $this->assertSame(2_200_000.0, $result['expense']); // tidak terpengaruh transfer 999rb
    }

    public function test_trend_bulanan_mengelompokkan_per_bulan(): void
    {
        $trend = (new CashFlowReport())
            ->trend('month', Carbon::parse('2026-01-01'), Carbon::parse('2026-02-28'))
            ->keyBy('key');

        $this->assertSame(2, $trend->count());

        $this->assertSame(5_000_000.0, $trend['2026-01']['income']);
        $this->assertSame(2_200_000.0, $trend['2026-01']['expense']); // 1jt + 500rb + 700rb
        $this->assertSame(0.0, $trend['2026-02']['income']);
        $this->assertSame(300_000.0, $trend['2026-02']['expense']);
    }

    public function test_trend_harian_mengelompokkan_per_hari(): void
    {
        $trend = (new CashFlowReport())
            ->trend('day', Carbon::parse('2026-01-05'), Carbon::parse('2026-01-10'))
            ->keyBy('key');

        $this->assertSame(6, $trend->count());
        $this->assertSame(5_000_000.0, $trend['2026-01-05']['income']);
        $this->assertSame(0.0, $trend['2026-01-06']['income']);
    }

    public function test_trend_tahunan_mengelompokkan_per_tahun(): void
    {
        $trend = (new CashFlowReport())
            ->trend('year', Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'))
            ->keyBy('key');

        $this->assertSame(1, $trend->count());
        $this->assertSame(2_500_000.0, $trend['2026']['expense']);
    }

    public function test_totals_periode_kosong_nol(): void
    {
        $result = (new CashFlowReport())->totals(Carbon::parse('2025-01-01'), Carbon::parse('2025-01-31'));

        $this->assertSame(0.0, $result['income']);
        $this->assertSame(0.0, $result['expense']);
        $this->assertSame(0.0, $result['net']);
    }
}