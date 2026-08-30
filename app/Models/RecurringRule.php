<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringRule extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    public const TYPES = ['income', 'expense', 'transfer'];

    public const FREQUENCIES = ['weekly', 'monthly', 'yearly'];

    public const FREQUENCY_LABELS = [
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ];

    protected $fillable = [
        'account_id',
        'category_id',
        'transfer_to_account_id',
        'type',
        'amount',
        'frequency',
        'interval_count',
        'start_date',
        'end_date',
        'next_run_date',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_date' => 'date',
            'interval_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('next_run_date', '<=', today());
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCY_LABELS[$this->frequency] ?? $this->frequency;
    }

    public function advanceNextRunDate(): void
    {
        $date = $this->next_run_date->copy();

        if ($this->frequency === 'weekly') {
            $date->addWeeks($this->interval_count);
        } elseif ($this->frequency === 'yearly') {
            $date->addYears($this->interval_count);
        } else {
            $date->addMonths($this->interval_count);
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            $this->update(['is_active' => false]);

            return;
        }

        $this->update(['next_run_date' => $date]);
    }
}