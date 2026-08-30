<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAccountBalances extends Command
{
    protected $signature = 'account:reconcile-balances';

    protected $description = 'Hitung ulang saldo setiap akun dari seluruh transaksi (fallback perbaikan saldo)';

    public function handle(): int
    {
        $accounts = DB::table('accounts')
            ->select('accounts.id', 'accounts.name')
            ->selectRaw('COALESCE(SUM(CASE
                WHEN transactions.type = "income" AND accounts.type = "credit_card" THEN -transactions.amount
                WHEN transactions.type = "income" THEN transactions.amount
                WHEN transactions.type = "expense" AND accounts.type = "credit_card" THEN transactions.amount
                WHEN transactions.type = "expense" THEN -transactions.amount
                WHEN transactions.type = "transfer" AND transactions.account_id = accounts.id AND accounts.type = "credit_card" THEN transactions.amount
                WHEN transactions.type = "transfer" AND transactions.account_id = accounts.id THEN -transactions.amount
                WHEN transactions.type = "transfer" AND transactions.transfer_to_account_id = accounts.id AND accounts.type = "credit_card" THEN -transactions.amount
                WHEN transactions.type = "transfer" AND transactions.transfer_to_account_id = accounts.id THEN transactions.amount
                ELSE 0
            END), 0) AS computed_balance')
            ->leftJoin('transactions', function ($join) {
                $join->on(function ($query) {
                    $query->on('transactions.account_id', '=', 'accounts.id')
                        ->orOn('transactions.transfer_to_account_id', '=', 'accounts.id');
                })->whereNull('transactions.deleted_at');
            })
            ->groupBy('accounts.id', 'accounts.name')
            ->get();

        foreach ($accounts as $account) {
            DB::table('accounts')
                ->where('id', $account->id)
                ->update(['balance' => $account->computed_balance]);
        }

        $this->info("Saldo {$accounts->count()} akun berhasil direkonsiliasi.");

        return self::SUCCESS;
    }
}