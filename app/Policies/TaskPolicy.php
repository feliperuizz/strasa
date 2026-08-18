<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    protected function sameCompany(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->sameCompany($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    /** Qualquer membro da empresa pode editar/mover a tarefa. */
    public function update(User $user, Task $task): bool
    {
        return $this->sameCompany($user, $task);
    }

    /** Excluir: qualquer membro da mesma empresa. */
    public function delete(User $user, Task $task): bool
    {
        return $this->sameCompany($user, $task);
    }
}
