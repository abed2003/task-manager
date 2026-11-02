<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->isAccessibleBy($user);
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }
}
