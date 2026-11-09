<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class EventMemberService
{
    public function __construct(private readonly UserService $users)
    {
    }

    /** Menugaskan akun yang sudah ada (event_admin atau receptionist) ke acara. */
    public function assign(Event $event, User $user): void
    {
        if ($user->role === UserRole::SuperAdmin || ! $user->isActive()) {
            throw new ApiException(
                'Hanya akun Event Admin atau Resepsionis yang aktif yang dapat ditugaskan.',
                'USER_NOT_ASSIGNABLE',
                422,
            );
        }

        if ($event->hasMember($user)) {
            throw new ApiException('Pengguna sudah ditugaskan pada acara ini.', 'ALREADY_ASSIGNED', 409);
        }

        $event->members()->attach($user->id, ['assigned_role' => $user->role->value]);
    }

    public function remove(Event $event, User $user): void
    {
        if (! $event->hasMember($user)) {
            throw new ApiException('Pengguna tidak ditugaskan pada acara ini.', 'NOT_ASSIGNED', 404);
        }

        $event->members()->detach($user->id);
    }

    /** Membuat akun resepsionis baru sekaligus menugaskannya ke acara. */
    public function createReceptionist(Event $event, array $data): User
    {
        return DB::transaction(function () use ($event, $data): User {
            $user = $this->users->create($data, UserRole::Receptionist);
            $event->members()->attach($user->id, ['assigned_role' => UserRole::Receptionist->value]);

            return $user;
        });
    }
}
