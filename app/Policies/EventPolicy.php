<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // daftar sudah dibatasi lewat Event::accessibleBy()
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isSuperAdmin() || $event->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isSuperAdmin() || $event->hasMember($user, UserRole::EventAdmin);
    }

    public function changeStatus(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageSessions(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageGuests(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageInvitations(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function viewMembers(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageReceptionists(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /** Event Admin hanya boleh menugaskan/mencabut resepsionis; admin acara hanya oleh Super Admin. */
    public function assignMember(User $actor, Event $event, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $event->hasMember($actor, UserRole::EventAdmin) && $target->role === UserRole::Receptionist;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->isSuperAdmin();
    }
}
