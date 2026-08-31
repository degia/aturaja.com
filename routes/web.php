<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FinancialHealthController;
use App\Http\Controllers\LiabilityController;
use App\Http\Controllers\NetWorthController;
use App\Http\Controllers\RecurringRuleController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UploadsController;
use App\Http\Controllers\WorkspaceSettingsController;
use App\Http\Controllers\WorkspaceSwitchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/workspace/switch/{workspace}', [WorkspaceSwitchController::class, 'store'])->name('workspace.switch');

    Route::resource('accounts', AccountController::class)->except(['show']);
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::get('uploads/account-logos/{account}', [UploadsController::class, 'accountLogo'])->name('uploads.account-logo');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');

    Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('transactions', fn () => view('transactions.index'))->name('transactions.index');

    Route::get('budgets', fn () => view('budgets.index'))->name('budgets.index');

    Route::get('net-worth', NetWorthController::class)->name('net-worth');
    Route::get('reports', ReportsController::class)->name('reports.index');
    Route::get('financial-health', FinancialHealthController::class)->name('financial-health');
    Route::post('exports', [ExportController::class, 'store'])->name('exports.store');
    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{exportJob}/download', [ExportController::class, 'download'])->name('exports.download');
    Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
    Route::patch('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    Route::post('liabilities', [LiabilityController::class, 'store'])->name('liabilities.store');
    Route::patch('liabilities/{liability}', [LiabilityController::class, 'update'])->name('liabilities.update');
    Route::delete('liabilities/{liability}', [LiabilityController::class, 'destroy'])->name('liabilities.destroy');

    Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts', [DebtController::class, 'store'])->name('debts.store');
    Route::patch('debts/{debt}', [DebtController::class, 'update'])->name('debts.update');
    Route::delete('debts/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');
    Route::post('debts/{debt}/payments', [DebtController::class, 'storePayment'])->name('debts.payments.store');
    Route::delete('debts/{debt}/payments/{payment}', [DebtController::class, 'destroyPayment'])->name('debts.payments.destroy');

    Route::get('recurring/create', [RecurringRuleController::class, 'create'])->name('recurring.create');
    Route::post('recurring', [RecurringRuleController::class, 'store'])->name('recurring.store');
    Route::delete('recurring/{rule}', [RecurringRuleController::class, 'destroy'])->name('recurring.destroy');
    Route::get('recurring', [RecurringRuleController::class, 'index'])->name('recurring.index');

    Route::get('settings/workspace', [WorkspaceSettingsController::class, 'edit'])->name('settings.index');
    Route::patch('settings/workspace', [WorkspaceSettingsController::class, 'update'])->name('settings.update');
});
