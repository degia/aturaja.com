<?php

namespace App\Domain\Exports\Exports;

use App\Domain\Exports\Services\ReportExportData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportExport implements FromCollection, WithHeadings, WithCustomCsvSettings
{
    public function __construct(
        private readonly string $report,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {
    }

    public function collection(): Collection
    {
        return (new ReportExportData())->rows($this->report, $this->from ? now()->parse($this->from) : null, $this->to ? now()->parse($this->to) : null);
    }

    public function headings(): array
    {
        return (new ReportExportData())->headings($this->report);
    }

    public function getCsvSettings(): array
    {
        return [
            'use_bom' => true,
        ];
    }
}
