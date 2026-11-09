<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UserService
{
    public function create(array $data, UserRole $role): User
    {
        $user = new User(Arr::only($data, ['name', 'email', 'password']));
        $user->role = $role;
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $this->assertNotProtected($user);

        $user->fill(Arr::only($data, ['name', 'email']));

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return $user;
    }

    public function setStatus(User $user, UserStatus $status): User
    {
        $this->assertNotProtected($user);

        DB::transaction(function () use ($user, $status): void {
            $user->status = $status;
            $user->save();

            if ($status === UserStatus::Inactive) {
                $user->tokens()->delete();
            }
        });

        return $user;
    }

    public function delete(User $user): void
    {
        $this->assertNotProtected($user);

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    /** Akun Super Admin tidak dapat diubah lewat fitur manajemen pengguna. */
    private function assertNotProtected(User $user): void
    {
        if ($user->isSuperAdmin()) {
            throw new ApiException('Akun Super Admin tidak dapat diubah dari sini.', 'PROTECTED_ACCOUNT', 403);
        }
    }
}
