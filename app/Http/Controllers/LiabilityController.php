<?php

namespace App\Http\Controllers;

use App\Models\Liability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LiabilityController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Liability::create($this->validated($request));

        return redirect()->route('net-worth')->with('status', 'Kewajiban berhasil ditambahkan.');
    }

    public function update(Request $request, Liability $liability): RedirectResponse
    {
        $liability->update($this->validated($request));

        return redirect()->route('net-worth')->with('status', 'Kewajiban berhasil diperbarui.');
    }

    public function destroy(Liability $liability): RedirectResponse
    {
        $liability->delete();

        return redirect()->route('net-worth')->with('status', 'Kewajiban berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Liability::CATEGORIES)],
            'principal_remaining' => ['required', 'numeric', 'min:0'],
            'monthly_installment' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'linked_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        if ($data['monthly_installment'] === null || $data['monthly_installment'] === '') {
            $data['monthly_installment'] = null;
        }
        if ($data['interest_rate'] === null || $data['interest_rate'] === '') {
            $data['interest_rate'] = null;
        }

        return $data;
    }
}
