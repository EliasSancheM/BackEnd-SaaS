<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $client->tenant_id
            && $user->can('clients.view');
    }

    public function create(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $client->tenant_id
            && $user->can('clients.edit');
    }

    public function delete(User $user, Client $client): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $client->tenant_id
            && $user->can('clients.delete');
    }

    public function restore(User $user, Client $client): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $client->tenant_id
            && $user->can('clients.delete');
    }

    public function forceDelete(User $user, Client $client): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $client->tenant_id
            && $user->can('clients.delete');
    }
}
