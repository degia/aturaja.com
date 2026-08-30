<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Personal',
            'owner_user_id' => $this->user->id,
            'currency' => 'IDR',
        ]);
        $this->user->workspaces()->attach($workspace->id, ['role' => 'owner']);

        session(['current_workspace_id' => $workspace->id]);

        Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0]);
        Category::create(['name' => 'Makanan', 'type' => 'expense']);
        Tag::create(['name' => 'penting']);
    }

    public function test_halaman_utama_dapat_diakses_user_workspace(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ringkasan Keuangan')
            ->assertSee('AturAja');
    }

    public function test_halaman_transaksi_merender_tabel_livewire(): void
    {
        $html = $this->actingAs($this->user)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Tambah Transaksi')
            ->assertSee('Semua Akun')
            ->getContent();

        $this->assertStringContainsString('m21 21-5.197-5.197', $html, 'Ikon pencarian (magnifying-glass) harus dirender dari Blade Icons.');
    }

    public function test_sidebar_menggunakan_blade_icons_heroicons(): void
    {
        $html = $this->actingAs($this->user)->get('/dashboard')->getContent();

        $this->assertStringNotContainsString('stroke-width="1.8"', $html, 'Sidebar/topbar tidak boleh lagi memuat SVG inline lama (Blade Icons memakai stroke-width 1.5).');
        $this->assertStringContainsString('M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25', $html, 'Ikon dashboard (squares-2x2) harus dirender dari Blade Icons.');
    }

    public function test_halaman_akun_dan_form_akun_dapat_dibuka(): void
    {
        $this->actingAs($this->user)
            ->get('/accounts')
            ->assertOk()
            ->assertSee('Akun & Dompet')
            ->assertSee('Dompet');

        $this->actingAs($this->user)
            ->get('/accounts/create')
            ->assertOk()
            ->assertSee('Tambah Akun Baru');
    }

    public function test_halaman_kategori_dan_tag_dapat_dibuka(): void
    {
        $this->actingAs($this->user)
            ->get('/categories')
            ->assertOk()
            ->assertSee('Makanan');

        $this->actingAs($this->user)
            ->get('/categories/create')
            ->assertOk()
            ->assertSee('Tambah Kategori Baru');

        $this->actingAs($this->user)
            ->get('/tags')
            ->assertOk()
            ->assertSee('penting');
    }

    public function test_halaman_transaksi_berulang_dapat_dibuka(): void
    {
        $this->actingAs($this->user)
            ->get('/recurring')
            ->assertOk()
            ->assertSee('Transaksi Berulang');

        $this->actingAs($this->user)
            ->get('/recurring/create')
            ->assertOk()
            ->assertSee('Tambah Aturan Berulang');
    }

    public function test_halaman_budget_merender_matrik_anggaran(): void
    {
        $this->actingAs($this->user)
            ->get('/budgets')
            ->assertOk()
            ->assertSee('Anggaran')
            ->assertSee('Makanan');
    }

    public function test_halaman_net_worth_dan_debt_tracker_dapat_dibuka(): void
    {
        $this->actingAs($this->user)
            ->get('/net-worth')
            ->assertOk()
            ->assertSee('Net Worth')
            ->assertSee('Total Aset');

        $this->actingAs($this->user)
            ->get('/debts')
            ->assertOk()
            ->assertSee('Debt Tracker')
            ->assertSee('Tambah Utang');
    }

    public function test_halaman_financial_health_dapat_dibuka(): void
    {
        $this->actingAs($this->user)
            ->get('/financial-health')
            ->assertOk()
            ->assertSee('Skor Kesehatan Keuangan')
            ->assertSee('Rasio Tabungan')
            ->assertSee('Rasio Utang (DTI)')
            ->assertSee('Dana Darurat');
    }

    public function test_halaman_export_dan_tombol_export_csv_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get('/exports')
            ->assertOk()
            ->assertSee('Buat Export Baru')
            ->assertSee('Export CSV');

        $html = $this->actingAs($this->user)->get('/dashboard')->getContent();
        $this->assertStringContainsString('Export CSV', $html, 'Tombol Export CSV harus ada di topbar dashboard.');
    }

    public function test_halaman_proteksi_melempar_tamu_ke_login(): void
    {
        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/accounts')->assertRedirect('/login');
    }
}