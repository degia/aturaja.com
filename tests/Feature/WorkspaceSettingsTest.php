<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSettingsTest extends TestCase
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

    public function test_halaman_settings_menampilkan_nama_mata_uang_dan_anggota(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspace($user);

        $this->actingAs($user)->get('/settings/workspace')
            ->assertStatus(200)
            ->assertSee('Workspace Settings')
            ->assertSee($workspace->name)
            ->assertSee('IDR')
            ->assertSee($user->name);
    }

    public function test_pemilik_dapat_memperbarui_nama_dan_mata_uang(): void
    {
        $user = User::factory()->create();
        $this->workspace($user);

        $this->actingAs($user)->patch('/settings/workspace', [
            'name' => 'Bisnis Keluarga',
            'currency' => 'USD',
        ])->assertRedirect('/settings/workspace');

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Bisnis Keluarga',
            'currency' => 'USD',
        ]);
    }

    public function test_validasi_nama_dan_mata_uang(): void
    {
        $user = User::factory()->create();
        $this->workspace($user);

        $this->actingAs($user)->patch('/settings/workspace', [
            'name' => '',
            'currency' => 'Rupiah',
        ])->assertSessionHasErrors(['name', 'currency']);
    }

    public function test_non_pemilik_tidak_bisa_mengubah_pengaturan(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspace($owner);

        $member = User::factory()->create();
        $member->workspaces()->attach($workspace->id, ['role' => 'member']);
        session(['current_workspace_id' => $workspace->id]);

        $this->actingAs($member)->patch('/settings/workspace', [
            'name' => 'Dibajak',
            'currency' => 'IDR',
        ])->assertForbidden();

        $this->assertDatabaseHas('workspaces', ['name' => $workspace->name]);
    }
}
