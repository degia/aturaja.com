<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use BelongsToWorkspace, HasFactory;

    public const TYPES = ['pdf', 'excel', 'csv'];

    public const REPORTS = [
        'cash_flow' => 'Arus Kas',
        'net_worth' => 'Net Worth',
        'budget_vs_actual' => 'Budget vs Actual',
        'expense_breakdown' => 'Perincian Pengeluaran',
        'transactions' => 'Daftar Transaksi',
    ];

    public const STATUSES = ['queued', 'processing', 'done', 'failed'];

    protected $fillable = [
        'user_id',
        'type',
        'report',
        'period_start',
        'period_end',
        'status',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReportLabelAttribute(): string
    {
        return self::REPORTS[$this->report] ?? $this->report;
    }
}
