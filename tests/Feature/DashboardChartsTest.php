<?php

namespace Tests\Feature;

use App\Domain\Reports\Actions\CashFlowReport;
use App\Domain\Reports\Actions\ExpenseBreakdownReport;
use App\Livewire\DashboardCharts;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

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

        $account = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        $this->food = Category::create(['name' => 'Makanan', 'type' => 'expense', 'icon' => '🍜', 'color' => '#16A34A']);
        $this->transport = Category::create(['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🚌', 'color' => '#0EA5E9']);
        $salary = Category::create(['name' => 'Gaji', 'type' => 'income']);

        foreach ([
            [$this->food, 'expense', 1_000_000, '2026-01-02'],
            [$this->food, 'expense', 500_000, '2026-01-20'],
            [$this->transport, 'expense', 300_000, '2026-01-05'],
            [$salary, 'income', 7_000_000, '2026-01-03'],
        ] as [$cat, $type, $amount, $on]) {
            Transaction::create([
                'account_id' => $account->id,
                'category_id' => $cat->id,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $on,
                'note' => 'demo',
            ]);
        }
    }

    public function test_render_menampilkan_total_periode_dan_chart_data(): void
    {
        Livewire::test(DashboardCharts::class)
            ->set('anchor', '2026-01')
            ->assertSet('granularity', 'month')
            ->assertOk()
            ->assertSee('Rp 7,000,000')
            ->assertSee('Rp 1,800,000')
            ->assertSee('Makanan')
            ->assertSee('\u002726', false);
    }

    public function test_angka_dashboard_cocok_dengan_sum_manual_query(): void
    {
        $component = Livewire::test(DashboardCharts::class)
            ->set('anchor', '2026-01');

        $cashFlow = (new CashFlowReport())->totals(Carbon::create(2026, 1, 1), Carbon::create(2026, 1, 31));
        $breakdown = (new ExpenseBreakdownReport())
            ->byCategory(Carbon::create(2026, 1, 1), Carbon::create(2026, 1, 31));

        $component->assertSet('granularity', 'month');
        $this->assertSame(7_000_000.0, $cashFlow['income']);
        $this->assertSame(1_800_000.0, $cashFlow['expense']);
        $this->assertSame(2, $breakdown->count());
        $this->assertSame(1_500_000.0, $breakdown->firstWhere('id', $this->food->id)['amount']);
    }

    public function test_pilih_kategori_membuka_drilldown_transaksi(): void
    {
        Livewire::test(DashboardCharts::class)
            ->set('anchor', '2026-01')
            ->call('selectCategory', $this->food->id)
            ->assertSet('selectedCategoryId', $this->food->id)
            ->assertSee('Rincian Makanan')
            ->assertSee('Rp 1,500,000');

        $component = Livewire::test(DashboardCharts::class)
            ->set('anchor', '2026-01')
            ->call('selectCategory', $this->food->id)
            ->call('selectCategory', null);

        $this->assertNull($component->get('selectedCategoryId'));
        $component->assertDontSee('Rincian Makanan');
    }

    public function test_granularity_bulanan_menampilkan_12_bulan_trend(): void
    {
        $component = Livewire::test(DashboardCharts::class)
            ->set('anchor', '2026-01');

        $rendered = $component->html();

        foreach (['Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'] as $month) {
            $this->assertStringContainsString($month . ' \\\\u002725', $rendered);
        }
        $this->assertStringContainsString('Jan \\\\u002726', $rendered);
    }

    public function test_granularity_harian_menggunakan_bucket_harian(): void
    {
        Livewire::test(DashboardCharts::class)
            ->set('granularity', 'day')
            ->set('anchor', '2026-01')
            ->call('setGranularity', 'day')
            ->assertOk()
            ->assertSee('02 Jan');
    }
}