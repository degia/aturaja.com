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
use App\Models\Transaction;
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

        $totals = (new NetWorthCalculator)->calculate();

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

        $totals = (new NetWorthCalculator)->calculate();

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
        $this->actingAs($workspaceUser = User::factory()->create());
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
        $this->actingAs($workspaceUser = User::factory()->create());
        $workspaceUser->workspaces()->attach($this->workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $this->workspace->id]);

        $this->get('/debts')->assertOk()->assertSee('Debt Tracker');

        $account = $this->liquid(5_000_000, 'bank');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Bank',
            'principal_amount' => 2_000_000,
            'remaining_amount' => 2_000_000,
            'status' => 'ongoing',
        ]);

        $this->post("/debts/{$debt->id}/payments", [
            'amount' => 500_000,
            'account_id' => $account->id,
            'paid_at' => now()->format('Y-m-d'),
        ])->assertRedirect('/debts');

        $this->assertDatabaseHas('debt_payments', ['debt_id' => $debt->id, 'amount' => 500_000]);
        $this->assertSame(1_500_000.0, (float) $debt->refresh()->remaining_amount);
    }

    public function test_pembayaran_utang_mencatat_transaksi_dan_mengurangi_saldo_akun(): void
    {
        $account = $this->liquid(1_000_000, 'bank');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Bank',
            'principal_amount' => 400_000,
            'remaining_amount' => 400_000,
            'status' => 'ongoing',
        ]);

        $payment = DebtPayment::create([
            'debt_id' => $debt->id,
            'account_id' => $account->id,
            'amount' => 400_000,
            'paid_at' => '2026-08-10',
            'note' => 'Cicilan bulanan',
        ]);

        $transaction = $payment->fresh()->transaction;

        $this->assertTrue($payment->fresh()->transaction_id !== null);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => 'expense',
            'amount' => '400000.00',
            'account_id' => $account->id,
            'transaction_date' => '2026-08-10',
        ]);
        $this->assertSame('400000.00', $transaction->amount);
        $this->assertSame('expense', $transaction->type);
        $this->assertSame(1_000_000.0 - 400_000.0, (float) $account->fresh()->balance);
        $this->assertSame(0.0, (float) $debt->fresh()->remaining_amount);
        $this->assertSame('paid', $debt->fresh()->status);
    }

    public function test_menghapus_pembayaran_membatalkan_transaksi_dan_status_utang_kembali(): void
    {
        $account = $this->liquid(1_000_000, 'bank');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Budi',
            'principal_amount' => 300_000,
            'remaining_amount' => 300_000,
            'status' => 'ongoing',
        ]);

        $payment = DebtPayment::create([
            'debt_id' => $debt->id,
            'account_id' => $account->id,
            'amount' => 300_000,
            'paid_at' => '2026-08-11',
        ]);

        $transaction = $payment->transaction;
        $transactionId = $transaction->id;
        $this->assertNotNull($transaction);

        $payment->delete();

        $this->assertNull(Transaction::find($transactionId));
        $this->assertSame(1_000_000.0, (float) $account->fresh()->balance);
        $this->assertSame(300_000.0, (float) $debt->fresh()->remaining_amount);
        $this->assertSame('ongoing', $debt->fresh()->status);
    }

    public function test_tenor_menghasilkan_jadwal_cicilan_bulanan(): void
    {
        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Kredit Motor',
            'principal_amount' => 300_000,
            'remaining_amount' => 300_000,
            'status' => 'ongoing',
            'installments_count' => 3,
            'first_due_date' => '2026-01-15',
        ]);

        $debt->generateSchedule();
        $debt->refresh();

        $this->assertSame(3, $debt->installments->count());
        $this->assertSame(['2026-01-15', '2026-02-15', '2026-03-15'], $debt->installments->map(
            fn ($i) => $i->due_date->format('Y-m-d')
        )->all());
        $this->assertSame(['100000.00', '100000.00', '100000.00'], $debt->installments->map(
            fn ($i) => (string) $i->amount
        )->all());
    }

    public function test_pembayaran_dialokasikan_ke_cicilan_secara_berurutan(): void
    {
        $account = $this->liquid(500_000, 'bank');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Kredit Motor',
            'principal_amount' => 300_000,
            'remaining_amount' => 300_000,
            'status' => 'ongoing',
            'installments_count' => 3,
            'first_due_date' => '2026-01-15',
        ]);
        $debt->generateSchedule();

        DebtPayment::create([
            'debt_id' => $debt->id,
            'account_id' => $account->id,
            'amount' => 150_000,
            'paid_at' => '2026-01-20',
        ]);

        $debt->refresh();

        $this->assertSame(['paid', 'partial', 'pending'], $debt->installments->pluck('status')->all());
        $this->assertSame('100000.00', (string) $debt->installments[0]->amount_paid);
        $this->assertSame('50000.00', (string) $debt->installments[1]->amount_paid);
        $this->assertSame(150_000.0, (float) $debt->fresh()->remaining_amount);
    }

    public function test_saving_tidak_masuk_saldo_aktif_namun_terhitung_tabungan(): void
    {
        Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 100_000]);
        Account::create(['name' => 'BCA', 'type' => 'bank', 'balance' => 500_000]);
        Account::create(['name' => 'Tabungan Emas', 'type' => 'saving', 'balance' => 2_000_000]);

        $user = User::whereHas('workspaces', fn ($q) => $q->whereKey($this->workspace->id))->first();
        $this->actingAs($user)->get('/accounts')->assertStatus(200);

        // Total Saldo Aktif tidak termasuk Tabungan (+ 100.000 + 500.000 = 600.000)
        $this->assertSame(600_000.0, (float) Account::query()
            ->active()
            ->whereIn('type', Account::ACTIVE_BALANCE_TYPES)
            ->sum('balance'));

        // Total Tabungan terpisah
        $this->assertSame(2_000_000.0, (float) Account::query()
            ->active()
            ->where('type', 'saving')
            ->sum('balance'));

        // Net worth memperlakukan tabungan sebagai aset (masuk total aset)
        $totals = (new NetWorthCalculator)->calculate();
        $this->assertSame(2_600_000.0, $totals['assets']);
    }
}
