<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_membuat_user_workspace_pertama_dan_mengarahkan_ke_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'budi@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(1, $user->workspaces()->count());

        $workspace = $user->workspaces()->first();

        $this->assertSame('owner', $workspace->pivot->role);
        $this->assertSame($user->id, $workspace->owner_user_id);
        $this->assertSame('IDR', $workspace->currency);
        $this->assertSame($workspace->id, (int) session('current_workspace_id'));
    }

    public function test_register_menseed_kategori_default_ke_workspace_baru(): void
    {
        $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $user = User::query()->where('email', 'siti@example.com')->first();
        $workspace = $user->workspaces()->first();

        $this->assertGreaterThan(0, $workspace->categories()->count());
        $this->assertTrue($workspace->categories()->income()->where('name', 'Gaji')->exists());
        $this->assertTrue($workspace->categories()->expense()->where('name', 'Makanan')->exists());
        $this->assertTrue($workspace->categories()->where('is_default', true)->count() > 0);
    }

    public function test_register_memvalidasi_email_unik(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->post('/register', [
            'name' => 'Budi Lain',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_mengatur_current_workspace_di_session(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Keuangan Pribadi', 'owner_user_id' => $user->id]);
        $user->workspaces()->attach($workspace->id, ['role' => 'owner']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame($workspace->id, (int) session('current_workspace_id'));
    }

    public function test_dashboard_memerlukan_autentikasi(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}