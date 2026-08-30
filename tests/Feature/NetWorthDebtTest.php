<?php

namespace Tests\Feature;

use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Domain\NetWorth\Jobs\SnapshotMonthlyNetWorth;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Liability;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetWorthDebtTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

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
    }

    private function liquid(float $balance, string $type = 'bank'): Account
    {
        return Account::create(['name' => 'Akun', 'type' => $type, 'balance' => $balance]);
    }

    public function test_net_worth_aset_dikurangi_kewajiban_konsisten(): void
    {
        $this->liquid(10_000_000, 'bank');
        $this->liquid(2_000_000, 'ewallet');
        $this->liquid(5_000_000, 'credit_card');

        Asset::create(['name' => 'Rumah', 'category' => 'real_estate', 'current_value' => 500_000_000]);
        Asset::create(['name' => 'Emas', 'category' => 'investment_gold', 'current_value' => 20_000_000]);
        Liability::create(['name' => 'KPR', 'category' => 'mortgage', 'principal_remaining' => 300_000_000]);

        $totals = (new NetWorthCalculator())->calculate();

        $this->assertSame(532_000_000.0, $totals['assets']);
        $this->assertSame(305_000_000.0, $totals['liabilities']);
        $this->assertSame(227_000_000.0, $totals['net_worth']);
        $this->assertSame($totals['assets'] - $totals['liabilities'], $totals['net_worth']);
    }

    public function test_aset_kas_yang_ditautkan_ke_akun_tidak_dihitung_dua_kali(): void
    {
        $account = $this->liquid(7_000_000, 'bank');
        Asset::create([
            'name' => 'Tabungan',
            'category' => 'cash_bank',
            'current_value' => 7_000_000,
            'linked_account_id' => $account->id,
        ]);

        $totals = (new NetWorthCalculator())->calculate();

        // Hanya saldo akun yang dihitung, aset cash_bank ter-link tidak dihitung lagi
        $this->assertSame(7_000_000.0, $totals['assets']);
    }

    public function test_snapshot_job_idempotent_tidak_dobel_pada_hari_sama(): void
    {
        $this->liquid(10_000_000, 'bank');
        Asset::create(['name' => 'Mobil', 'category' => 'vehicle', 'current_value' => 100_000_000]);

        $job = new SnapshotMonthlyNetWorth($this->workspace->id, '2026-08-15');
        $job->handle();
        $job->handle();

        $day = Carbon::parse('2026-08-15')->endOfMonth()->format('Y-m-d');

        $this->assertSame(1, NetWorthSnapshot::where('workspace_id', $this->workspace->id)->count());
        $snapshot = NetWorthSnapshot::where('workspace_id', $this->workspace->id)->first();
        $this->assertSame($day, $snapshot->snapshot_date->format('Y-m-d'));
        $this->assertSame(110_000_000.0, (float) $snapshot->total_assets);
        $this->assertSame(110_000_000.0, (float) $snapshot->net_worth);
    }

    public function test_pembayaran_utang_memperbarui_sisa_dan_status_lunas(): void
    {
        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Budi',
            'principal_amount' => 1_000_000,
            'remaining_amount' => 1_000_000,
            'status' => 'ongoing',
        ]);

        DebtPayment::create(['debt_id' => $debt->id, 'amount' => 300_000, 'paid_at' => '2026-08-01']);

        $debt->refresh();
        $this->assertSame(700_000.0, (float) $debt->remaining_amount);
        $this->assertSame('partially_paid', $debt->status);

        DebtPayment::create(['debt_id' => $debt->id, 'amount' => 700_000, 'paid_at' => '2026-08-02']);

        $debt->refresh();
        $this->assertSame(0.0, (float) $debt->remaining_amount);
        $this->assertSame('paid', $debt->status);
    }

    public function test_menghapus_pembayaran_mengembalikan_sisa_utang(): void
    {
        $debt = Debt::create([
            'direction' => 'receivable',
            'counterparty_name' => 'Andi',
            'principal_amount' => 500_000,
            'remaining_amount' => 500_000,
            'status' => 'ongoing',
        ]);

        $payment = DebtPayment::create(['debt_id' => $debt->id, 'amount' => 500_000, 'paid_at' => '2026-08-03']);

        $debt->refresh();
        $this->assertSame('paid', $debt->status);

        $payment->delete();
        $debt->refresh();
        $this->assertSame(500_000.0, (float) $debt->remaining_amount);
        $this->assertSame('ongoing', $debt->status);
    }

    public function test_dashboard_dan_halaman_net_worth_memakai_kalkulasi_yang_sama(): void
    {
        $this->actingAs($workspaceUser = \App\Models\User::factory()->create());
        $workspaceUser->workspaces()->attach($this->workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $this->workspace->id]);

        $this->liquid(10_000_000, 'bank');
        Asset::create(['name' => 'Properti', 'category' => 'real_estate', 'current_value' => 200_000_000]);
        Liability::create(['name' => 'KPR', 'category' => 'mortgage', 'principal_remaining' => 50_000_000]);

        $response = $this->get('/dashboard');
        $response->assertOk()->assertSee('Net Worth');

        $netWorth = $this->get('/net-worth');
        $netWorth->assertOk()->assertSee('Net Worth')->assertSee('Total Aset');
    }

    public function test_halaman_debt_tracker_merender_dan_mencatat_pembayaran(): void
    {
        $this->actingAs($workspaceUser = \App\Models\User::factory()->create());
        $workspaceUser->workspaces()->attach($this->workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $this->workspace->id]);

        $this->get('/debts')->assertOk()->assertSee('Debt Tracker');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Bank',
            'principal_amount' => 2_000_000,
            'remaining_amount' => 2_000_000,
            'status' => 'ongoing',
        ]);

        $this->post("/debts/{$debt->id}/payments", [
            'amount' => 500_000,
            'paid_at' => now()->format('Y-m-d'),
        ])->assertRedirect('/debts');

        $this->assertDatabaseHas('debt_payments', ['debt_id' => $debt->id, 'amount' => 500_000]);
        $this->assertSame(1_500_000.0, (float) $debt->refresh()->remaining_amount);
    }
}
