<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'installments_count',
        'first_due_date',
        'installment_amount',
        'default_account_id',
        'due_date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'installments_count' => 'integer',
            'installment_amount' => 'decimal:2',
            'first_due_date' => 'date',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Debt $debt) {
            if ($debt->isForceDeleting()) {
                return;
            }

            // Batalkan seluruh pembayaran (yang otomatis membatalkan transaksi
            // terkait dan mengembalikan saldo akun), lalu bersihkan jadwal cicilan.
            foreach ($debt->payments()->get() as $payment) {
                $payment->delete();
            }

            $debt->installments()->delete();
        });
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class)->orderBy('installment_number');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function getProgressPercentAttribute(): int
    {
        $principal = (float) $this->principal_amount;

        if ($principal <= 0) {
            return 0;
        }

        return (int) round($this->total_paid / $principal * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->direction !== 'payable' || $this->status === 'paid') {
            return false;
        }

        if ($this->due_date && $this->due_date->isBefore(today())) {
            return true;
        }

        $next = $this->installments()
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->first();

        return $next !== null && Carbon::parse($next->due_date)->isBefore(today());
    }

    /**
     * Hitung ulang sisa utang/piutang secara sistematis dari seluruh catatan
     * pembayaran, lalu sinkronkan status dan alokasi ke jadwal cicilan.
     */
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

        $this->update([
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);

        $this->syncInstallmentsFromPayments();
    }

    /**
     * Buat ulang jadwal cicilan bulanan sesuai tenor (installments_count).
     *
     * Tiap cicilan membagi pokok secara merata; cicilan terakhir menampung sisa
     * pembulatan agar total selalu sama dengan pokok.
     */
    public function generateSchedule(): void
    {
        $count = (int) ($this->installments_count ?? 0);
        $principal = (float) $this->principal_amount;

        $this->installments()->delete();

        if ($count < 1) {
            $this->installment_amount = null;
            $this->saveQuietly();

            return;
        }

        $monthly = round($principal / $count, 2);

        $start = $this->first_due_date
            ? Carbon::parse($this->first_due_date)
            : ($this->due_date ? Carbon::parse($this->due_date) : today());

        for ($i = 1; $i <= $count; $i++) {
            $amount = $i < $count
                ? $monthly
                : round($principal - $monthly * ($count - 1), 2);

            $this->installments()->create([
                'installment_number' => $i,
                'due_date' => $start->copy()->addMonths($i - 1)->format('Y-m-d'),
                'amount' => $amount,
                'amount_paid' => 0,
                'status' => 'pending',
            ]);
        }

        $this->installment_amount = $monthly;
        $this->first_due_date = $this->first_due_date ?? $start->format('Y-m-d');
        $this->saveQuietly();

        $this->syncInstallmentsFromPayments();
    }

    /**
     * Alokasikan seluruh pembayaran ke cicilan secara berurutan (cicilan paling
     * awal dan jatuh tempo paling dulu dipenuhi), lalu perbarui status tiap cicilan.
     */
    public function syncInstallmentsFromPayments(): void
    {
        $installments = $this->installments()->get();

        if ($installments->isEmpty()) {
            return;
        }

        foreach ($installments as $installment) {
            $installment->amount_paid = 0;
        }

        $cursor = 0;

        foreach ($this->payments()->orderBy('paid_at')->orderBy('id')->get() as $payment) {
            $remaining = (float) $payment->amount;

            while ($remaining > 0 && $cursor < $installments->count()) {
                $installment = $installments[$cursor];
                $due = (float) $installment->amount;
                $applied = min($remaining, $due);

                $installment->amount_paid = (float) $installment->amount_paid + $applied;
                $remaining -= $applied;

                if ((float) $installment->amount_paid >= $due) {
                    $cursor++;
                }
            }

            if ($cursor >= $installments->count()) {
                break;
            }
        }

        foreach ($installments as $installment) {
            $paid = round((float) $installment->amount_paid, 2);
            $due = round((float) $installment->amount, 2);

            if ($paid <= 0) {
                $status = 'pending';
            } elseif ($paid >= $due) {
                $status = 'paid';
                $paid = $due;
            } else {
                $status = 'partial';
            }

            $installment->status = $status;
            $installment->amount_paid = $paid;
            $installment->saveQuietly();
        }
    }
}
