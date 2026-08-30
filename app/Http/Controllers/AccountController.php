<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Support\BankLogos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = Account::withCount('transactions')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $totalBalance = $accounts->where('is_archived', false)->where('type', '!=', 'credit_card')->sum('balance');
        $totalCreditBalance = $accounts->where('is_archived', false)->where('type', 'credit_card')->sum('balance');

        return view('accounts.index', compact('accounts', 'totalBalance', 'totalCreditBalance'));
    }

    public function create(): View
    {
        return view('accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_emergency_fund'] = $request->boolean('is_emergency_fund');

        if ($request->hasFile('logo')) {
            $data['icon'] = $this->storeLogo($request);
        }

        Account::create($data);

        return redirect()->route('accounts.index')->with('status', 'Akun berhasil ditambahkan.');
    }

    public function edit(Account $account): View
    {
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_emergency_fund'] = $request->boolean('is_emergency_fund');

        if ($request->hasFile('logo')) {
            $this->forgetLogo($account);
            $data['icon'] = $this->storeLogo($request);
        } elseif ($request->boolean('logo_touched')) {
            $data['icon'] = $request->input('icon') ?: null;
            $this->forgetLogo($account, $data['icon']);
        }

        $account->update($data);

        return redirect()->route('accounts.index')->with('status', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('accounts.index')->with('status', 'Akun diarsipkan.');
    }

    public function archive(Account $account): RedirectResponse
    {
        $account->update(['is_archived' => ! $account->is_archived]);

        return redirect()->route('accounts.index')->with(
            'status',
            $account->is_archived ? 'Akun diarsipkan.' : 'Akun dikembalikan.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Account::TYPES)],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'billing_date' => ['nullable', 'integer', 'between:1,31'],
            'due_date' => ['nullable', 'integer', 'between:1,31'],
            'icon' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:svg,png,jpg,jpeg', 'max:1024'],
            'logo_touched' => ['nullable', 'in:0,1'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_emergency_fund' => ['nullable', 'boolean'],
        ]);
    }

    private function storeLogo(Request $request): string
    {
        $path = $request->file('logo')->store('account-logos', ['disk' => 'public']);

        return BankLogos::UPLOAD_PREFIX.$path;
    }

    private function forgetLogo(Account $account, ?string $nextIcon = null): void
    {
        $oldPath = BankLogos::uploadedPath($account->icon);
        if ($oldPath !== null && $oldPath !== BankLogos::uploadedPath($nextIcon)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}