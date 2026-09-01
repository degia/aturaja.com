<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Tag;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

class TransactionForm extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $type = 'expense';

    public ?int $account_id = null;

    public ?int $transfer_to_account_id = null;

    public ?int $category_id = null;

    public ?int $debt_id = null;

    public string $amount = '';

    public string $transfer_fee = '';

    public ?int $transfer_fee_category_id = null;

    public string $transaction_date = '';

    public ?string $note = null;

    public array $tag_ids = [];

    public function mount(): void
    {
        $this->transaction_date = today()->format('Y-m-d');
    }

    #[On('open-transaction-form')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    #[On('edit-transaction')]
    public function openEdit(mixed $id = null): void
    {
        // Browser mengirim payload sebagai arg bernama (id: X),
        // sedangkan test framework mengirim array terbungkus.
        $id = is_array($id) ? ($id['id'] ?? null) : $id;

        $transaction = Transaction::find($id);

        if (! $transaction) {
            return;
        }

        $this->resetForm();
        $this->editingId = $transaction->id;
        $this->type = $transaction->type;
        $this->account_id = $transaction->account_id;
        $this->transfer_to_account_id = $transaction->transfer_to_account_id;
        $this->category_id = $transaction->category_id;
        $this->amount = (string) $transaction->amount;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
        $this->note = $transaction->note;
        $this->tag_ids = $transaction->tags()->pluck('tags.id')->map(fn (int $id): int => $id)->all();
        $this->debt_id = $transaction->debtPayment?->debt_id;
        $this->showForm = true;
    }

    public function close(): void
    {
        $this->showForm = false;
    }

    public function updatedType(string $value): void
    {
        if ($value === 'transfer') {
            $this->category_id = null;
            $this->debt_id = null;
        } else {
            $this->transfer_to_account_id = null;
        }
    }

    public function save(): void
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'debt_id' => ['nullable', 'integer', 'exists:debts,id'],
        ];

        if ($this->type === 'transfer') {
            $rules['transfer_to_account_id'] = ['required', 'integer', 'different:account_id'];
            $rules['transfer_fee'] = ['nullable', 'numeric', 'min:0'];

            if ((float) ($this->transfer_fee ?? 0) > 0) {
                $rules['transfer_fee_category_id'] = ['required', 'integer', 'exists:categories,id'];
            }
        } else {
            $rules['category_id'] = ['required', 'integer'];
        }

        $validated = $this->validate($rules);

        $data = [
            'type' => $this->type,
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'account_id' => $this->account_id,
            'note' => $validated['note'] ?? null,
        ];

        if ($this->type === 'transfer') {
            $data['transfer_to_account_id'] = $this->transfer_to_account_id;
            $data['category_id'] = null;
        } else {
            $data['category_id'] = $this->category_id;
            $data['transfer_to_account_id'] = null;
        }

        $transaction = $this->editingId
            ? Transaction::findOrFail($this->editingId)
            : new Transaction;

        $transaction->fill($data);
        $transaction->save();
        $transaction->tags()->sync($this->tag_ids);

        $this->syncDebtLink($transaction);
        $this->syncTransferFee($transaction);

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('transaction-saved');
    }

    /**
     * Untuk transfer (antar bank) yang baru dibuat, catat biaya admin sebagai
     * transaksi expense terpisah (pengurang dari akun sumber) apabila nominal
     * potongan diisi. Tidak dipicu saat mengubah transaksi agar tidak dobel.
     */
    private function syncTransferFee(Transaction $transaction): void
    {
        if ($this->type !== 'transfer' || $this->editingId) {
            return;
        }

        $fee = (float) ($this->transfer_fee ?? 0);

        if ($fee <= 0) {
            return;
        }

        Transaction::create([
            'type' => 'expense',
            'amount' => $fee,
            'transaction_date' => $transaction->transaction_date,
            'account_id' => $transaction->account_id,
            'category_id' => $this->transfer_fee_category_id,
            'note' => $transaction->note
                ? $transaction->note.' · Biaya admin transfer'
                : 'Biaya admin transfer',
        ]);
    }

    private function syncDebtLink(Transaction $transaction): void
    {
        $debt = $this->debt_id ? Debt::find($this->debt_id) : null;

        $canLink = $debt
            && $this->type !== 'transfer'
            && $debt->status !== 'paid'
            && (
                ($this->type === 'expense' && $debt->direction === 'payable')
                || ($this->type === 'income' && $debt->direction === 'receivable')
            );

        $payment = $transaction->debtPayment;

        if ($canLink) {
            if (! $payment) {
                DebtPayment::create([
                    'debt_id' => $debt->id,
                    'transaction_id' => $transaction->id,
                    'account_id' => $transaction->account_id,
                    'amount' => $transaction->amount,
                    'paid_at' => $transaction->transaction_date,
                    'note' => $transaction->note,
                ]);
            } elseif ($payment->debt_id !== $debt->id) {
                $previousDebt = $payment->debt;
                $payment->debt_id = $debt->id;
                $payment->save();
                $previousDebt?->recalculateRemaining();
            }
        } elseif ($payment) {
            $payment->delete();
        }
    }

    /**
     * Kategori yang valid untuk biaya admin transfer: hanya kategori "Biaya
     * Administrasi" beserta seluruh sub-kategorinya (sedalam apapun).
     */
    private function transferFeeCategories(): \Illuminate\Support\Collection
    {
        $roots = Category::query()
            ->active()
            ->where('name', 'Biaya Administrasi')
            ->get();

        if ($roots->isEmpty()) {
            return collect();
        }

        $result = $roots->keyBy('id');
        $pending = $roots->pluck('id');

        while ($pending->isNotEmpty()) {
            $children = Category::query()
                ->active()
                ->whereIn('parent_id', $pending)
                ->get();

            foreach ($children as $child) {
                $result->put($child->id, $child);
            }

            $pending = $children->pluck('id');
        }

        return $result->sortBy('name')->values();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = 'expense';
        $this->account_id = null;
        $this->transfer_to_account_id = null;
        $this->category_id = null;
        $this->amount = '';
        $this->transfer_fee = '';
        $this->transfer_fee_category_id = null;
        $this->transaction_date = today()->format('Y-m-d');
        $this->note = null;
        $this->tag_ids = [];
        $this->debt_id = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.transaction-form', [
            'accounts' => Account::active()->orderBy('name')->get(),
            'incomeCategories' => Category::income()->active()->orderBy('name')->get(),
            'expenseCategories' => Category::expense()->active()->orderBy('name')->get(),
            'transferFeeCategories' => $this->transferFeeCategories(),
            'tags' => Tag::orderBy('name')->get(),
            'debts' => Debt::query()->where('status', '!=', 'paid')->orderBy('counterparty_name')->get(),
        ]);
    }
}
