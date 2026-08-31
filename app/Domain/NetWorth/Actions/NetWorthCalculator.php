<?php

namespace App\Domain\NetWorth\Actions;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Liability;

final class NetWorthCalculator
{
    /**
     * Kalkulasi total aset, kewajiban, dan net worth secara live.
     *
     * Aset = jumlah nilai aset manual + saldo akun likuid (cash/bank/ewallet).
     * Aset kategori cash_bank yang ter-link ke akun TIDAK dihitung dua kali
     * (nilainya sudah tercermin di saldo akun likuid).
     *
     * Kewajiban = jumlah sisa pokok liability + saldo kartu kredit.
     */
    public function calculate(): array
    {
        $assets = (float) Asset::query()
            ->where(function ($query) {
                $query->where('category', '!=', 'cash_bank')
                    ->orWhereNull('linked_account_id');
            })
            ->sum('current_value');

        $liquidBalance = (float) Account::query()
            ->whereIn('type', ['cash', 'bank', 'ewallet', 'saving'])
            ->sum('balance');

        $liabilities = (float) Liability::query()
            ->sum('principal_remaining');

        $creditBalance = (float) Account::query()
            ->where('type', 'credit_card')
            ->where('balance', '>', 0)
            ->sum('balance');

        $totalAssets = round($assets + $liquidBalance, 2);
        $totalLiabilities = round($liabilities + $creditBalance, 2);

        return [
            'assets' => $totalAssets,
            'liabilities' => $totalLiabilities,
            'net_worth' => round($totalAssets - $totalLiabilities, 2),
        ];
    }
}
