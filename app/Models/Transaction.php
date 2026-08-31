<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const TYPES = ['income', 'expense', 'transfer'];

    public const TYPE_LABELS = [
        'income' => 'Pemasukan',
        'expense' => 'Pengeluaran',
        'transfer' => 'Transfer',
    ];

    protected $fillable = [
        'account_id',
        'category_id',
        'transfer_to_account_id',
        'type',
        'amount',
        'transaction_date',
        'note',
        'attachment_path',
        'is_reconciled',
        'recurring_rule_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'is_reconciled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::observe(TransactionObserver::class);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_to_account_id');
    }

    public function recurringRule(): BelongsTo
    {
        return $this->belongsTo(RecurringRule::class);
    }

    public function debtPayment(): HasOne
    {
        return $this->hasOne(DebtPayment::class, 'transaction_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'transaction_tag');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
