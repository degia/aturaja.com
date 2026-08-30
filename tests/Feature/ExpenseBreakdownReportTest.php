<?php

namespace Tests\Feature;

use App\Domain\Reports\Actions\ExpenseBreakdownReport;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseBreakdownReportTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private Category $food;

    private Category $transport;

    private Category $salary;

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
        $this->salary = Category::create(['name' => 'Gaji', 'type' => 'income']);

        $eks = [
            [$this->food, 1_000_000, '2026-01-02'],
            [$this->food, 500_000, '2026-01-20'],
            [$this->transport, 300_000, '2026-01-05'],
            [$this->food, 700_000, '2026-01-08'],
            [$this->transport, 200_000, '2026-01-25'],
        ];
        foreach ($eks as [$cat, $amount, $on]) {
            Transaction::create([
                'account_id' => $account->id,
                'category_id' => $cat->id,
                'type' => 'expense',
                'amount' => $amount,
                'transaction_date' => $on,
                'note' => 'test',
            ]);
        }

        // Income & transfer tidak boleh masuk breakdown pengeluaran
        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->salary->id,
            'type' => 'income',
            'amount' => 9_000_000,
            'transaction_date' => '2026-01-03',
            'note' => 'gaji',
        ]);

        // Di luar periode
        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 800_000,
            'transaction_date' => '2025-12-30',
            'note' => 'tahun lalu',
        ]);
    }

    public function test_by_category_mengagregasi_dan_meranking_sesuai_sum_manual(): void
    {
        $result = (new ExpenseBreakdownReport())
            ->byCategory(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $manual = Transaction::query()
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->whereBetween('transaction_date', ['2026-01-01 00:00:00', '2026-01-31 23:59:59'])
            ->get()
            ->groupBy('category_id');

        $this->assertCount(2, $result);
        $this->assertSame($this->food->id, $result[0]['id']);
        $this->assertSame(2_200_000.0, $result[0]['amount']);
        $this->assertSame(81.5, $result[0]['percentage']);
        $this->assertSame(500_000.0, $result[1]['amount']);
        $this->assertSame((float) $manual[$this->food->id]->sum('amount'), $result[0]['amount']);
    }

    public function test_by_category_limit_dan_lainnya(): void
    {
        $result = (new ExpenseBreakdownReport())
            ->byCategory(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), 1);

        $this->assertCount(2, $result);
        $this->assertSame('Makanan', $result[0]['name']);
        $this->assertSame('Lainnya', $result[1]['name']);
        $this->assertNull($result[1]['id']);
        $this->assertSame(500_000.0, $result[1]['amount']);
        $this->assertSame(18.5, $result[1]['percentage']);
    }

    public function test_by_category_periode_kosong_mengembalikan_koleksi_kosong(): void
    {
        $result = (new ExpenseBreakdownReport())
            ->byCategory(Carbon::parse('2025-06-01'), Carbon::parse('2025-06-30'));

        $this->assertTrue($result->isEmpty());
    }

    public function test_category_transactions_menyaring_kategori_tertentu(): void
    {
        $result = (new ExpenseBreakdownReport())
            ->categoryTransactions($this->food->id, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn ($t) => $t->category_id === $this->food->id));
        $this->assertTrue($result->every(fn ($t) => $t->type === 'expense'));
        $this->assertSame(1_000_000, (int) $result->max('amount'));
    }

    public function test_category_transactions_tanpa_kategori_mengambil_semua_pengeluaran(): void
    {
        $result = (new ExpenseBreakdownReport())
            ->categoryTransactions(null, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertCount(5, $result);
    }
}