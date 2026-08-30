<?php

use App\Domain\NetWorth\Jobs\SnapshotMonthlyNetWorth;
use App\Domain\Transactions\Jobs\GenerateRecurringTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new GenerateRecurringTransactions)->daily();
Schedule::command('account:reconcile-balances')->daily();
Schedule::job(new SnapshotMonthlyNetWorth)->lastDayOfMonth('23:30');