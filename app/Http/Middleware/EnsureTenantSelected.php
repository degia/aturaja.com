<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $workspaceId = session('current_workspace_id');

        $isMember = $workspaceId
            && $user->workspaces()->whereKey($workspaceId)->exists();

        if (! $isMember) {
            $workspace = $user->workspaces()->orderBy('id')->first();

            if (! $workspace) {
                abort(403, 'Belum ada workspace untuk akun ini.');
            }

            session(['current_workspace_id' => $workspace->id]);
        }

        return $next($request);
    }
}