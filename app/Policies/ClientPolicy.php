<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /** Garante que o registro é da mesma empresa do usuário. */
    protected function sameCompany(User $user, Client $client): bool
    {
        return $user->company_id === $client->company_id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $this->sameCompany($user, $client);
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Client $client): bool
    {
        return $this->sameCompany($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->sameCompany($user, $client) && $user->isManager();
    }
}
