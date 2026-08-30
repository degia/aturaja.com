<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@aturaja.test'],
            ['name' => 'Demo User', 'password' => bcrypt('password')],
        );

        $workspace = Workspace::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'Workspace Saya', 'owner_user_id' => $user->id, 'currency' => 'IDR'],
        );

        if ($workspace->wasRecentlyCreated || ! $user->workspaces()->whereKey($workspace->id)->exists()) {
            $user->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'owner']]);
        }

        $this->call(DemoDataSeeder::class);
    }
}