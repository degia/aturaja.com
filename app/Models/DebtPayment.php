<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'amount',
        'paid_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    protected static function booted(): void
    {
        static::saved(function (DebtPayment $payment) {
            $payment->debt?->recalculateRemaining();
        });

        static::deleted(function (DebtPayment $payment) {
            $payment->debt?->recalculateRemaining();
        });
    }
}
