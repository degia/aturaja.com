<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $this->user->id,
            'currency' => 'IDR',
        ]);
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'owner']);

        session(['current_workspace_id' => $this->workspace->id]);
    }

    private function makeAccount(string $type = 'cash'): Account
    {
        return Account::create([
            'name' => 'Akun '.$type,
            'type' => $type,
            'balance' => 0,
        ]);
    }

    private function makeCategory(string $type = 'expense'): Category
    {
        return Category::create([
            'name' => 'Kategori '.$type,
            'type' => $type,
        ]);
    }

    public function test_income_menambah_saldo_akun(): void
    {
        $account = $this->makeAccount();

        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory('income')->id,
            'type' => 'income',
            'amount' => 100_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('100000.00', (string) $account->fresh()->balance);
    }

    public function test_expense_mengurangi_saldo_akun(): void
    {
        $account = $this->makeAccount();

        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory('expense')->id,
            'type' => 'expense',
            'amount' => 75_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('-75000.00', (string) $account->fresh()->balance);
    }

    public function test_transfer_memindahkan_saldo_antar_akun(): void
    {
        $from = $this->makeAccount('bank');
        $to = $this->makeAccount('cash');

        Transaction::create([
            'account_id' => $from->id,
            'transfer_to_account_id' => $to->id,
            'type' => 'transfer',
            'amount' => 250_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('-250000.00', (string) $from->fresh()->balance);
        $this->assertSame('250000.00', (string) $to->fresh()->balance);
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(0, Transaction::expense()->count());
    }

    public function test_menghapus_transaksi_mengembalikan_saldo_akun(): void
    {
        $account = $this->makeAccount();
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory('income')->id,
            'type' => 'income',
            'amount' => 500_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('500000.00', (string) $account->fresh()->balance);

        $transaction->delete();

        $this->assertSame('0.00', (string) $account->fresh()->balance);
    }

    public function test_transaksi_dipulihkan_kembali_mengembalikan_efek_saldo(): void
    {
        $account = $this->makeAccount();
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory('expense')->id,
            'type' => 'expense',
            'amount' => 30_000,
            'transaction_date' => today(),
        ]);

        $transaction->delete();
        $this->assertSame('0.00', (string) $account->fresh()->balance);

        $transaction->restore();
        $this->assertSame('-30000.00', (string) $account->fresh()->balance);
    }

    public function test_mengubah_nominal_memperbarui_saldo_akun(): void
    {
        $account = $this->makeAccount();
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $this->makeCategory('expense')->id,
            'type' => 'expense',
            'amount' => 50_000,
            'transaction_date' => today(),
        ]);

        $transaction->update(['amount' => 80_000]);

        $this->assertSame('-80000.00', (string) $account->fresh()->balance);
    }

    public function test_mengubah_akun_transaksi_memindahkan_efek_ke_akun_baru(): void
    {
        $first = $this->makeAccount('bank');
        $second = $this->makeAccount('cash');

        $transaction = Transaction::create([
            'account_id' => $first->id,
            'category_id' => $this->makeCategory('expense')->id,
            'type' => 'expense',
            'amount' => 90_000,
            'transaction_date' => today(),
        ]);

        $transaction->update(['account_id' => $second->id]);

        $this->assertSame('0.00', (string) $first->fresh()->balance);
        $this->assertSame('-90000.00', (string) $second->fresh()->balance);
    }

    public function test_kartu_kredit_expense_menambah_utang(): void
    {
        $card = $this->makeAccount('credit_card');

        Transaction::create([
            'account_id' => $card->id,
            'category_id' => $this->makeCategory('expense')->id,
            'type' => 'expense',
            'amount' => 300_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('300000.00', (string) $card->fresh()->balance);
    }

    public function test_transfer_ke_kartu_kredit_mengurangi_utang(): void
    {
        $bank = $this->makeAccount('bank');
        $card = $this->makeAccount('credit_card');

        Transaction::create([
            'account_id' => $bank->id,
            'transfer_to_account_id' => $card->id,
            'type' => 'transfer',
            'amount' => 150_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('-150000.00', (string) $bank->fresh()->balance);
        $this->assertSame('-150000.00', (string) $card->fresh()->balance);
    }

    public function test_transfer_dari_kartu_kredit_menambah_utang_dan_saldo_tunai(): void
    {
        $card = $this->makeAccount('credit_card');
        $cash = $this->makeAccount('cash');

        Transaction::create([
            'account_id' => $card->id,
            'transfer_to_account_id' => $cash->id,
            'type' => 'transfer',
            'amount' => 50_000,
            'transaction_date' => today(),
        ]);

        $this->assertSame('50000.00', (string) $card->fresh()->balance);
        $this->assertSame('50000.00', (string) $cash->fresh()->balance);
    }

    public function test_transaksi_workspace_lain_tidak_menyentuh_workspace_aktif(): void
    {
        $otherWorkspace = Workspace::create([
            'name' => 'Workspace Lain',
            'owner_user_id' => $this->user->id,
            'currency' => 'IDR',
        ]);

        session(['current_workspace_id' => $otherWorkspace->id]);
        $otherAccount = Account::create(['name' => 'Akun lain', 'type' => 'cash', 'balance' => 0]);
        Transaction::create([
            'account_id' => $otherAccount->id,
            'category_id' => $this->makeCategory('income')->id,
            'type' => 'income',
            'amount' => 900_000,
            'transaction_date' => today(),
        ]);

        session(['current_workspace_id' => $this->workspace->id]);

        $this->assertSame(
            '900000.00',
            (string) Account::withoutGlobalScopes()->findOrFail($otherAccount->id)->balance,
        );

        $mine = $this->makeAccount();
        $this->assertSame('0.00', (string) $mine->fresh()->balance);
        $this->assertSame(0, Transaction::query()->count());
    }
}