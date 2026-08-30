<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceSwitchController extends Controller
{
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->workspaces()->whereKey($workspace->id)->exists(), 403);

        session(['current_workspace_id' => $workspace->id]);

        return redirect()->route('dashboard');
    }
}