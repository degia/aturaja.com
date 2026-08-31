<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $workspace = $request->user()->currentWorkspace();

        return view('settings.index', [
            'workspace' => $workspace,
            'members' => $workspace->members()->orderBy('workspace_user.role')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace();

        abort_unless($workspace->owner_user_id === $request->user()->id, 403, 'Hanya pemilik workspace yang dapat mengubah pengaturan.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);

        $workspace->update($data);

        return redirect()->route('settings.index')
            ->with('status', 'Pengaturan workspace berhasil diperbarui.');
    }
}
