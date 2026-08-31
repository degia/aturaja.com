<?php

namespace Tests\Feature;

use App\Domain\Reports\Actions\HealthScoreCalculator;
use App\Models\Account;
use App\Models\Debt;
use App\Models\Liability;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthScoreTest extends TestCase
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

    private function transaction(string $type, float $amount, string $date): Transaction
    {
        $account = Account::create(['name' => 'Akun '.uniqid(), 'type' => 'bank', 'balance' => 0]);

        return Transaction::create([
            'account_id' => $account->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
        ]);
    }

    public function test_skor_kesehatan_adalah_rata_rata_tiga_subskor(): void
    {
        $report = (new HealthScoreCalculator)->buildResult(
            income: 10_000_000,
            expense: 7_000_000,
            monthlyInstallments: 1_000_000,
            emergencyBalance: 15_000_000,
            avgMonthlyExpense: 5_000_000,
        );

        // Savings Rate = 30% -> 100 (cap)
        // DTI = 10% -> 100
        // Emergency = 3 bln -> 100
        $this->assertSame(100, $report['score']);
        $this->assertSame('Sehat', $report['score_label']);
    }

    public function test_status_rasio_tabungan_di_bawah_tepat_dan_di_atas_ideal(): void
    {
        $calculator = new HealthScoreCalculator;

        $this->assertSame('healthy', $calculator->savingsRateStatus(20));
        $this->assertSame('healthy', $calculator->savingsRateStatus(25));
        $this->assertSame('warning', $calculator->savingsRateStatus(10));
        $this->assertSame('warning', $calculator->savingsRateStatus(15));
        $this->assertSame('danger', $calculator->savingsRateStatus(5));
        $this->assertSame('danger', $calculator->savingsRateStatus(0));
    }

    public function test_status_dti_di_bawah_tepat_dan_di_atas_ideal(): void
    {
        $calculator = new HealthScoreCalculator;

        $this->assertSame('healthy', $calculator->dtiStatus(30));
        $this->assertSame('healthy', $calculator->dtiStatus(20));
        $this->assertSame('warning', $calculator->dtiStatus(31));
        $this->assertSame('warning', $calculator->dtiStatus(43));
        $this->assertSame('danger', $calculator->dtiStatus(44));
        $this->assertSame('danger', $calculator->dtiStatus(70));
    }

    public function test_status_dana_darurat_di_bawah_di_ideal_dan_di_atas(): void
    {
        $calculator = new HealthScoreCalculator;

        $this->assertSame('danger', $calculator->emergencyStatus(0));
        $this->assertSame('warning', $calculator->emergencyStatus(2));
        $this->assertSame('healthy', $calculator->emergencyStatus(3));
        $this->assertSame('healthy', $calculator->emergencyStatus(6));
        $this->assertSame('warning', $calculator->emergencyStatus(7));
    }

    public function test_rekomendasi_dihasilkan_berdasarkan_status(): void
    {
        $report = (new HealthScoreCalculator)->buildResult(
            income: 10_000_000,
            expense: 9_000_000,
            monthlyInstallments: 5_000_000,
            emergencyBalance: 0,
            avgMonthlyExpense: 5_000_000,
        );

        $savings = $report['ratios']['savings_rate'];
        $this->assertSame('warning', $savings['status']);
        $this->assertSame('Waspada', $savings['status_label']);
        $this->assertStringContainsString('10', $savings['recommendation']);

        $dti = $report['ratios']['dti'];
        $this->assertSame('danger', $dti['status']);
        $this->assertStringContainsString('50', $dti['recommendation']);

        $emergency = $report['ratios']['emergency_fund'];
        $this->assertSame('danger', $emergency['status']);
        $this->assertStringContainsString('belum memiliki dana darurat', $emergency['recommendation']);
    }

    public function test_kalkulasi_end_to_end_dengan_model_real(): void
    {
        $this->transaction('income', 10_000_000, '2026-08-15');
        $this->transaction('expense', 7_000_000, '2026-08-20');
        $this->transaction('expense', 5_000_000, '2026-07-15');

        Liability::create(['name' => 'KPR', 'category' => 'mortgage', 'principal_remaining' => 100_000_000, 'monthly_installment' => 1_000_000]);
        Account::create(['name' => 'Dana Darurat', 'type' => 'bank', 'balance' => 15_000_000, 'is_emergency_fund' => true]);
        Account::create(['name' => 'Tabungan', 'type' => 'bank', 'balance' => 9_000_000, 'is_emergency_fund' => false]);

        $report = (new HealthScoreCalculator)->calculate('2026-08-31');

        // Income 10jt, expense 7jt -> savings rate 30%
        $this->assertSame(30.0, $report['ratios']['savings_rate']['value']);
        $this->assertSame(10.0, $report['ratios']['dti']['value']);
        // Emergency balance hanya akun bertanda, avg expense (7+5)/2 = 6jt -> 2.5 bln
        $this->assertSame(2.5, $report['ratios']['emergency_fund']['value']);

        $this->assertSame('healthy', $report['ratios']['savings_rate']['status']);
        $this->assertSame('healthy', $report['ratios']['dti']['status']);
        $this->assertSame('warning', $report['ratios']['emergency_fund']['status']);

        $this->assertSame(15_000_000.0, $report['totals']['emergency_balance']);
    }

    public function test_dti_menghitung_cicilan_debt_tenor_yang_jatuh_tempo(): void
    {
        $this->transaction('income', 10_000_000, '2026-08-15');

        $debt = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Bank',
            'principal_amount' => 6_000_000,
            'remaining_amount' => 6_000_000,
            'installments_count' => 6,
            'first_due_date' => '2026-07-10',
        ]);
        $debt->generateSchedule();

        // Cicilan jatuh tempo di bulan Agustus: no 1 (10 Jul) & no 2 (10 Agu)
        $report = (new HealthScoreCalculator)->calculate('2026-08-31');

        // installment bulanan = 6.000.000 / 6 = 1.000.000
        $this->assertSame(1_000_000.0, $report['totals']['monthly_installments']);
        $this->assertSame(10.0, $report['ratios']['dti']['value']);
    }
}
