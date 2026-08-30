<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Liability extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'bank_loan',
        'mortgage',
        'vehicle_loan',
        'credit_card',
        'paylater',
        'other',
    ];

    public const CATEGORY_LABELS = [
        'bank_loan' => 'Pinjaman Bank',
        'mortgage' => 'KPR',
        'vehicle_loan' => 'Kredit Kendaraan',
        'credit_card' => 'Kartu Kredit',
        'paylater' => 'Paylater',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'name',
        'category',
        'principal_remaining',
        'monthly_installment',
        'interest_rate',
        'due_date',
        'linked_account_id',
    ];

    protected function casts(): array
    {
        return [
            'principal_remaining' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function linkedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'linked_account_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
