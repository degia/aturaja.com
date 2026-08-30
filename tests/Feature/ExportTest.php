<?php

namespace Tests\Feature;

use App\Domain\Exports\Exports\ReportExport;
use App\Domain\Exports\Services\ReportExportData;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Liability;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $this->user->id,
            'currency' => 'IDR',
        ]);
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $this->workspace->id]);
    }

    private function transaction(string $type, float $amount, string $date, ?Category $category = null): Transaction
    {
        $account = Account::create(['name' => 'Bank '.uniqid(), 'type' => 'bank', 'balance' => 0]);

        return Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
            'note' => 'Catatan '.$type,
        ]);
    }

    public function test_data_export_transaksi_memuat_kolom_dan_baris_yang_benar(): void
    {
        $category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $t = $this->transaction('expense', 15000, '2026-08-10', $category);
        $this->transaction('income', 5000000, '2026-08-12');

        $data = new ReportExportData();
        $rows = $data->rows('transactions', now()->parse('2026-08-01'), now()->parse('2026-08-31'));

        $this->assertSame(['Tanggal', 'Tipe', 'Kategori', 'Akun', 'Catatan', 'Tag', 'Jumlah'], $data->headings('transactions'));

        $row = collect($rows->all())->firstWhere('0', '10/08/2026');
        $this->assertSame('Pengeluaran', $row[1]);
        $this->assertSame('Makanan', $row[2]);
        $this->assertSame('Catatan expense', $row[4]);
        $this->assertSame('-15.000,00', $row[6]);
    }

    public function test_csv_export_mengandung_header_bom_utf8_dan_nilai_rupiah(): void
    {
        $category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $this->transaction('expense', 15000, '2026-08-10', $category);

        Storage::fake('local');
        Excel::fake();

        $export = new ReportExport('transactions', '2026-08-01', '2026-08-31');
        $this->assertSame(['Tanggal', 'Tipe', 'Kategori', 'Akun', 'Catatan', 'Tag', 'Jumlah'], $export->headings());
        $this->assertTrue($export->getCsvSettings()['use_bom'], 'CSV harus menyertakan BOM agar ekspor UTF-8 benar.');
    }

    public function test_data_export_cash_flow_net_worth_dan_budget_terhitung(): void
    {
        $category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $this->transaction('expense', 50000, '2026-08-05', $category);
        $this->transaction('income', 2000000, '2026-08-06');

        Asset::create(['name' => 'Rumah', 'category' => 'real_estate', 'current_value' => 500_000_000]);
        Liability::create(['name' => 'KPR', 'category' => 'mortgage', 'principal_remaining' => 300_000_000]);

        $data = new ReportExportData();

        $cash = $data->rows('cash_flow', now()->parse('2026-08-01'), now()->parse('2026-08-31'));
        $this->assertSame('TOTAL', $cash->last()[0]);
        $this->assertSame('2.000.000,00', $cash->last()[1]);
        $this->assertSame('50.000,00', $cash->last()[2]);

        $netWorth = $data->rows('net_worth', null, null);
        $this->assertSame('Net Worth', $netWorth->last()[0]);
    }

    public function test_route_export_menghasilkan_file_dan_mencatat_riwayat(): void
    {
        $this->transaction('income', 1000000, '2026-08-15');

        Storage::fake('local');

        $response = $this->actingAs($this->user)            ->post('/exports', [
                'type' => 'csv',
                'report' => 'transactions',
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('export_jobs', 1);
        $this->assertDatabaseHas('export_jobs', [
            'workspace_id' => $this->workspace->id,
            'report' => 'transactions',
            'status' => 'done',
            'type' => 'csv',
        ]);
    }

    public function test_pdf_report_dirrender_menghasilkan_byte_valid(): void
    {
        $category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $this->transaction('expense', 15000, '2026-08-10', $category);

        Storage::fake('local');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf.report', [
            'title' => 'Daftar Transaksi',
            'periodText' => '01 Agustus 2026 – 31 Agustus 2026',
            'workspaceName' => 'Personal',
            'headings' => ['Tanggal', 'Jumlah'],
            'rows' => [['10/08/2026', '-15.000,00']],
            'numColumns' => [1],
        ])->output();

        $this->assertStringStartsWith('%PDF', $pdf, 'Hasil render PDF harus diawali penanda %PDF.');
    }
}
