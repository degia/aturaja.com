<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Support\BankLogos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const TYPES = ['cash', 'bank', 'ewallet', 'saving', 'credit_card'];

    public const TYPE_LABELS = [
        'cash' => 'Tunai',
        'bank' => 'Bank',
        'ewallet' => 'E-Wallet',
        'saving' => 'Tabungan',
        'credit_card' => 'Kartu Kredit',
    ];

    /**
     * Tipe akun yang masuk perhitungan "Saldo Aktif" (saldo kas yang likuid).
     * Akun Tabungan dan Kartu Kredit tidak termasuk.
     */
    public const ACTIVE_BALANCE_TYPES = ['cash', 'bank', 'ewallet'];

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

    public function hasUploadedIcon(): bool
    {
        return BankLogos::isUploaded($this->icon);
    }

    public function getLogoBadgeAttribute(): ?array
    {
        return BankLogos::find($this->icon);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->hasUploadedIcon()) {
            return null;
        }

        return route('uploads.account-logo', $this);
    }
}
