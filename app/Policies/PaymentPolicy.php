<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $payment->tenant_id
            && $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $payment->tenant_id
            && $user->can('invoices.edit');
    }

    public function delete(User $user, Payment $payment): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $payment->tenant_id
            && $user->can('invoices.delete');
    }
}
