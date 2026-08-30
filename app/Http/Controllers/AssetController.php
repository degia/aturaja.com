<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['category'] = $data['category'] === 'cash_bank' && $data['linked_account_id']
            ? 'cash_bank'
            : $data['category'];

        Asset::create($data);

        return redirect()->route('net-worth')->with('status', 'Aset berhasil ditambahkan.');
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update($this->validated($request, $asset->id));

        return redirect()->route('net-worth')->with('status', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('net-worth')->with('status', 'Aset berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Asset::CATEGORIES)],
            'current_value' => ['required', 'numeric', 'min:0'],
            'linked_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'last_updated_at' => ['nullable', 'date'],
        ]);
    }
}
