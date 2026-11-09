<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\User;

trait InteractsWithApi
{
    /**
     * Mengautentikasi permintaan berikutnya sebagai $user (token baru).
     * Guard di-reset karena aplikasi yang sama dipakai di antara beberapa request dalam satu test.
     */
    protected function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    protected function assign(Event $event, User $user): void
    {
        $event->members()->attach($user->id, ['assigned_role' => $user->role->value]);
    }
}
