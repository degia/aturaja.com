<?php

namespace Tests\Feature;

use App\Domain\Budgets\Actions\BudgetReport;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetReportTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private Account $cash;

    private Category $food;

    private Category $transport;

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

        $this->cash = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        $this->food = Category::create(['name' => 'Makanan', 'type' => 'expense', 'icon' => '🍜', 'color' => '#16A34A']);
        $this->transport = Category::create(['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🚌', 'color' => '#0EA5E9']);
    }

    private function addExpense(Category $category, float $amount, string $date): void
    {
        Transaction::create([
            'account_id' => $this->cash->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => $amount,
            'transaction_date' => $date,
            'note' => 'test',
        ]);
    }

    public function test_usage_persen_menghitung_actual_dibagi_limit(): void
    {
        $this->assertSame(50.0, BudgetReport::usage(500_000, 1_000_000));
        $this->assertSame(0.0, BudgetReport::usage(0, 1_000_000));
        $this->assertSame(0.0, BudgetReport::usage(500_000, 0));
    }

    public function test_status_berubah_tepat_di_ambang_70_dan_100_persen(): void
    {
        $this->assertSame('green', BudgetReport::statusFor(0));
        $this->assertSame('green', BudgetReport::statusFor(69.99));
        $this->assertSame('yellow', BudgetReport::statusFor(70.0));
        $this->assertSame('yellow', BudgetReport::statusFor(99.99));
        $this->assertSame('red', BudgetReport::statusFor(100.0));
        $this->assertSame('red', BudgetReport::statusFor(150.0));
    }

    public function test_for_month_mengagregasi_realisasi_sesuai_sum_manual(): void
    {
        Budget::create([
            'period_month' => '2026-02-01',
            'category_id' => $this->food->id,
            'limit_amount' => 2_000_000,
            'alert_threshold_percent' => 80,
        ]);

        $this->addExpense($this->food, 500_000, '2026-02-03');
        $this->addExpense($this->food, 700_000, '2026-02-17');
        // Di luar bulan
        $this->addExpense($this->food, 900_000, '2026-01-20');
        // Kategori tanpa budget tidak boleh muncul
        $this->addExpense($this->transport, 100_000, '2026-02-05');

        $rows = (new BudgetReport())->forMonth('2026-02-01');

        $this->assertCount(1, $rows);

        $manual = Transaction::query()
            ->where('type', 'expense')
            ->where('category_id', $this->food->id)
            ->whereBetween('transaction_date', ['2026-02-01 00:00:00', '2026-02-28 23:59:59'])
            ->sum('amount');

        $row = $rows->first();
        $this->assertSame((float) $manual, $row['actual']);
        $this->assertSame(1_200_000.0, $row['actual']);
        $this->assertSame(60.0, $row['usage']);
        $this->assertSame('green', $row['status']);
    }

    public function test_alert_dipicu_per_threshold_kategori_bukan_hardcode_80(): void
    {
        // Makanan: threshold 50% -> 500rb dari 1jt = 50% -> alert
        Budget::create([
            'period_month' => '2026-03-01',
            'category_id' => $this->food->id,
            'limit_amount' => 1_000_000,
            'alert_threshold_percent' => 50,
        ]);
        // Transportasi: threshold 90% -> 200rb dari 1jt = 20% -> tidak alert
        Budget::create([
            'period_month' => '2026-03-01',
            'category_id' => $this->transport->id,
            'limit_amount' => 1_000_000,
            'alert_threshold_percent' => 90,
        ]);

        $this->addExpense($this->food, 500_000, '2026-03-10');
        $this->addExpense($this->transport, 200_000, '2026-03-11');

        $report = new BudgetReport();
        $rows = $report->forMonth('2026-03-01');
        $alerts = $report->alertsForMonth('2026-03-01');

        $foodRow = $rows->firstWhere('category_id', $this->food->id);
        $transportRow = $rows->firstWhere('category_id', $this->transport->id);

        $this->assertTrue($foodRow['alert']);
        $this->assertFalse($transportRow['alert']);

        $this->assertCount(1, $alerts);
        $this->assertSame($this->food->id, $alerts->first()['category_id']);
    }

    public function test_alert_tidak_picu_bila_belum_mencapai_threshold(): void
    {
        Budget::create([
            'period_month' => '2026-04-01',
            'category_id' => $this->food->id,
            'limit_amount' => 1_000_000,
            'alert_threshold_percent' => 80,
        ]);

        $this->addExpense($this->food, 200_000, '2026-04-05'); // 20% < 80%

        $report = new BudgetReport();
        $this->assertEmpty($report->alertsForMonth('2026-04-01'));
    }

    public function test_budget_matrix_component_menyimpan_limit_dan_menampilkan_progres(): void
    {
        Livewire::test(\App\Livewire\BudgetMatrix::class, ['periodMonth' => '2026-05'])
            ->set('limits', [$this->food->id => '1000000', $this->transport->id => '500000'])
            ->set('thresholds', [$this->food->id => 75, $this->transport->id => 80])
            ->call('save')
            ->assertSet('saved', true);

        $this->assertDatabaseHas('budgets', [
            'category_id' => $this->food->id,
            'period_month' => '2026-05-01',
            'limit_amount' => 1000000,
            'alert_threshold_percent' => 75,
        ]);
        $this->assertDatabaseHas('budgets', [
            'category_id' => $this->transport->id,
            'period_month' => '2026-05-01',
            'limit_amount' => 500000,
        ]);

        Livewire::test(\App\Livewire\BudgetMatrix::class, ['periodMonth' => '2026-05'])
            ->assertSet('limits', [$this->food->id => '1000000', $this->transport->id => '500000'])
            ->assertSet('thresholds', [$this->food->id => 75, $this->transport->id => 80]);
    }

    public function test_budget_matrix_mengosongkan_limit_menghapus_budget(): void
    {
        Budget::create([
            'period_month' => '2026-06-01',
            'category_id' => $this->food->id,
            'limit_amount' => 500_000,
            'alert_threshold_percent' => 80,
        ]);

        Livewire::test(\App\Livewire\BudgetMatrix::class, ['periodMonth' => '2026-06'])
            ->set('limits', [$this->food->id => '0'])
            ->call('save');

        $this->assertDatabaseMissing('budgets', ['category_id' => $this->food->id, 'period_month' => '2026-06-01']);
    }

    public function test_periode_kosong_tanpa_budget_mengembalikan_koleksi_kosong(): void
    {
        $this->assertTrue((new BudgetReport())->forMonth('2026-07-01')->isEmpty());
    }
}
