<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->user = $user;
        $this->workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $user->id,
            'currency' => 'IDR',
        ]);
        $user->workspaces()->attach($this->workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $this->workspace->id]);

        $this->account = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
    }

    private function category(string $name, string $type): Category
    {
        return Category::create(['name' => $name, 'type' => $type]);
    }

    private function txn(string $type, float $amount, string $date, ?Category $category = null): void
    {
        Transaction::create([
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
            'account_id' => $this->account->id,
            'category_id' => $category?->id,
        ]);
    }

    public function test_halaman_reports_menampilkan_totals_dan_kategori(): void
    {
        $income = $this->category('Gaji', 'income');
        $food = $this->category('Makanan', 'expense');

        $this->txn('income', 10_000_000, '2026-08-10', $income);
        $this->txn('expense', 2_500_000, '2026-08-12', $food);
        $this->txn('expense', 1_500_000, '2026-08-20', $food);

        $this->actingAs($this->user)->get('/reports?month=2026-08')
            ->assertStatus(200)
            ->assertSee('Pemasukan')
            ->assertSee('Pengeluaran')
            ->assertSee('Net Cash Flow')
            ->assertSee('Makanan')
            ->assertSee('Rp 10,000,000')
            ->assertSee('Rp 4,000,000');
    }

    public function test_halaman_reports_bulan_lain_menampilkan_nol(): void
    {
        $this->actingAs($this->user)->get('/reports?month=2026-01')
            ->assertStatus(200)
            ->assertSee('Belum ada transaksi bulan ini.');
    }

    public function test_halaman_reports_tanpa_transaksi_membuka_halaman(): void
    {
        $this->actingAs($this->user)->get('/reports')
            ->assertStatus(200)
            ->assertSee('Reports');
    }
}
