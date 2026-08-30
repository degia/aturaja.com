<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'cash_bank',
        'investment_stock',
        'investment_mutual_fund',
        'investment_gold',
        'investment_crypto',
        'real_estate',
        'vehicle',
        'other',
    ];

    public const CATEGORY_LABELS = [
        'cash_bank' => 'Kas & Bank',
        'investment_stock' => 'Investasi Saham',
        'investment_mutual_fund' => 'Investasi Reksa Dana',
        'investment_gold' => 'Investasi Emas',
        'investment_crypto' => 'Investasi Kripto',
        'real_estate' => 'Properti',
        'vehicle' => 'Kendaraan',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'name',
        'category',
        'current_value',
        'linked_account_id',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'last_updated_at' => 'date',
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
