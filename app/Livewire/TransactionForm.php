<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
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

    public string $amount = '';

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
    public function openEdit(array $payload): void
    {
        $transaction = Transaction::find($payload['id'] ?? null);

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
        ];

        if ($this->type === 'transfer') {
            $rules['transfer_to_account_id'] = ['required', 'integer', 'different:account_id'];
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
            : new Transaction();

        $transaction->fill($data);
        $transaction->save();
        $transaction->tags()->sync($this->tag_ids);

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('transaction-saved');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = 'expense';
        $this->account_id = null;
        $this->transfer_to_account_id = null;
        $this->category_id = null;
        $this->amount = '';
        $this->transaction_date = today()->format('Y-m-d');
        $this->note = null;
        $this->tag_ids = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.transaction-form', [
            'accounts' => Account::active()->orderBy('name')->get(),
            'incomeCategories' => Category::income()->active()->orderBy('name')->get(),
            'expenseCategories' => Category::expense()->active()->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }
}