<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;

trait AuthorizesProjectAccess
{
    protected function authorizeProjectAccess(Project $project): void
    {
        $user = auth()->user();

        abort_unless(
            $user->role === 'admin' || $project->tasks()->where('user_id', $user->id)->exists(),
            403
        );
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
