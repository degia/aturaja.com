<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetWorthSnapshot extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'snapshot_date',
        'total_assets',
        'total_liabilities',
        'net_worth',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
        ];
    }
}
