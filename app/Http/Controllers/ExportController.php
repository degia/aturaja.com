<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Services\ReportGenerator;
use App\Models\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index()
    {
        $jobs = ExportJob::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('exports.index', [
            'jobs' => $jobs,
            'reports' => ExportJob::REPORTS,
            'types' => ExportJob::TYPES,
        ]);
    }

    public function store(Request $request): BinaryFileResponse|StreamedResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(ExportJob::TYPES)],
            'report' => ['required', Rule::in(array_keys(ExportJob::REPORTS))],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $workspace = $request->user()->currentWorkspace();

        $generated = (new ReportGenerator())->generate(
            type: $validated['type'],
            report: $validated['report'],
            from: $validated['from'],
            to: $validated['to'],
            workspaceName: $workspace->name,
            workspaceId: (string) $workspace->id,
        );

        ExportJob::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'report' => $validated['report'],
            'period_start' => $validated['from'],
            'period_end' => $validated['to'],
            'status' => 'done',
            'file_path' => $generated['path'],
        ]);

        return $this->streamDownload($generated['path'], $generated['name']);
    }

    public function download(ExportJob $exportJob): StreamedResponse|Response
    {
        abort_unless($exportJob->file_path && Storage::disk('local')->exists($exportJob->file_path), 404);

        return $this->streamDownload($exportJob->file_path, basename($exportJob->file_path));
    }

    private function streamDownload(string $path, string $name): StreamedResponse
    {
        $disk = Storage::disk('local');

        return $disk->download($path, $name);
    }
}
