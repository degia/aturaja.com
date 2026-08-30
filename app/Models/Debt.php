<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const DIRECTIONS = ['payable', 'receivable'];

    public const DIRECTION_LABELS = [
        'payable' => 'Utang (saya bayar)',
        'receivable' => 'Piutang (dibayar ke saya)',
    ];

    public const STATUSES = ['ongoing', 'partially_paid', 'paid'];

    public const STATUS_LABELS = [
        'ongoing' => 'Berjalan',
        'partially_paid' => 'Sebagian Terbayar',
        'paid' => 'Lunas',
    ];

    protected $fillable = [
        'direction',
        'counterparty_name',
        'principal_amount',
        'remaining_amount',
        'due_date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->direction === 'payable'
            && $this->due_date
            && $this->status !== 'paid'
            && $this->due_date->isBefore(today());
    }

    public function recalculateRemaining(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $remaining = round((float) $this->principal_amount - $paid, 2);

        $status = 'ongoing';
        if ($remaining <= 0) {
            $remaining = 0;
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        }

        $this->update(['remaining_amount' => $remaining, 'status' => $status]);
    }
}
