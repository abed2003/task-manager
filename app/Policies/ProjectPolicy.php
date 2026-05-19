<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->role === 'admin' || $project->tasks()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }
}
