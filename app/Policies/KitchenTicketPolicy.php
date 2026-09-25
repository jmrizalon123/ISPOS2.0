<?php

namespace App\Policies;

use App\Models\KitchenTicket;
use App\Models\User;

class KitchenTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kds.view');
    }

    public function view(User $user, KitchenTicket $ticket): bool
    {
        if (! $user->can('kds.view')) {
            return false;
        }

        return $user->canAccessStore($ticket->store_id);
    }

    public function update(User $user, KitchenTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
