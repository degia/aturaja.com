<?php

namespace App\Http\Controllers;

use App\Domain\Backup\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupsController extends Controller
{
    public function backup(Request $request, BackupService $service): StreamedResponse
    {
        $workspace = $request->user()->currentWorkspace();

        abort_unless($workspace->owner_user_id === $request->user()->id, 403, 'Hanya pemilik workspace yang dapat membuat backup.');

        $scope = $request->validate([
            'scope' => ['required', 'in:full,settings'],
        ])['scope'];

        $result = $service->export($workspace, $scope);

        $path = "backups/{$result['filename']}";
        Storage::disk('local')->put($path, json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return Storage::disk('local')->download($path, $result['filename']);
    }

    public function restore(Request $request, BackupService $service): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace();

        abort_unless($workspace->owner_user_id === $request->user()->id, 403, 'Hanya pemilik workspace yang dapat me-restore data.');

        $validated = $request->validate([
            'backup' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
        ]);

        $payload = json_decode($validated['backup']->get(), true);

        if (! is_array($payload)) {
            return back()->withErrors(['backup' => 'File backup tidak valid (bukan JSON yang benar).']);
        }

        try {
            $service->restore($workspace, $payload);
        } catch (\Throwable $e) {
            return back()->withErrors(['backup' => 'Restore gagal: data tidak diubah. ('.$e->getMessage().')']);
        }

        return redirect()->route('dashboard')->with('status', 'Data berhasil dipulihkan dari backup. Dashboard telah diperbarui dengan data terbaru.');
    }
}
