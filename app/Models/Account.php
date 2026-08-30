<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const TYPES = ['cash', 'bank', 'ewallet', 'credit_card'];

    public const TYPE_LABELS = [
        'cash' => 'Tunai',
        'bank' => 'Bank',
        'ewallet' => 'E-Wallet',
        'credit_card' => 'Kartu Kredit',
    ];

    protected $fillable = [
        'name',
        'type',
        'balance',
        'credit_limit',
        'billing_date',
        'due_date',
        'is_archived',
        'is_emergency_fund',
        'icon',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'billing_date' => 'integer',
            'due_date' => 'integer',
            'is_archived' => 'boolean',
            'is_emergency_fund' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}