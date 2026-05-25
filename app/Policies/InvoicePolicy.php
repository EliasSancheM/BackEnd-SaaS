<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.edit');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.delete');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.delete');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.delete');
    }

    public function send(User $user, Invoice $invoice): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoice->tenant_id
            && $user->can('invoices.send');
    }
}
