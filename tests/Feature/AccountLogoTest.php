<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use App\Support\BankLogos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountLogoTest extends TestCase
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

    private function createAccountWithIcon(?string $icon): Account
    {
        return Account::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Akun '.Str::random(6),
            'type' => 'bank',
            'balance' => 0,
            'icon' => $icon,
        ]);
    }

    public function test_registry_menuatkan_badge_bank_dan_ewallet(): void
    {
        $this->assertSame('BCA', BankLogos::find('bca')['label']);
        $this->assertSame('#0060AC', BankLogos::find('bca')['color']);
        $this->assertArrayHasKey('mandiri', BankLogos::all());
        $this->assertArrayHasKey('bni', BankLogos::all());
        $this->assertArrayHasKey('gopay', BankLogos::all());
        $this->assertArrayHasKey('dana', BankLogos::all());
        $this->assertArrayHasKey('ovo', BankLogos::all());
        $this->assertNull(BankLogos::find('bank-tidak-ada'));
    }

    public function test_helper_mengenali_ikon_upload(): void
    {
        $this->assertTrue(BankLogos::isUploaded('upload:account-logos/a.svg'));
        $this->assertSame('account-logos/a.svg', BankLogos::uploadedPath('upload:account-logos/a.svg'));
        $this->assertFalse(BankLogos::isUploaded('bca'));
        $this->assertNull(BankLogos::uploadedPath('bca'));
    }

    public function test_logo_url_mengarah_ke_rute_uploads(): void
    {
        $account = $this->createAccountWithIcon('upload:account-logos/a.svg');

        $this->assertSame(
            route('uploads.account-logo', $account),
            $account->logo_url
        );

        $this->assertNull($this->createAccountWithIcon('bca')->logo_url);
        $this->assertNull($this->createAccountWithIcon(null)->logo_url);
    }

    public function test_rute_uploads_stream_file_logo_dengan_mime_benar(): void
    {
        Storage::fake('public');
        $path = 'account-logos/logo.png';
        Storage::disk('public')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));

        $account = $this->createAccountWithIcon('upload:'.$path);

        $this->actingAs($this->user)
            ->get(route('uploads.account-logo', $account))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_rute_uploads_404_saat_logo_tidak_ada(): void
    {
        Storage::fake('public');

        $missing = $this->createAccountWithIcon('upload:account-logos/hilang.svg');
        $this->actingAs($this->user)->get(route('uploads.account-logo', $missing))->assertNotFound();

        $badge = $this->createAccountWithIcon('bca');
        $this->actingAs($this->user)->get(route('uploads.account-logo', $badge))->assertNotFound();
    }

    public function test_akun_dapat_dibuat_dengan_badge_bawaan(): void
    {
        $this->actingAs($this->user)->post('/accounts', [
            'name' => 'Rekening BCA',
            'type' => 'bank',
            'balance' => 1000,
            'icon' => 'bca',
            'logo_touched' => '1',
        ])->assertRedirect('/accounts');

        $this->assertDatabaseHas('accounts', ['name' => 'Rekening BCA', 'type' => 'bank', 'icon' => 'bca']);
    }

    public function test_akun_dapat_dibuat_dengan_upload_logo_png(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user)->post('/accounts', [
            'name' => 'Rekening Upload',
            'type' => 'bank',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect('/accounts');

        $account = Account::where('name', 'Rekening Upload')->firstOrFail();
        $this->assertTrue(BankLogos::isUploaded($account->icon));
        Storage::disk('public')->assertExists(BankLogos::uploadedPath($account->icon));
    }

    public function test_update_mengganti_upload_dengan_badge_dan_menghapus_file_lama(): void
    {
        Storage::fake('public');
        $account = $this->createAccountWithIcon('upload:account-logos/old.svg');
        Storage::disk('public')->put('account-logos/old.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->actingAs($this->user)->put('/accounts/'.$account->id, [
            'name' => $account->name,
            'type' => $account->type,
            'icon' => 'mandiri',
            'logo_touched' => '1',
        ])->assertRedirect('/accounts');

        $this->assertSame('mandiri', $account->refresh()->icon);
        Storage::disk('public')->assertMissing('account-logos/old.svg');
    }

    public function test_update_dengan_upload_baru_menghapus_logo_lama(): void
    {
        Storage::fake('public');
        $account = $this->createAccountWithIcon('upload:account-logos/ganti.svg');
        Storage::disk('public')->put('account-logos/ganti.svg', '<svg></svg>');

        $this->actingAs($this->user)->put('/accounts/'.$account->id, [
            'name' => $account->name,
            'type' => $account->type,
            'logo' => UploadedFile::fake()->image('baru.png'),
        ])->assertRedirect('/accounts');

        $account->refresh();
        $this->assertTrue(BankLogos::isUploaded($account->icon));
        $this->assertNotSame('upload:account-logos/ganti.svg', $account->icon);
        Storage::disk('public')->assertMissing('account-logos/ganti.svg');
        Storage::disk('public')->assertExists(BankLogos::uploadedPath($account->icon));
    }

    public function test_update_tanpa_menyentuh_logo_mempertahankan_logo(): void
    {
        Storage::fake('public');
        $account = $this->createAccountWithIcon('upload:account-logos/keep.svg');
        Storage::disk('public')->put('account-logos/keep.svg', '<svg></svg>');

        $this->actingAs($this->user)->put('/accounts/'.$account->id, [
            'name' => 'Nama Baru',
            'type' => $account->type,
        ])->assertRedirect('/accounts');

        $account->refresh();
        $this->assertSame('Nama Baru', $account->name);
        $this->assertSame('upload:account-logos/keep.svg', $account->icon);
        Storage::disk('public')->assertExists('account-logos/keep.svg');
    }

    public function test_upload_file_non_gambar_ditolak(): void
    {
        $this->actingAs($this->user)
            ->from('/accounts/create')
            ->post('/accounts', [
                'name' => 'Salah',
                'type' => 'bank',
                'logo' => UploadedFile::fake()->create('virus.txt', 100),
            ])
            ->assertSessionHasErrors('logo');
    }
}