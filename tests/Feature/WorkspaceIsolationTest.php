<?php

namespace Tests\Feature;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkspaceProbe extends Model
{
    use BelongsToWorkspace;

    protected $table = 'probes';

    protected $guarded = [];
}

class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('probes');
        Schema::create('probes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    private function makeUserWithTwoWorkspaces(): array
    {
        $user = User::factory()->create();
        $first = Workspace::create(['name' => 'Personal', 'owner_user_id' => $user->id, 'currency' => 'IDR']);
        $second = Workspace::create(['name' => 'Bisnis', 'owner_user_id' => $user->id, 'currency' => 'IDR']);

        $user->workspaces()->attach([
            $first->id => ['role' => 'owner'],
            $second->id => ['role' => 'owner'],
        ]);

        return [$user, $first, $second];
    }

    public function test_scope_membatasi_query_hanya_pada_workspace_aktif(): void
    {
        [$user, $first, $second] = $this->makeUserWithTwoWorkspaces();

        session(['current_workspace_id' => $first->id]);
        WorkspaceProbe::create(['name' => 'Beli susu']);

        session(['current_workspace_id' => $second->id]);
        WorkspaceProbe::create(['name' => 'Alat kantor']);

        session(['current_workspace_id' => $first->id]);
        $this->assertCount(1, WorkspaceProbe::all());
        $this->assertSame('Beli susu', WorkspaceProbe::first()->name);

        session(['current_workspace_id' => $second->id]);
        $this->assertCount(1, WorkspaceProbe::all());
        $this->assertSame('Alat kantor', WorkspaceProbe::first()->name);
    }

    public function test_creating_secara_otomatis_mengisi_workspace_id_dari_session(): void
    {
        [$user, $first] = $this->makeUserWithTwoWorkspaces();

        session(['current_workspace_id' => $first->id]);
        $probe = WorkspaceProbe::create(['name' => 'Tanpa workspace_id']);

        $this->assertEquals($first->id, $probe->workspace_id);
    }

    public function test_tanpa_workspace_terpilih_scope_mengosongkan_query(): void
    {
        [$user, $first, $second] = $this->makeUserWithTwoWorkspaces();

        session(['current_workspace_id' => $first->id]);
        WorkspaceProbe::create(['name' => 'Data pribadi']);

        session()->forget('current_workspace_id');

        $this->assertCount(0, WorkspaceProbe::all());
    }

    public function test_middleware_mengganti_workspace_asing_dengan_workspace_milik_user(): void
    {
        [$user, $first, $second] = $this->makeUserWithTwoWorkspaces();
        Workspace::create(['name' => 'Milik orang lain']);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $first->id - 100])
            ->get('/dashboard')
            ->assertOk();

        $this->assertSame($first->id, (int) session('current_workspace_id'));
    }
}