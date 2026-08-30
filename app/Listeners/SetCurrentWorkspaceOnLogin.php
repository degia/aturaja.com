<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class SetCurrentWorkspaceOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $workspace = $user->workspaces()->orderBy('id')->first();

            if ($workspace) {
                session(['current_workspace_id' => $workspace->id]);
            }
        }
    }
}