<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $workspaceId = session('current_workspace_id');

        if (! $workspaceId) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where($model->qualifyColumn('workspace_id'), $workspaceId);
    }
}