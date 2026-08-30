<?php

namespace App\Domain\Exports\Services;

use App\Domain\Exports\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportGenerator
{
    private const NUM_COLUMNS = [
        'transactions' => [6],
        'cash_flow' => [1, 2, 3],
        'net_worth' => [3],
        'budget_vs_actual' => [1, 2, 3],
        'expense_breakdown' => [1, 2],
    ];

    /**
     * @return array{path: string, name: string, size: int}
     */
    public function generate(string $type, string $report, string $from, string $to, string $workspaceName, string $workspaceId): array
    {
        $data = new ReportExportData();
        $rows = $data->rows($report, Carbon::parse($from), Carbon::parse($to));
        $headings = $data->headings($report);

        $stamp = Carbon::now()->format('Ymd-His');
        $base = Str::slug($report).'-'.Carbon::parse($from)->format('Ymd').'-'.Carbon::parse($to)->format('Ymd').'-'.$stamp;
        $folder = 'exports/'.$workspaceId;

        return match ($type) {
            'pdf' => $this->pdf($report, $rows, $headings, $from, $to, $workspaceName, $folder, $base),
            'excel' => $this->excel($report, $from, $to, $type, $folder, $base),
            default => $this->excel($report, $from, $to, 'csv', $folder, $base),
        };
    }

    private function pdf(string $report, $rows, array $headings, string $from, string $to, string $workspaceName, string $folder, string $base): array
    {
        $fromLabel = Carbon::parse($from)->translatedFormat('d F Y');
        $toLabel = Carbon::parse($to)->translatedFormat('d F Y');
        $periodText = $fromLabel.' – '.$toLabel;

        $path = $folder.'/'.$base.'.pdf';

        $pdf = Pdf::loadView('exports.pdf.report', [
            'title' => ReportExportData::label($report),
            'periodText' => $periodText,
            'workspaceName' => $workspaceName,
            'headings' => $headings,
            'rows' => $rows->all(),
            'numColumns' => self::NUM_COLUMNS[$report] ?? [],
        ])->setPaper('a4', 'portrait');

        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdf->output());

        return [
            'path' => $path,
            'name' => basename($path),
            'size' => strlen($pdf->output()),
        ];
    }

    private function excel(string $report, string $from, string $to, string $type, string $folder, string $base): array
    {
        $extension = $type === 'excel' ? 'xlsx' : 'csv';
        $path = $folder.'/'.$base.'.'.$extension;

        $export = new ReportExport($report, $from, $to);

        Excel::store($export, $path, 'local');

        $size = \Illuminate\Support\Facades\Storage::disk('local')->size($path);

        return [
            'path' => $path,
            'name' => basename($path),
            'size' => $size,
        ];
    }
}
