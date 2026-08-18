<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    protected function sameCompany(User $user, Project $project): bool
    {
        return $user->company_id === $project->company_id;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->sameCompany($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->sameCompany($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->sameCompany($user, $project) && $user->isAdmin();
    }
}
