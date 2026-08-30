<?php

namespace Tests\Feature;

use App\Livewire\TransactionTable;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionTableTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    private Category $food;

    private Category $salary;

    private Tag $important;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $user->id,
            'currency' => 'IDR',
        ]);
        $user->workspaces()->attach($workspace->id, ['role' => 'owner']);

        session(['current_workspace_id' => $workspace->id]);

        $this->account = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        $this->food = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $this->salary = Category::create(['name' => 'Gaji', 'type' => 'income']);
        $this->important = Tag::create(['name' => 'penting']);
    }

    private function makeTransaction(string $note, Category $category, int $amount, ?Tag $tag = null): Transaction
    {
        $transaction = Transaction::create([
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => $category->type === 'income' ? 'income' : 'expense',
            'amount' => $amount,
            'transaction_date' => today(),
            'note' => $note,
        ]);

        if ($tag) {
            $transaction->tags()->attach($tag);
        }

        return $transaction;
    }

    public function test_table_menampilkan_transaksi_dan_pencarian_filter(): void
    {
        $this->makeTransaction('Belanja mingguan alfa', $this->food, 120_000);
        $this->makeTransaction('Gaji bulanan beta', $this->salary, 7_000_000);

        Livewire::test(TransactionTable::class)
            ->assertSee('Belanja mingguan alfa')
            ->assertSee('Gaji bulanan beta')
            ->set('search', 'Gaji bulanan')
            ->assertSee('Gaji bulanan beta')
            ->assertDontSee('Belanja mingguan alfa');
    }

    public function test_filter_kategori_membatasi_baris(): void
    {
        $this->makeTransaction('Beli nasi padang', $this->food, 40_000);
        $this->makeTransaction('Bonus tahunan', $this->salary, 5_000_000);

        Livewire::test(TransactionTable::class)
            ->set('categoryFilter', $this->food->id)
            ->assertSee('Beli nasi padang')
            ->assertDontSee('Bonus tahunan');
    }

    public function test_filter_akun_membatasi_baris(): void
    {
        $other = Account::create(['name' => 'Bank', 'type' => 'bank', 'balance' => 0]);
        $this->makeTransaction('Beli di dompet', $this->food, 10_000);

        Transaction::create([
            'account_id' => $other->id,
            'category_id' => $this->salary->id,
            'type' => 'income',
            'amount' => 9_000_000,
            'transaction_date' => today(),
            'note' => 'Gaji masuk bank',
        ]);

        Livewire::test(TransactionTable::class)
            ->set('accountFilter', $this->account->id)
            ->assertSee('Beli di dompet')
            ->assertDontSee('Gaji masuk bank');
    }

    public function test_filter_tag_membatasi_baris(): void
    {
        $routine = Tag::create(['name' => 'rutin']);
        $this->makeTransaction('Belanja baju payung', $this->food, 200_000, $this->important);
        $this->makeTransaction('Makan siang rutin', $this->food, 25_000, $routine);

        Livewire::test(TransactionTable::class)
            ->set('tagFilter', $this->important->id)
            ->assertSee('Belanja baju payung')
            ->assertDontSee('Makan siang rutin');
    }

    public function test_filter_rentang_tanggal_membatasi_baris(): void
    {
        $yesterday = Transaction::create([
            'account_id' => $this->account->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 15_000,
            'transaction_date' => today()->subDay(),
            'note' => 'Kemarin desa',
        ]);

        Transaction::create([
            'account_id' => $this->account->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 15_000,
            'transaction_date' => today(),
            'note' => 'Hari ini kota',
        ]);

        Livewire::test(TransactionTable::class)
            ->set('dateFrom', today()->format('Y-m-d'))
            ->set('dateTo', today()->format('Y-m-d'))
            ->assertSee('Hari ini kota')
            ->assertDontSee('Kemarin desa');
    }

    public function test_hapus_transaksi_menghapus_baris_dan_mengembalikan_saldo(): void
    {
        $transaction = $this->makeTransaction('Beli kopi', $this->food, 35_000);

        $this->assertSame('-35000.00', (string) $this->account->fresh()->balance);

        Livewire::test(TransactionTable::class)
            ->call('delete', $transaction->id);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
            'deleted_at' => null,
        ]);
        $this->assertSame('0.00', (string) $this->account->fresh()->balance);
    }
}