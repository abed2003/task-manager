<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;

trait AuthorizesProjectAccess
{
    protected function authorizeProjectAccess(Project $project): void
    {
        abort_unless($project->isAccessibleBy(auth()->user()), 403);
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
