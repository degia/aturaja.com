<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackupsTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(User $user, string $name = 'Keuangan Pribadi'): Workspace
    {
        $workspace = Workspace::create([
            'name' => $name,
            'owner_user_id' => $user->id,
            'currency' => 'IDR',
        ]);
        $user->workspaces()->attach($workspace->id, ['role' => 'owner']);
        session(['current_workspace_id' => $workspace->id]);

        return $workspace;
    }

    private function seedData(Workspace $workspace): void
    {
        $account = Account::create(['name' => 'Bank', 'type' => 'bank', 'balance' => 100_000]);
        $category = Category::create(['name' => 'Makanan', 'type' => 'expense']);
        $tag = Tag::create(['name' => 'rutin', 'color' => '#DC2626']);

        $tx = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50_000,
            'transaction_date' => now(),
            'note' => 'test',
        ]);
        $tx->tags()->attach($tag->id);
    }

    public function test_pemilik_dapat_mengunduh_backup_full(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspace($user);
        $this->seedData($workspace);

        $this->actingAs($user)->post('/settings/backup', ['scope' => 'full'])
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertDownload();
    }

    public function test_backup_full_memuat_data_transaksi(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspace($user);
        $this->seedData($workspace);

        $response = $this->actingAs($user)->post('/settings/backup', ['scope' => 'full']);
        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame('full', $payload['scope']);
        $this->assertSame($workspace->id, $payload['workspace']['id']);
        $this->assertCount(1, $payload['data']['accounts']);
        $this->assertCount(1, $payload['data']['transactions']);
        $this->assertCount(1, $payload['data']['transaction_tag']);
    }

    public function test_non_pemilik_tidak_bisa_membuat_backup(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspace($owner);

        $member = User::factory()->create();
        $member->workspaces()->attach($workspace->id, ['role' => 'member']);
        session(['current_workspace_id' => $workspace->id]);

        $this->actingAs($member)->post('/settings/backup', ['scope' => 'full'])->assertForbidden();
    }

    public function test_restore_full_mengganti_data_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspace($user);
        $this->seedData($workspace);

        // Simulasikan backup yang diambil dari data awal.
        $response = $this->actingAs($user)->post('/settings/backup', ['scope' => 'full']);
        $payload = json_decode($response->streamedContent(), true);

        // Hapus seluruh data agar simulasi "kehilangan data".
        DB::table('transactions')->delete();
        DB::table('accounts')->delete();

        $this->assertSame(0, Transaction::count());

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));

        $this->actingAs($user)->post('/settings/restore', ['backup' => $file])
            ->assertRedirect();

        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('transactions', ['amount' => 50_000, 'note' => 'test']);
        $this->assertDatabaseHas('accounts', ['name' => 'Bank', 'balance' => 50_000]);
    }

    public function test_restore_settings_tidak_mempengaruhi_transaksi(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspace($user);
        $this->seedData($workspace);

        $response = $this->actingAs($user)->post('/settings/backup', ['scope' => 'settings']);
        $payload = json_decode($response->streamedContent(), true);

        // Settings-only tidak mengandung data transaksi.
        $this->assertArrayNotHasKey('transactions', $payload['data']);
        $this->assertArrayHasKey('accounts', $payload['data']);
        $this->assertArrayHasKey('tags', $payload['data']);

        // Simulasikan perubahan: nama/currency berubah dan tag terhapus.
        $workspace->update(['name' => 'Terganti', 'currency' => 'USD']);
        DB::table('tags')->delete();

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));

        $this->actingAs($user)->post('/settings/restore', ['backup' => $file])->assertRedirect();

        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Keuangan Pribadi', 'currency' => 'IDR']);
        $this->assertDatabaseHas('tags', ['name' => 'rutin']);
        // Transaksi & akun yang direferensikan tetap utuh.
        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('accounts', ['name' => 'Bank', 'balance' => 50_000]);
    }

    public function test_restore_menolak_backup_workspace_lain(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $workspace = $this->workspace($user);
        $this->workspace($other, 'Workspace Lain');

        // Backup diambil milik workspace milik user.
        session(['current_workspace_id' => $workspace->id]);

        $response = $this->actingAs($user)->post('/settings/backup', ['scope' => 'full']);
        $payload = json_decode($response->streamedContent(), true);

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));

        $this->actingAs($other)->post('/settings/restore', ['backup' => $file])
            ->assertSessionHasErrors('backup');
    }

    public function test_restore_menolak_file_bukan_backup(): void
    {
        $user = User::factory()->create();
        $this->workspace($user);

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode(['foo' => 'bar']));

        $this->actingAs($user)->post('/settings/restore', ['backup' => $file])
            ->assertSessionHasErrors('backup');
    }
}
