<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    use BelongsToWorkspace, HasFactory;

    /**
     * Penanda ketika transaksi sedang disinkronkan dari sisi pembayaran,
     * dipakai untuk mencegah rekursi pembaruan dua arah (payment <-> transaction).
     */
    public static bool $syncingTransaction = false;

    protected $fillable = [
        'debt_id',
        'account_id',
        'transaction_id',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    protected static function booted(): void
    {
        static::saved(function (DebtPayment $payment) {
            if (! self::$syncingTransaction) {
                $payment->syncTransaction();
            }

            $payment->debt?->recalculateRemaining();
        });

        static::deleted(function (DebtPayment $payment) {
            $payment->debt?->recalculateRemaining();

            $transaction = $payment->transaction;

            if ($transaction && ! $transaction->trashed()) {
                self::$syncingTransaction = true;
                $transaction->delete();
                self::$syncingTransaction = false;
            }
        });
    }

    /**
     * Buat atau perbarui transaksi cermin untuk pembayaran ini.
     *
     * Utang (payable) menghasilkan transaksi expense, piutang (receivable)
     * menghasilkan transaksi income. Saldo akun dirawat oleh TransactionObserver.
     */
    public function syncTransaction(): void
    {
        $debt = $this->debt;

        if (! $debt || ! $this->account_id) {
            return;
        }

        $data = [
            'type' => $debt->direction === 'payable' ? 'expense' : 'income',
            'amount' => $this->amount,
            'transaction_date' => $this->paid_at,
            'account_id' => $this->account_id,
            'category_id' => self::categoryIdFor($debt),
            'note' => $this->note,
        ];

        if (! $this->transaction_id) {
            self::$syncingTransaction = true;
            $transaction = Transaction::create($data);
            self::$syncingTransaction = false;

            $this->transaction_id = $transaction->id;
            $this->saveQuietly();
        } else {
            $transaction = Transaction::find($this->transaction_id);

            if (! $transaction) {
                return;
            }

            self::$syncingTransaction = true;
            $transaction->update($data);
            self::$syncingTransaction = false;
        }
    }

    public static function categoryIdFor(Debt $debt): int
    {
        $type = $debt->direction === 'payable' ? 'expense' : 'income';
        $name = $debt->direction === 'payable' ? 'Pembayaran Utang' : 'Penerimaan Piutang';

        $category = Category::query()
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if (! $category) {
            $category = Category::create([
                'name' => $name,
                'type' => $type,
                'is_default' => true,
            ]);
        }

        return $category->id;
    }
}
