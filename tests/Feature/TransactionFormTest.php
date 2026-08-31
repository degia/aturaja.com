<?php

namespace Tests\Feature;

use App\Livewire\TransactionForm;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionFormTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private Account $cash;

    private Account $bank;

    private Category $food;

    private Category $salary;

    private Tag $tag;

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

        $this->cash = Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 100_000]);
        $this->bank = Account::create(['name' => 'Bank', 'type' => 'bank', 'balance' => 0]);
        $this->food = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $this->salary = Category::create(['name' => 'Gaji', 'type' => 'income']);
        $this->tag = Tag::create(['name' => 'penting']);
    }

    public function test_event_open_transaction_form_membuka_modal_dengan_form_kosong(): void
    {
        Livewire::test(TransactionForm::class)
            ->call('save', ['amount' => '']) // placeholder to trigger validation state
            ->call('close')
            ->assertSet('showForm', false)
            ->dispatch('open-transaction-form')
            ->assertSet('showForm', true)
            ->assertSet('editingId', null)
            ->assertSet('amount', '')
            ->assertSet('type', 'expense');
    }

    public function test_event_edit_transaction_mengisi_form(): void
    {
        $transaction = Transaction::create([
            'account_id' => $this->cash->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 50_000,
            'transaction_date' => today(),
            'note' => 'makan siang',
        ]);
        $transaction->tags()->attach($this->tag);

        Livewire::test(TransactionForm::class)
            ->dispatch('edit-transaction', ['id' => $transaction->id])
            ->assertSet('showForm', true)
            ->assertSet('editingId', $transaction->id)
            ->assertSet('amount', '50000.00')
            ->assertSet('category_id', $this->food->id)
            ->assertSet('tag_ids', [$this->tag->id]);
    }

    public function test_simpan_income_membuat_transaksi_dan_menambah_saldo(): void
    {
        Livewire::test(TransactionForm::class)
            ->set('type', 'income')
            ->set('account_id', $this->bank->id)
            ->set('category_id', $this->salary->id)
            ->set('amount', '7500000')
            ->set('transaction_date', today()->format('Y-m-d'))
            ->call('save')
            ->assertDispatched('transaction-saved')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->bank->id,
            'type' => 'income',
            'amount' => 7_500_000,
        ]);
        $this->assertSame(7_500_000, (int) $this->bank->fresh()->balance);
    }

    public function test_simpan_tanpa_akun_dan_nominal_memunculkan_error_validasi(): void
    {
        Livewire::test(TransactionForm::class)
            ->dispatch('open-transaction-form')
            ->assertSet('showForm', true)
            ->set('amount', '')
            ->set('account_id', null)
            ->call('save')
            ->assertHasErrors(['amount' => 'required', 'account_id' => 'required', 'category_id' => 'required'])
            ->assertSet('showForm', true);
    }

    public function test_transfer_memerlukan_akun_tujuan(): void
    {
        Livewire::test(TransactionForm::class)
            ->set('type', 'transfer')
            ->set('account_id', $this->cash->id)
            ->set('amount', '100000')
            ->call('save')
            ->assertHasErrors(['transfer_to_account_id' => 'required']);
    }

    public function test_transfer_tujuan_sama_dengan_akun_sumber_ditolak(): void
    {
        Livewire::test(TransactionForm::class)
            ->set('type', 'transfer')
            ->set('account_id', $this->cash->id)
            ->set('transfer_to_account_id', $this->cash->id)
            ->set('amount', '100000')
            ->call('save')
            ->assertHasErrors(['transfer_to_account_id' => 'different']);
    }

    public function test_transfer_dengan_biaya_admin_mencatat_pengeluaran(): void
    {
        $feeCategory = Category::create(['name' => 'Biaya Admin', 'type' => 'expense']);

        Livewire::test(TransactionForm::class)
            ->set('type', 'transfer')
            ->set('account_id', $this->cash->id)
            ->set('transfer_to_account_id', $this->bank->id)
            ->set('amount', '100000')
            ->set('transfer_fee', '6500')
            ->set('transfer_fee_category_id', $feeCategory->id)
            ->set('transaction_date', today()->format('Y-m-d'))
            ->call('save')
            ->assertDispatched('transaction-saved')
            ->assertSet('showForm', false);

        // Transfer mengurangi akun sumber & menambah akun tujuan
        $this->assertDatabaseHas('transactions', [
            'type' => 'transfer',
            'account_id' => $this->cash->id,
            'transfer_to_account_id' => $this->bank->id,
            'amount' => 100_000,
        ]);

        // Biaya admin tercatat sebagai expense pada akun sumber
        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'account_id' => $this->cash->id,
            'category_id' => $feeCategory->id,
            'amount' => 6_500,
        ]);

        // Saldo akhir: 100.000 - 100.000 (transfer) - 6.500 (biaya admin)
        $this->assertSame(-6_500, (int) $this->cash->fresh()->balance);
        $this->assertSame(100_000, (int) $this->bank->fresh()->balance);
    }

    public function test_transfer_dengan_biaya_admin_memerlukan_kategori(): void
    {
        Livewire::test(TransactionForm::class)
            ->set('type', 'transfer')
            ->set('account_id', $this->cash->id)
            ->set('transfer_to_account_id', $this->bank->id)
            ->set('amount', '100000')
            ->set('transfer_fee', '6500')
            ->set('transaction_date', today()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['transfer_fee_category_id' => 'required']);
    }
}
