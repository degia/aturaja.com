<?php

namespace App\Http\Controllers;

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
            ->withSum('payments as paid_total', 'amount')
            ->orderByDesc('id')
            ->get();

        return view('debts.index', [
            'debts' => $debts,
            'totalPayable' => (float) $debts->where('direction', 'payable')->sum('remaining_amount'),
            'totalReceivable' => (float) $debts->where('direction', 'receivable')->sum('remaining_amount'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['status'] = (float) $data['remaining_amount'] >= (float) $data['principal_amount'] ? 'ongoing' : 'partially_paid';

        Debt::create($data);

        return redirect()->route('debts.index')->with('status', 'Utang/piutang berhasil ditambahkan.');
    }

    public function update(Request $request, Debt $debt): RedirectResponse
    {
        $data = $this->validated($request, $debt->id);
        $debt->update($data);
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
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DebtPayment::create([
            'debt_id' => $debt->id,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('debts.index')->with('status', 'Pembayaran berhasil dicatat.');
    }

    public function destroyPayment(Debt $debt, DebtPayment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('debts.index')->with('status', 'Catatan pembayaran dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'direction' => ['required', Rule::in(Debt::DIRECTIONS)],
            'counterparty_name' => ['required', 'string', 'max:255'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'remaining_amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
