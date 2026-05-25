<?php

namespace App\Policies;

use App\Models\InvoiceItem;
use App\Models\User;

class InvoiceItemPolicy
{
    public function viewAny(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.view');
    }

    public function view(User $user, InvoiceItem $invoiceItem): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoiceItem->tenant_id
            && $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->can('invoices.edit');
    }

    public function update(User $user, InvoiceItem $invoiceItem): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoiceItem->tenant_id
            && $user->can('invoices.edit');
    }

    public function delete(User $user, InvoiceItem $invoiceItem): bool
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->tenant_id === $invoiceItem->tenant_id
            && $user->can('invoices.edit');
    }
}
