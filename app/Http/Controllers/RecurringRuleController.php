<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringRuleController extends Controller
{
    public function index(): View
    {
        return view('recurring.index', [
            'rules' => RecurringRule::with(['account', 'category', 'transferToAccount'])
                ->orderByDesc('is_active')
                ->orderBy('next_run_date')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('recurring.create', [
            'accounts' => Account::active()->orderBy('name')->get(),
            'incomeCategories' => Category::income()->active()->orderBy('name')->get(),
            'expenseCategories' => Category::expense()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'transfer_to_account_id' => ['nullable', 'integer', 'different:account_id'],
            'type' => ['required', Rule::in(RecurringRule::TYPES)],
            'category_id' => ['required_unless:type,transfer', 'nullable', 'integer', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', Rule::in(RecurringRule::FREQUENCIES)],
            'interval_count' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_run_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $data['next_run_date'] ??= $data['start_date'];

        RecurringRule::create($data);

        return redirect()->route('recurring.index')->with('status', 'Aturan berulang ditambahkan.');
    }

    public function destroy(RecurringRule $rule): RedirectResponse
    {
        $rule->delete();

        return redirect()->route('recurring.index')->with('status', 'Aturan berulang dihapus.');
    }
}