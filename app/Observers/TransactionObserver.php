<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Transaction;

/**
 * Menjaga saldo akun sinkron dengan setiap perubahan transaksi.
 *
 * Catatan: untuk akun kartu kredit, saldo yang disimpan adalah JUMLAH UTANG
 * (positif = utang yang harus dibayar). Karena itu arah perubahannya terbalik
 * dari akun biasa: belanja menaikkan saldo, pembayaran menurunkannya.
 */
class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->applyEffects($transaction);
    }

    public function updating(Transaction $transaction): void
    {
        $this->revertEffectsOn(
            $transaction->getOriginal('account_id'),
            $transaction->getOriginal('transfer_to_account_id'),
            $transaction->getOriginal('type'),
            $transaction->getOriginal('amount'),
        );

        $this->applyEffects($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        if ($transaction->isForceDeleting()) {
            return;
        }

        $this->applyEffects($transaction, revert: true);
    }

    public function restored(Transaction $transaction): void
    {
        $this->applyEffects($transaction);
    }

    public function forceDeleted(Transaction $transaction): void
    {
        $this->applyEffects($transaction, revert: true);
    }

    private function applyEffects(Transaction $transaction, bool $revert = false): void
    {
        $sign = $revert ? -1 : 1;

        $sourceChange = $this->effectSign($transaction->type, true, $this->resolveAccount($transaction->account_id))
            * (float) $transaction->amount * $sign;

        $this->delta($transaction->account_id, (string) $sourceChange);

        if ($transaction->type === 'transfer' && $transaction->transfer_to_account_id) {
            $targetChange = $this->effectSign(
                $transaction->type,
                false,
                $this->resolveAccount($transaction->transfer_to_account_id),
            ) * (float) $transaction->amount * $sign;

            $this->delta($transaction->transfer_to_account_id, (string) $targetChange);
        }
    }

    private function revertEffectsOn(?int $accountId, ?int $transferToAccountId, ?string $type, ?string $amount): void
    {
        if (! $type || ! $amount) {
            return;
        }

        $sourceChange = $this->effectSign($type, true, $this->resolveAccount($accountId))
            * (float) $amount;

        $this->delta($accountId, (string) -$sourceChange);

        if ($type === 'transfer' && $transferToAccountId) {
            $targetChange = $this->effectSign($type, false, $this->resolveAccount($transferToAccountId))
                * (float) $amount;

            $this->delta($transferToAccountId, (string) -$targetChange);
        }
    }

    /**
     * Arah perubahan saldo pada sebuah akun.
     *
     * Akun biasa: income menaikkan, expense/transfer keluar menurunkan.
     * Kartu kredit (saldo = utang): belanja/transfer keluar menaikkan utang,
     * pembayaran masuk (income/transfer masuk) menurunkannya.
     */
    private function effectSign(string $type, bool $isSource, ?Account $account): int
    {
        $isCreditCard = $account?->type === 'credit_card';

        if ($type === 'income') {
            return $isCreditCard ? -1 : 1;
        }

        if ($type === 'expense') {
            return $isCreditCard ? 1 : -1;
        }

        // transfer
        if ($isCreditCard) {
            return $isSource ? 1 : -1;
        }

        return $isSource ? -1 : 1;
    }

    private function resolveAccount(?int $accountId): ?Account
    {
        return $accountId ? Account::withTrashed()->find($accountId) : null;
    }

    private function delta(?int $accountId, string $amount): void
    {
        if (! $accountId || ! (float) $amount) {
            return;
        }

        $account = Account::withTrashed()->find($accountId);

        if (! $account) {
            return;
        }

        if (str_starts_with($amount, '-')) {
            $account->decrement('balance', ltrim($amount, '-'));
        } else {
            $account->increment('balance', $amount);
        }
    }
}