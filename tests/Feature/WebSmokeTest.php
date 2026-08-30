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
        $this->actingAs($this->user)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Tambah Transaksi')
            ->assertSee('Semua Akun');
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

    public function test_halaman_proteksi_melempar_tamu_ke_login(): void
    {
        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/accounts')->assertRedirect('/login');
    }
}