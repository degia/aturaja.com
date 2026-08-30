<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'period_month',
        'category_id',
        'limit_amount',
        'alert_threshold_percent',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'limit_amount' => 'decimal:2',
            'alert_threshold_percent' => 'integer',
        ];
    }

    public function scopeForPeriod(Builder $query, string $periodMonth): Builder
    {
        return $query->where('period_month', $periodMonth);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
