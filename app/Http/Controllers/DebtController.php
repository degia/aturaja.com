<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function index(): View
    {
        $debts = Debt::query()
            ->with(['payments', 'installments', 'account'])
            ->orderByDesc('id')
            ->get();

        return view('debts.index', [
            'debts' => $debts,
            'accounts' => Account::active()->orderBy('name')->get(),
            'totalPayable' => (float) $debts->where('direction', 'payable')->sum('remaining_amount'),
            'totalReceivable' => (float) $debts->where('direction', 'receivable')->sum('remaining_amount'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $principal = (float) $data['principal_amount'];
        $data['remaining_amount'] = $principal;
        $data['status'] = $principal <= 0 ? 'paid' : 'ongoing';

        $debt = Debt::create($data);

        if ($debt->installments_count) {
            $debt->generateSchedule();
        }

        return redirect()->route('debts.index')->with('status', 'Utang/piutang berhasil ditambahkan.');
    }

    public function update(Request $request, Debt $debt): RedirectResponse
    {
        $data = $this->validated($request, $debt->id);

        $debt->update($data);

        if ($debt->wasChanged(['principal_amount', 'installments_count', 'first_due_date'])) {
            $debt->generateSchedule();
        }

        $debt->recalculateRemaining();

        return redirect()->route('debts.index')->with('status', 'Utang/piutang berhasil diperbarui.');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        $debt->delete();

        return redirect()->route('debts.index')->with('status', 'Utang/piutang berhasil dihapus.');
    }

    public function storePayment(Request $request, Debt $debt): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ((float) $validated['amount'] > (float) $debt->remaining_amount) {
            return back()
                ->withErrors(['amount' => 'Jumlah pembayaran melebihi sisa utang/piutang.'])
                ->withInput();
        }

        DebtPayment::create([
            'debt_id' => $debt->id,
            'account_id' => $validated['account_id'],
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('debts.index')->with('status', 'Pembayaran dicatat dan masuk ke transaksi.');
    }

    public function destroyPayment(Debt $debt, DebtPayment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('debts.index')->with('status', 'Catatan pembayaran dihapus dan transaksi dibatalkan.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'direction' => ['required', Rule::in(Debt::DIRECTIONS)],
            'counterparty_name' => ['required', 'string', 'max:255'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1', 'max:120'],
            'first_due_date' => ['nullable', 'date'],
            'default_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
