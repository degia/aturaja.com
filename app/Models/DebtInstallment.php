<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtInstallment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'partial', 'paid'];

    public const STATUS_LABELS = [
        'pending' => 'Belum dibayar',
        'partial' => 'Sebagian',
        'paid' => 'Lunas',
    ];

    protected $fillable = [
        'debt_id',
        'installment_number',
        'due_date',
        'amount',
        'amount_paid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
