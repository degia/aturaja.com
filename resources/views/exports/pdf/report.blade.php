<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} · AturAja</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1C2B22; font-size: 11px; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #16A34A; padding-bottom: 10px; margin-bottom: 18px; }
        .brand { font-size: 20px; font-weight: bold; color: #16A34A; }
        .brand small { display: block; font-size: 10px; color: #6B8074; font-weight: normal; }
        h1 { font-size: 16px; margin-top: 6px; }
        .meta { font-size: 10px; color: #6B8074; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #DCFCE7; color: #15803D; text-align: left; padding: 7px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; }
        tr:last-child td { border-bottom: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .total-row td { font-weight: bold; background: #F2F8F4; border-top: 2px solid #16A34A; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; color: #6B8074; font-size: 9px; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        .empty { padding: 30px 0; text-align: center; color: #6B8074; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">AturAja <small>Atur uangmu, aja.</small></div>
            <h1>{{ $title }}</h1>
            <div class="meta">
                {{ $workspaceName }}@if($periodText) · {{ $periodText }}@endif · {{ now()->translatedFormat('d F Y H:i') }}
            </div>
        </div>
    </div>

    @if (count($rows) === 0)
        <div class="empty">Belum ada data untuk periode ini.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($headings as $index => $heading)
                        <th class="{{ $numColumns[$index] ?? '' }}">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $last = false;
                        $firstCell = is_array($row) ? (string) reset($row) : '';
                    @endphp
                    <tr class="{{ in_array(strtolower((string) $firstCell), ['total', 'total aset', 'net worth'], true) ? 'total-row' : '' }}">
                        @foreach ($headings as $index => $heading)
                            <td class="{{ $numColumns[$index] ?? '' }}">{{ is_array($row) ? ($row[$index] ?? '') : '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Dokumen dihasilkan otomatis oleh AturAja.com · Periode {{ $periodText ?: 'semua data' }}</div>
</body>
</html>
