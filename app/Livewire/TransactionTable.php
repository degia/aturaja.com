<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionTable extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $accountFilter = null;

    public ?int $categoryFilter = null;

    public ?int $tagFilter = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'accountFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'tagFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAccountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTagFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Transaction::findOrFail($id)->delete();

        $this->dispatch('transaction-saved');
    }

    #[On('transaction-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.transaction-table', [
            'transactions' => Transaction::with(['account', 'category', 'transferToAccount', 'tags'])
                ->when($this->search !== '', function ($query) {
                    $search = '%'.$this->search.'%';

                    $query->where(function ($query) use ($search) {
                        $query->where('note', 'like', $search)
                            ->orWhereHas('account', fn ($q) => $q->where('name', 'like', $search))
                            ->orWhereHas('category', fn ($q) => $q->where('name', 'like', $search))
                            ->orWhereHas('tags', fn ($q) => $q->where('name', 'like', $search));
                    });
                })
                ->when($this->accountFilter, fn ($query) => $query->where('account_id', $this->accountFilter))
                ->when($this->categoryFilter, fn ($query) => $query->where('category_id', $this->categoryFilter))
                ->when($this->tagFilter, fn ($query) => $query->whereHas('tags', fn ($q) => $q->whereKey($this->tagFilter)))
                ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('transaction_date', '>=', $this->dateFrom))
                ->when($this->dateTo !== '', fn ($query) => $query->whereDate('transaction_date', '<=', $this->dateTo))
                ->latest('transaction_date')
                ->latest('id')
                ->paginate(15),
            'accounts' => Account::active()->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }
}