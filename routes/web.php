<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecurringRuleController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WorkspaceSwitchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/workspace/switch/{workspace}', [WorkspaceSwitchController::class, 'store'])->name('workspace.switch');

    Route::resource('accounts', AccountController::class)->except(['show']);
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');

    Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('transactions', fn () => view('transactions.index'))->name('transactions.index');

    Route::get('budgets', fn () => view('budgets.index'))->name('budgets.index');

    Route::get('recurring/create', [RecurringRuleController::class, 'create'])->name('recurring.create');
    Route::post('recurring', [RecurringRuleController::class, 'store'])->name('recurring.store');
    Route::delete('recurring/{rule}', [RecurringRuleController::class, 'destroy'])->name('recurring.destroy');
    Route::get('recurring', [RecurringRuleController::class, 'index'])->name('recurring.index');
});